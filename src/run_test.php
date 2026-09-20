<?php

use App\Models\Lecture;
use App\Models\Student;
use App\Services\VideoAccessService;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$student = Student::first();
$user = $student->user;
$lecture = Lecture::where('title', 'دورة تجريبية 2 امتحان')->first();
$service = app(VideoAccessService::class);
echo 'Blocked: '.($service->isBlockedByExam($user, $lecture, 'lecture_access') ? 'Yes' : 'No')."\n";
