<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait ResolvesMinioUrls
{
    protected function resolveMinioUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
