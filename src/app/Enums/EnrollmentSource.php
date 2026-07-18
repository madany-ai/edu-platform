<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Manual = 'manual';
    case Purchase = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'يدوي',
            self::Purchase => 'شراء',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Manual => 'info',
            self::Purchase => 'success',
        };
    }
}
