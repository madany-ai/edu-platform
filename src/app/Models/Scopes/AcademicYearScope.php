<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AcademicYearScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $user = auth('sanctum')->user() ?? auth('web')->user();
        if ($user && $user->hasRole('student')) {
            $student = $user->student;
            if ($student && $student->academic_year) {
                $builder->where($model->getTable() . '.academic_year', $student->academic_year);
            }
        }
    }
}
