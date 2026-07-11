<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CourseAssistant extends Pivot
{
    use HasUuids;

    protected $table = 'course_assistants';
}
