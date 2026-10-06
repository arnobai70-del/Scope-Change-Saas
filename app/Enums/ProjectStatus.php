<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }

    /**
     * Active and on-hold projects count towards plan limits.
     */
    public function countsTowardsLimit(): bool
    {
        return $this === self::Active || $this === self::OnHold;
    }
}
