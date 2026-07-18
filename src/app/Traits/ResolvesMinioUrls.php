<?php

namespace App\Traits;

trait ResolvesMinioUrls
{
    protected function resolveMinioUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        try {
            $url = \Illuminate\Support\Facades\Storage::disk('minio')
                ->temporaryUrl($path, now()->addHours(2));
        } catch (\Exception $e) {
            $url = \Illuminate\Support\Facades\Storage::disk('minio')->url($path);
        }

        $minioEndpoint = rtrim(config('filesystems.disks.minio.endpoint', 'http://minio:9000'), '/');
        $publicUrl = config('filesystems.disks.minio.url', 'http://localhost:9000/lms-videos');
        $parsed = parse_url($publicUrl);
        $publicHost = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');

        return str_replace($minioEndpoint, $publicHost, $url);
    }
}
