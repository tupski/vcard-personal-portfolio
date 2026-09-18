<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value site settings (site identity, contact embed, ...).
 *
 * Values are strings persisted by the admin in Phase 4+; Setting::get() is
 * memoised per request and the cache is flushed whenever a row changes, so
 * the value can never go stale inside a request.
 */
class Setting extends Model
{
    /**
     * Per-request memo, keyed by setting key.
     *
     * @var array<string, mixed>
     */
    protected static array $cache = [];

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Look up a single setting value, with an optional fallback.
     *
     * Missing keys are never memoised: the fallback stays live until an admin
     * actually persists a value for the key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, static::$cache)) {
            $stored = static::query()->where('key', $key)->value('value');

            if ($stored === null) {
                return $default;
            }

            static::$cache[$key] = $stored;
        }

        return static::$cache[$key];
    }

    /**
     * Drop the per-request memo (also used after seeding in tests).
     */
    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
