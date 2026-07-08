<?php

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['type', 'reference_id', 'name', 'price'];
}
