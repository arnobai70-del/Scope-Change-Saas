<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $scope
 * @property bool $enabled
 * @property array<string, mixed>|null $config
 * @property string|null $description
 */
class FeatureFlag extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'config' => 'array'];
    }

    public static function enabled(string $key): bool
    {
        $flag = static::query()->where('key', $key)->first();

        return $flag->enabled ?? (bool) config("features.{$key}", false);
    }
}
