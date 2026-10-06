<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case MarkedSent = 'marked_sent';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Not required',
            self::Pending => 'Pending',
            self::MarkedSent => 'Marked sent by client',
            self::Confirmed => 'Confirmed received',
        };
    }
}
