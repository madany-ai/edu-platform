<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Course\Models\Course;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'is_active'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
