<?php

namespace App\Domain\Geography\Models;

use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    protected $fillable = ['name', 'sort_order'];
}
