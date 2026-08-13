<?php

namespace App\Jobs;

use App\Services\BunnyStreamService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideoHLS implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 3;

    protected $lecture;
    protected ?string $bunnyVideoId;
    protected int $attemptCount;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Lecture $lecture, ?string $bunnyVideoId = null, int $attemptCount = 1)
    {
        $this->lecture = $lecture;
        $this->bunnyVideoId = $bunnyVideoId;
        $this->attemptCount = $attemptCount;
    }

    /**
     * Execute the job — upload MP4 to Bunny Stream which handles HLS transcoding automatically.
     */
    public function handle(BunnyStreamService $bunny): void
    {
        $lecture = $this->lecture;

        // If this is the initial run (no video ID yet)
        if (!$this->bunnyVideoId) {
            if (!$lecture->video_path || filter_var($lecture->video_path, FILTER_VALIDATE_URL)) {
                return;
            }

            // Initialize metadata
            \App\Models\LectureVideo::updateOrCreate(
                ['lecture_id' => $lecture->id],
                ['status' => 'processing']
            );

            // Download source from MinIO to a temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'video_');
            $videoStream = \Illuminate\Support\Facades\Storage::disk('public')->readStream($lecture->video_path);

            if (!$videoStream) {
                \App\Models\LectureVideo::updateOrCreate(
                    ['lecture_id' => $lecture->id],
                    ['status' => 'failed']
                );
                return;
            }

            $localFileHandle = fopen($tempFile, 'w');
            stream_copy_to_stream($videoStream, $localFileHandle);
            fclose($localFileHandle);
            fclose($videoStream);

            try {
                // 1. Create video entry on Bunny Stream
                $videoId = $bunny->createVideo("lecture_{$lecture->id}");
                Log::info("Bunny Stream: Created video {$videoId} for Lecture {$lecture->id}");

                // 2. Upload the MP4 content
                $bunny->uploadContent($videoId, $tempFile);
                Log::info("Bunny Stream: Uploaded content for video {$videoId}");

                // 3. Update LectureVideo record
                \App\Models\LectureVideo::updateOrCreate(
                    ['lecture_id' => $lecture->id],
                    [
                        'bunny_video_id' => $videoId,
                        'status' => 'processing',
                    ]
                );

                // 4. Dispatch the polling job with a delay
                self::dispatch($lecture, $videoId, 1)->delay(now()->addSeconds(15));

            } catch (\Exception $e) {
                Log::error("Bunny Stream: Upload failed for Lecture {$lecture->id}: " . $e->getMessage());
                \App\Models\LectureVideo::updateOrCreate(
                    ['lecture_id' => $lecture->id],
                    ['status' => 'failed']
                );
                throw $e;
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
            return;
        }

        // If this is a polling run
        $videoId = $this->bunnyVideoId;
        Log::info("Bunny Stream Polling: Checking status for video {$videoId} (Attempt {$this->attemptCount}/30)");

        try {
            $videoData = $bunny->getVideo($videoId);
            $status = $videoData['status'] ?? 0;

            if ($status === 2) {
                // Transcoding finished!
                $durationSeconds = $videoData['length'] ?? 0;
                $durationMinutes = (int) round($durationSeconds / 60);

                \App\Models\LectureVideo::updateOrCreate(
                    ['lecture_id' => $lecture->id],
                    [
                        'duration' => $durationMinutes,
                        'status' => 'completed',
                        'original_filename' => basename($lecture->video_path),
                    ]
                );

                Log::info("Bunny Stream: Video {$videoId} completed transcoding for Lecture {$lecture->id}");

                // Clean up the original MinIO upload since it is transcoded on Bunny
                if ($lecture->video_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($lecture->video_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($lecture->video_path);
                }
                return;
            }

            if ($status === 3) {
                throw new \Exception("Bunny Stream encoding failed for video {$videoId}");
            }

            // Still processing: dispatch another check if under 30 attempts
            if ($this->attemptCount < 30) {
                self::dispatch($lecture, $videoId, $this->attemptCount + 1)->delay(now()->addSeconds(15));
            } else {
                throw new \Exception("Bunny Stream transcoding timed out after 30 attempts for video {$videoId}");
            }

        } catch (\Exception $e) {
            Log::error("Bunny Stream Polling: Failed for video {$videoId}: " . $e->getMessage());
            \App\Models\LectureVideo::updateOrCreate(
                ['lecture_id' => $lecture->id],
                ['status' => 'failed']
            );
            throw $e;
        }
    }
}
