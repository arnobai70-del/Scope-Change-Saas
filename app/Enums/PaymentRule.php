<?php

namespace App\Enums;

enum PaymentRule: string
{
    case None = 'none';
    case BeforeStart = 'before_start';
    case BeforeHandoff = 'before_handoff';
    case Custom = 'custom';

    public function requiresPayment(): bool
    {
        return $this !== self::None;
    }

    public function label(): string
    {
        return match ($this) {
            self::None => 'No payment required',
            self::BeforeStart => 'Payment required before work starts',
            self::BeforeHandoff => 'Payment required before handoff',
            self::Custom => 'Custom payment terms',
        };
    }
}
