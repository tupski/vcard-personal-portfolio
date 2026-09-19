<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Key/value site settings (site identity, contact embed, SEO defaults, ...).
 *
 * All settings are loaded in ONE query and memoised for the request. The
 * previous per-key lookup issued a separate query for every distinct key, and
 * a single page render reads several (site name, map embed, description,
 * robots, site URL, OG image) — so the site paid five or six queries per
 * request for a table that has a handful of rows.
 *
 * The cache is flushed whenever a row changes, so a value can never go stale
 * inside a request, and missing keys are never memoised so a fallback stays
 * live until an admin actually persists a value.
 */
class Setting extends Model
{
    /**
     * Per-request memo of every setting, keyed by setting key.
     *
     * `null` means "not loaded yet"; an empty array means "loaded, table
     * empty". Distinguishing the two is what lets a missing key fall back
     * correctly without re-querying on every access.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $cache = null;

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
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::map();

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        return $all[$key];
    }

    /**
     * Every setting, loaded once per request.
     *
     * Named `map()` rather than `all()` because Eloquent already defines
     * `Model::all()` with an incompatible signature.
     *
     * @return array<string, string>
     */
    public static function map(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            static::$cache = static::query()->pluck('value', 'key')->all();
        } catch (Throwable) {
            // Requests that run before the settings table exists (fresh boot,
            // pre-migration) must fall back silently rather than erroring.
            // Deliberately NOT memoised so the next call can succeed once the
            // table is there.
            return [];
        }

        return static::$cache;
    }

    /**
     * Drop the per-request memo (also used after seeding in tests).
     */
    public static function flushCache(): void
    {
        static::$cache = null;
    }
}
