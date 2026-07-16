<?php

namespace App\Jobs;

use App\Services\BunnyStreamService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessVideoHLS implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;
    public int $tries = 3;

    protected $lecture;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Lecture $lecture)
    {
        $this->lecture = $lecture;
    }

    /**
     * Execute the job — upload MP4 to Bunny Stream which handles HLS transcoding automatically.
     */
    public function handle(BunnyStreamService $bunny): void
    {
        $lecture = $this->lecture;

        if (!$lecture->video_path) {
            return;
        }

        // Initialize metadata
        \App\Models\LectureVideo::updateOrCreate(
            ['lecture_id' => $lecture->id],
            ['status' => 'processing']
        );

        // Download source from MinIO to a temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'video_');
        $videoStream = \Illuminate\Support\Facades\Storage::disk('minio')->readStream($lecture->video_path);

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

            Log::info("Bunny Stream: Upload complete for {$videoId}. Waiting for Bunny to finish transcoding...");

            // 3. Poll Bunny until transcoding is done (status 2) — max 5 minutes
            $status = 0;
            for ($attempt = 0; $attempt < 30; $attempt++) {
                sleep(10);
                $videoData = $bunny->getVideo($videoId);
                $status = $videoData['status'] ?? 0;
                Log::info("Bunny Stream: Video {$videoId} status = {$status} (attempt " . ($attempt + 1) . "/30)");
                if ($status === 2) {
                    break;
                }
                if ($status === 3) {
                    throw new \Exception("Bunny Stream encoding failed for video {$videoId}");
                }
            }

            $durationSeconds = $videoData['length'] ?? 0;
            $durationMinutes = (int) round($durationSeconds / 60);

            // 4. Update LectureVideo record
            \App\Models\LectureVideo::updateOrCreate(
                ['lecture_id' => $lecture->id],
                [
                    'bunny_video_id' => $videoId,
                    'original_filename' => basename($lecture->video_path),
                    'duration' => $durationMinutes,
                    'status' => $status === 2 ? 'completed' : 'processing',
                ]
            );

            Log::info("Bunny Stream: Video {$videoId} final status = {$status} for Lecture {$lecture->id}");

            // 5. Clean up the original MinIO upload (the MP4 is now on Bunny)
            \Illuminate\Support\Facades\Storage::disk('minio')->delete($lecture->video_path);

        } catch (\Exception $e) {
            Log::error("Bunny Stream: Failed for Lecture {$lecture->id}: " . $e->getMessage());
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
    }
}
