<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('activitylog:clean')->daily();

// النسخ الاحتياطي التلقائي (البيانات فقط) أسبوعياً، وتنظيف النسخ القديمة يومياً
Schedule::command('backup:clean')->daily();
Schedule::command('backup:run --only-db')->weeklyOn(5, '00:00');
