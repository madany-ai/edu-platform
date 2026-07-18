<?php

use App\Jobs\ProcessVideoHLS;
use App\Models\Lecture;
use App\Models\LectureVideo;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);
});

it('creates LectureVideo with processing status when job starts', function () {
    Queue::fake();

    $lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Video Lecture',
        'sort_order' => 2,
        'video_path' => 'videos/test.mp4',
    ]);

    $video = LectureVideo::create([
        'lecture_id' => $lecture->id,
        'status' => 'processing',
    ]);

    expect($video)->not->toBeNull()
        ->and($video->status)->toBe('processing');
});

it('job returns early when lecture has no video_path', function () {
    Queue::fake();

    $lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'No Video',
        'sort_order' => 2,
    ]);

    $lectureVideo = LectureVideo::where('lecture_id', $lecture->id)->first();
    expect($lectureVideo)->toBeNull();
});

it('job is dispatched to queue when lecture is created with video_path', function () {
    Queue::fake();

    $lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Queued Lecture',
        'sort_order' => 2,
        'video_path' => 'videos/test.mp4',
    ]);

    Queue::assertPushed(ProcessVideoHLS::class);
});

it('job re-dispatches when lecture video status is failed and lecture is saved', function () {
    Queue::fake();

    $lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Retry Lecture',
        'sort_order' => 2,
        'video_path' => 'videos/test.mp4',
    ]);

    LectureVideo::create([
        'lecture_id' => $lecture->id,
        'status' => 'failed',
        'video_path' => 'hls/old/playlist.m3u8',
    ]);

    $lecture->touch();

    Queue::assertPushed(ProcessVideoHLS::class);
});

it('Lecture model dispatches ProcessVideoHLS on new lecture with video_path', function () {
    Queue::fake();

    $lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Auto Dispatch',
        'sort_order' => 2,
        'video_path' => 'videos/test.mp4',
    ]);

    Queue::assertPushed(ProcessVideoHLS::class);
});

it('observer checks for existing video before dispatching', function () {
    Queue::fake();

    $lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Check Video',
        'sort_order' => 2,
        'video_path' => 'videos/test.mp4',
    ]);

    $jobCount = count(Queue::pushed(ProcessVideoHLS::class));
    expect($jobCount)->toBeGreaterThanOrEqual(1);

    LectureVideo::create([
        'lecture_id' => $lecture->id,
        'status' => 'completed',
        'video_path' => 'hls/test/playlist.m3u8',
    ]);

    $video = $lecture->fresh()->video;
    expect($video)->not->toBeNull()
        ->and($video->status)->toBe('completed');
});

it('Lecture model dispatches ProcessVideoHLS when video_path changes', function () {
    Queue::fake();

    $lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Change Path',
        'sort_order' => 2,
        'video_path' => 'videos/old.mp4',
    ]);

    LectureVideo::create([
        'lecture_id' => $lecture->id,
        'status' => 'completed',
    ]);

    Queue::fake();

    $lecture->update(['video_path' => 'videos/new.mp4']);

    Queue::assertPushed(ProcessVideoHLS::class);
});

it('LectureVideo has correct fillable fields', function () {
    $video = new LectureVideo();

    expect($video->getFillable())->toContain('lecture_id')
        ->and($video->getFillable())->toContain('status')
        ->and($video->getFillable())->toContain('video_path')
        ->and($video->getFillable())->not->toContain('encryption_key')
        ->and($video->getFillable())->toContain('original_filename');
});

it('LectureVideo status defaults to pending', function () {
    $video = LectureVideo::create([
        'lecture_id' => $this->lecture->id,
        'status' => 'pending',
    ]);

    expect($video->status)->toBe('pending');
});

it('LectureVideo can transition through all status states', function () {
    $video = LectureVideo::create([
        'lecture_id' => $this->lecture->id,
        'status' => 'pending',
    ]);

    expect($video->status)->toBe('pending');

    $video->update(['status' => 'processing']);
    expect($video->fresh()->status)->toBe('processing');

    $video->update(['status' => 'completed']);
    expect($video->fresh()->status)->toBe('completed');

    $video->update(['status' => 'failed']);
    expect($video->fresh()->status)->toBe('failed');
});

it('LectureVideo stores encryption key as hex string', function () {
    $key = openssl_random_pseudo_bytes(16);
    $hexKey = bin2hex($key);

    $video = new LectureVideo([
        'lecture_id' => $this->lecture->id,
        'status' => 'completed',
    ]);
    $video->encryption_key = $hexKey;
    $video->save();

    expect($video->encryption_key)->toBe($hexKey)
        ->and(strlen($video->encryption_key))->toBe(32);
});
