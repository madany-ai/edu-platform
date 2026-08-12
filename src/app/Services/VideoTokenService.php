<?php

namespace App\Services;

class VideoTokenService
{
    private string $secret;

    public function __construct()
    {
        // Derive a strong secret from the app key
        $this->secret = hash('sha256', config('app.key') . 'video-stream-token');
    }

    /**
     * Generate a short-lived HMAC token for video streaming.
     * Token is tied to: user_id, video_id, lecture_id, expiry.
     */
    public function generateVideoToken(string $videoId, int|string $userId, string $lectureId, string $ipAddress = '', int $expiryHours = 4): string
    {
        $payload = [
            'v' => $videoId,
            'u' => (string) $userId,
            'l' => $lectureId,
            'ip' => $ipAddress,
            'e' => now()->addHours($expiryHours)->timestamp,
        ];

        $payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payloadEncoded, $this->secret);

        return $payloadEncoded . '.' . $signature;
    }

    /**
     * Validate a token and return its payload, or null if invalid/expired.
     */
    public function validateVideoToken(string $token, string $videoId): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadEncoded, $signature] = $parts;

        // Constant-time comparison to prevent timing attacks
        $expectedSignature = hash_hmac('sha256', $payloadEncoded, $this->secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($payloadEncoded, '-_', '+/')), true);
        if (!is_array($payload)) {
            return null;
        }

        // Check expiry
        if (($payload['e'] ?? 0) < now()->timestamp) {
            return null;
        }

        // Check video ID matches
        if (($payload['v'] ?? '') !== $videoId) {
            return null;
        }

        // Check IP address if it was bound to the token
        $boundIp = $payload['ip'] ?? '';
        if ($boundIp !== '' && $boundIp !== request()->ip()) {
            return null;
        }

        return $payload;
    }
}
