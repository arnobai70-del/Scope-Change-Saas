<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Owners and admins manage billing, team, branding and settings.
     */
    public function canManageWorkspace(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
