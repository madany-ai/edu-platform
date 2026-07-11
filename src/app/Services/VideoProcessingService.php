<?php

namespace App\Services;

use App\Models\Lecture;
use App\Models\LectureVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoProcessingService
{
    /**
     * Process an MP4 video file: convert to HLS and encrypt with AES-128.
     */
    public function process(Lecture $lecture, string $tempSourcePath): void
    {
        // 1. Create a secure folder in storage for processing
        $processId = (string) Str::uuid();
        $workDir = storage_path("app/processing/{$processId}");
        if (!file_exists($workDir)) {
            mkdir($workDir, 0755, true);
        }

        try {
            // 2. Generate HLS AES-128 Key
            $key = openssl_random_pseudo_bytes(16);
            $keyPath = "{$workDir}/key.key";
            file_put_contents($keyPath, $key);

            // 3. Create key info file for FFmpeg
            // Line 1: Key URI (this will be replaced dynamically on serving)
            // Line 2: Path to the key file for encryption
            $keyInfoContent = "key.key\n{$keyPath}\n";
            $keyInfoPath = "{$workDir}/key_info.txt";
            file_put_contents($keyInfoPath, $keyInfoContent);

            // 4. Run FFmpeg command to segment and encrypt
            $playlistName = "playlist.m3u8";
            $outputPath = "{$workDir}/{$playlistName}";
            $segmentPattern = "{$workDir}/segment_%03d.ts";

            $cmd = sprintf(
                'ffmpeg -y -i %s -hls_time 10 -hls_key_info_file %s -hls_playlist_type vod -hls_segment_filename %s %s 2>&1',
                escapeshellarg($tempSourcePath),
                escapeshellarg($keyInfoPath),
                escapeshellarg($segmentPattern),
                escapeshellarg($outputPath)
            );

            Log::info("Running FFmpeg: {$cmd}");
            exec($cmd, $output, $resultCode);

            if ($resultCode !== 0) {
                throw new \Exception("FFmpeg failed with exit code {$resultCode}. Output: " . implode("\n", $output));
            }

            // 5. Upload files to MinIO
            $minioFolder = "hls/{$lecture->id}";
            $files = glob("{$workDir}/*");

            foreach ($files as $file) {
                $filename = basename($file);
                if ($filename === 'key.key' || $filename === 'key_info.txt') {
                    // Do NOT upload the key or key_info files to MinIO!
                    continue;
                }

                $destination = "{$minioFolder}/{$filename}";
                Storage::disk('minio')->put($destination, fopen($file, 'r'), 'private');
            }

            // 6. Update or Create LectureVideo metadata
            LectureVideo::updateOrCreate(
                ['lecture_id' => $lecture->id],
                [
                    'video_path' => "{$minioFolder}/{$playlistName}",
                    'original_filename' => basename($lecture->video_path),
                    'encryption_key' => bin2hex($key),
                    'status' => 'completed',
                    'duration' => $this->getVideoDuration($tempSourcePath),
                ]
            );

            Log::info("Successfully processed HLS video for Lecture: {$lecture->id}");

        } catch (\Exception $e) {
            Log::error("Failed processing video for Lecture: {$lecture->id}. Error: " . $e->getMessage());
            LectureVideo::updateOrCreate(
                ['lecture_id' => $lecture->id],
                ['status' => 'failed']
            );
            throw $e;
        } finally {
            // Clean up temporary files
            $this->cleanDir($workDir);
        }
    }

    /**
     * Get video duration using ffprobe.
     */
    private function getVideoDuration(string $path): int
    {
        $cmd = sprintf('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s', escapeshellarg($path));
        exec($cmd, $output, $result);
        return isset($output[0]) ? (int) round($output[0] / 60) : 0; // Return in minutes
    }

    /**
     * Clean directory.
     */
    private function cleanDir(string $dir): void
    {
        if (is_dir($dir)) {
            $files = glob("{$dir}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }
}
