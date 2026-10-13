<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide settings editable from the admin panel, one row per section.
 *
 * @property int $id
 * @property string $key
 * @property array<string, mixed>|null $value
 */
class SiteSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
