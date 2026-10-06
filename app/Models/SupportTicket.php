<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $workspace_id
 * @property int|null $user_id
 * @property string|null $name
 * @property string $email
 * @property string $subject
 * @property string $body
 * @property string $state
 * @property string $priority
 * @property string|null $internal_notes
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 */
#[Fillable(['workspace_id', 'user_id', 'name', 'email', 'subject', 'body', 'priority'])]
class SupportTicket extends Model
{
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }
}
