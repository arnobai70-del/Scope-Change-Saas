<?php

namespace App\Enums;

enum ScopeItemType: string
{
    case Deliverable = 'deliverable';
    case Exclusion = 'exclusion';
    case Assumption = 'assumption';
    case Revision = 'revision';

    public function label(): string
    {
        return match ($this) {
            self::Deliverable => 'Deliverable',
            self::Exclusion => 'Exclusion',
            self::Assumption => 'Assumption',
            self::Revision => 'Revision allowance',
        };
    }
}
