<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessVideoHLS implements ShouldQueue
{
    use Queueable;

    protected $lecture;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Lecture $lecture)
    {
        $this->lecture = $lecture;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\VideoProcessingService $processingService): void
    {
        $lecture = $this->lecture;

        // Ensure we have a video path
        if (!$lecture->video_path) {
            return;
        }

        // Initialize metadata
        \App\Models\LectureVideo::updateOrCreate(
            ['lecture_id' => $lecture->id],
            ['status' => 'processing']
        );

        // Download source from MinIO
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
            // Process the HLS Transcoding & Encryption
            $processingService->process($lecture, $tempFile);
        } catch (\Exception $e) {
            // Error logged inside service
            throw $e;
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
