<?php

namespace App\Domain\Notification\Services;

use App\Domain\Auth\Models\User;
use App\Domain\Notification\Models\Notification;

class NotificationService
{
    public function send(User $user, string $title, string $body): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
        ]);
    }
}
