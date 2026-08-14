<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VideoTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoStreamController extends Controller
{
    public function __construct(
        private VideoTokenService $tokenService
    ) {}

    /**
     * Proxy the HLS master playlist, rewriting all URLs to go through our backend.
     * This hides the real Bunny CDN URL from the client.
     */
    public function playlist(Request $request, string $videoId)
    {
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $videoId)) {
            return response()->json(['error' => 'معرف الفيديو غير صالح'], 400);
        }

        $token = $request->query('token', '');
        $payload = $this->tokenService->validateVideoToken($token, $videoId);

        if (!$payload) {
            return response()->json(['error' => 'رابط الفيديو غير صالح أو منتهي الصلاحية'], 401);
        }

        $user = \App\Models\User::find($payload['u']);
        if (!$user || $user->status !== \App\Enums\UserStatus::Active) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $cdnHostname = config('services.bunny_stream.cdn_hostname');
        $m3u8Url = "https://{$cdnHostname}/{$videoId}/playlist.m3u8";

        try {
            $response = Http::timeout(15)->get($m3u8Url);

            if ($response->failed()) {
                Log::warning("VideoStream: Failed to fetch master playlist", [
                    'video_id' => $videoId,
                    'status'   => $response->status(),
                ]);
                return response()->json(['error' => 'الفيديو غير متاح'], 404);
            }

            $content = $response->body();
            $content = $this->rewritePlaylistUrls($content, $videoId, $token, '');

            return response($content, 200, [
                'Content-Type'                => 'application/vnd.apple.mpegurl',
                'Cache-Control'               => 'no-store, no-cache, must-revalidate',
                'X-Content-Type-Options'      => 'nosniff',
                'Access-Control-Allow-Origin' => config('app.url'),
            ]);
        } catch (\Exception $e) {
            Log::error("VideoStream: Exception fetching playlist", [
                'video_id' => $videoId,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['error' => 'خطأ في تحميل الفيديو'], 500);
        }
    }

    /**
     * Proxy HLS sub-playlists (.m3u8) and segments (.ts / .aac).
     * Sub-playlists are re-written; segments are fetched and streamed directly.
     */
    public function segment(Request $request, string $videoId)
    {
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $videoId)) {
            return response()->json(['error' => 'معرف الفيديو غير صالح'], 400);
        }

        $token = $request->query('token', '');
        $file  = $request->query('file', '');

        $payload = $this->tokenService->validateVideoToken($token, $videoId);
        if (!$payload || !$file) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $user = \App\Models\User::find($payload['u']);
        if (!$user || $user->status !== \App\Enums\UserStatus::Active) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        // Sanitise: strip directory traversal
        $file = ltrim(preg_replace('/\.{2,}/', '', $file), '/');
        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $file)) {
            return response()->json(['error' => 'ملف غير صالح'], 400);
        }

        $cdnHostname = config('services.bunny_stream.cdn_hostname');
        $fileUrl     = "https://{$cdnHostname}/{$videoId}/{$file}";

        try {
            // Sub-playlist: proxy + rewrite
            if (str_ends_with($file, '.m3u8')) {
                $response = Http::timeout(15)->get($fileUrl);

                if ($response->failed()) {
                    return response()->json(['error' => 'القائمة غير موجودة'], 404);
                }

                $baseDir = ($dir = dirname($file)) !== '.' ? $dir : '';
                $content = $this->rewritePlaylistUrls($response->body(), $videoId, $token, $baseDir);

                return response($content, 200, [
                    'Content-Type'  => 'application/vnd.apple.mpegurl',
                    'Cache-Control' => 'no-store, no-cache',
                    'Access-Control-Allow-Origin' => config('app.url'),
                ]);
            }

            // Video/audio segment: proxy and stream back
            // DO NOT use Http::get() body as it loads the entire segment into RAM
            // We'll use fopen to pipe the stream
            
            // Check headers to get content type (lightweight HEAD request or just default)
            $contentType = 'video/mp2t';
            if (str_ends_with($file, '.aac')) {
                $contentType = 'audio/aac';
            }

            return response()->stream(function () use ($fileUrl) {
                $stream = @fopen($fileUrl, 'r');
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type'                => $contentType,
                'Cache-Control'               => 'private, max-age=3600',
                'Access-Control-Allow-Origin' => config('app.url'),
            ]);
        } catch (\Exception $e) {
            Log::error("VideoStream: Exception fetching segment", [
                'video_id' => $videoId,
                'file'     => $file,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['error' => 'خطأ في تحميل القطعة'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Rewrite all relative/absolute resource URLs in an HLS playlist body
     * so they route through our segment proxy endpoint.
     *
     * @param string $content  Raw m3u8 text
     * @param string $videoId  Bunny video GUID
     * @param string $token    Our HMAC token
     * @param string $baseDir  Directory prefix for relative paths (e.g. "720p")
     */
    private function rewritePlaylistUrls(string $content, string $videoId, string $token, string $baseDir): string
    {
        $segmentBase = "/api/video/{$videoId}/segment" . '?token=' . urlencode($token) . '&file=';

        return preg_replace_callback(
            '/^([^\s#].+\.(m3u8|ts|aac|mp4|vtt))(\?.*)?$/m',
            function ($matches) use ($segmentBase, $baseDir) {
                $filePath = $matches[1];

                // Already an absolute URL → strip host, keep only path portion
                if (str_starts_with($filePath, 'http')) {
                    $parsed = parse_url($filePath);
                    $filePath = ltrim($parsed['path'] ?? $filePath, '/');
                    // Remove leading videoId segment if present (Bunny includes it)
                    $filePath = preg_replace('/^[a-f0-9\-]{36}\//', '', $filePath);
                } elseif ($baseDir !== '') {
                    $filePath = $baseDir . '/' . $filePath;
                }

                return $segmentBase . urlencode($filePath);
            },
            $content
        );
    }
}
