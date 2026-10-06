<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $email
 * @property string $reason
 * @property int $bounce_count
 */
class EmailSuppression extends Model
{
    protected $guarded = ['id'];

    /** Soft bounces are counted but only suppress after repeated failures. */
    public const PENDING = 'soft_bounce_pending';

    public static function isSuppressed(string $email): bool
    {
        return static::query()
            ->where('email', mb_strtolower($email))
            ->where('reason', '!=', self::PENDING)
            ->exists();
    }
}
