<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Runs a callback at most once per key within the lock window. Used for
 * double-submit protection on public endpoints in addition to database
 * unique constraints, which remain the source of truth.
 */
class Idempotency
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function run(string $key, Closure $callback, int $seconds = 10): mixed
    {
        return Cache::lock('idempotency:'.$key, $seconds)->block($seconds, $callback);
    }
}
