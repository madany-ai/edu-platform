<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BunnyStreamService
{
    private string $apiKey;
    private string $libraryId;
    private string $baseUrl;
    private string $cdnHostname;
    private string $signingKey;

    public function __construct()
    {
        $this->apiKey = config('services.bunny_stream.api_key', '');
        $this->libraryId = config('services.bunny_stream.library_id', '');
        $this->cdnHostname = config('services.bunny_stream.cdn_hostname', '');
        $this->signingKey = config('services.bunny_stream.signing_key', '');
        $this->baseUrl = "https://video.bunnycdn.com/library/{$this->libraryId}";
    }

    /**
     * Create a new video entry in Bunny Stream.
     * Returns the video GUID (used as the video ID).
     */
    public function createVideo(string $title): string
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/videos", [
            'title' => $title,
        ]);

        if ($response->failed()) {
            Log::error("Bunny Stream: Failed to create video", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("Failed to create video on Bunny Stream: {$response->body()}");
        }

        return $response->json('guid');
    }

    /**
     * Upload video content to an existing video entry.
     */
    public function uploadContent(string $videoId, string $filePath): void
    {
        $url = "{$this->baseUrl}/videos/{$videoId}";
        $fileSize = filesize($filePath);

        Log::info("Bunny Stream: Uploading {$fileSize} bytes to {$url}");

        $fileHandle = fopen($filePath, 'r');
        if (!$fileHandle) {
            throw new \Exception("Failed to open local file for reading: {$filePath}");
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_UPLOAD         => true,
            CURLOPT_INFILE         => $fileHandle,
            CURLOPT_INFILESIZE     => $fileSize,
            CURLOPT_HTTPHEADER     => [
                "AccessKey: {$this->apiKey}",
                "Content-Type: application/octet-stream",
                "Content-Length: {$fileSize}",
            ],
            CURLOPT_TIMEOUT        => 3600,
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        fclose($fileHandle);

        if ($error) {
            Log::error("Bunny Stream: cURL upload error", ['video_id' => $videoId, 'error' => $error]);
            throw new \Exception("cURL upload failed: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Log::error("Bunny Stream: Failed to upload content", [
                'video_id' => $videoId,
                'status'   => $httpCode,
                'body'     => $response,
            ]);
            throw new \Exception("Failed to upload video content to Bunny Stream: HTTP {$httpCode}");
        }

        Log::info("Bunny Stream: Upload complete for {$videoId} (HTTP {$httpCode})");
    }

    /**
     * Get video details including processing status.
     * Status: 0=uploaded, 1=processing, 2=finished, 3=error
     */
    public function getVideo(string $videoId): array
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->get("{$this->baseUrl}/videos/{$videoId}");

        if ($response->failed()) {
            Log::error("Bunny Stream: Failed to get video", [
                'video_id' => $videoId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("Failed to get video from Bunny Stream: {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Check if video processing is complete.
     */
    public function isProcessed(string $videoId): bool
    {
        try {
            $video = $this->getVideo($videoId);
            return ($video['status'] ?? 0) === 2;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a video from Bunny Stream.
     */
    public function deleteVideo(string $videoId): void
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->delete("{$this->baseUrl}/videos/{$videoId}");

        if ($response->failed()) {
            Log::warning("Bunny Stream: Failed to delete video", [
                'video_id' => $videoId,
                'status' => $response->status(),
            ]);
        }
    }

    /**
     * Generate a signed playback URL for a video.
     * Uses Bunny Stream Token Authentication.
     */
    public function getSignedPlaybackUrl(string $videoId, int $expirationMinutes = 60): string
    {
        $expiration = now()->addMinutes($expirationMinutes)->timestamp;

        if (!empty($this->signingKey)) {
            // Bunny Embed View Token Auth: sha256(key + videoId + expiration).
            // Note: Bunny Stream API explicitly requires simple SHA-256 concatenation, not HMAC.
            $token = hash('sha256', $this->signingKey . $videoId . $expiration);
            return "https://iframe.mediadelivery.net/embed/{$this->libraryId}/{$videoId}?token={$token}&expires={$expiration}";
        }

        return "https://iframe.mediadelivery.net/embed/{$this->libraryId}/{$videoId}";
    }
}
