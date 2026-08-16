<?php
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = \App\Models\Student::first();
$user = $student->user;
$lecture = \App\Models\Lecture::where('title', 'دورة تجريبية 2 امتحان')->first();
$service = app(\App\Services\VideoAccessService::class);
echo "Blocked: " . ($service->isBlockedByExam($user, $lecture, 'lecture_access') ? "Yes" : "No") . "\n";
