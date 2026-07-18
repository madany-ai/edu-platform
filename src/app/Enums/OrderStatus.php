<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Completed = 'completed';
    case Failed    = 'failed';
    case Refunded  = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'قيد الانتظار',
            self::Completed => 'مكتمل',
            self::Failed    => 'فشل',
            self::Refunded  => 'مسترجع',
        };
    }

    public function color(): string|array|null
    {
        return match ($this) {
            self::Completed => 'success',
            self::Pending   => 'warning',
            self::Failed    => 'danger',
            self::Refunded  => 'gray',
        };
    }
}
