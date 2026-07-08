<?php

namespace App\Domain\Analytics\Models;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'json'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
