<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The public content cache.
 *
 * Content that every visitor sees identically (profile, services, skills,
 * projects, blog posts, settings) is expensive to rebuild on every request but
 * changes only when an admin edits it. This class gives the repository one
 * place to read and write those values, so caching never leaks into Blade and
 * never becomes a second data-access layer.
 *
 * Design rules, each of which exists to avoid a specific failure:
 *
 *  - One short version tag keys the whole public namespace. Invalidation
 *    bumps that tag, which orphans every cached value at once. That is a
 *    targeted invalidation (no Cache::flush(), which would also wipe unrelated
 *    application cache such as sessions or rate limiters).
 *  - Per-request memoisation still happens in the repository. The cache is
 *    about surviving across requests, not within one.
 *  - Only public, user-independent data is cached. Nothing here touches the
 *    admin session, contact messages, CSRF or anything request-specific.
 *  - The store is whatever `config('cache.default')` says — `database` by
 *    default, `file` on shared hosting, Redis if a deployment provides it.
 *    No driver is required by this class.
 *  - A cache failure is never fatal: if the store is unavailable the value is
 *    computed normally, because a broken cache must not break the website.
 */
class ContentCache
{
    /**
     * Key holding the current namespace version.
     *
     * Deliberately outside the versioned namespace: bumping the version is how
     * every namespaced key is invalidated.
     */
    private const VERSION_KEY = 'content:v';

    /**
     * How long a cached public value stays valid when nothing invalidates it.
     *
     * Invalidation is event-driven, so this is only a backstop against a
     * missed event — it is intentionally generous rather than short, because a
     * short TTL would silently re-introduce the per-request cost it exists to
     * remove.
     */
    private const TTL_SECONDS = 86400;

    /**
     * Whether caching is active for this process.
     */
    public function enabled(): bool
    {
        return (bool) config('content.cache', true);
    }

    /**
     * Read a value, or compute and store it.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function remember(string $key, callable $callback): mixed
    {
        if (! $this->enabled()) {
            return $callback();
        }

        try {
            return $this->store()->remember(
                $this->namespaced($key),
                self::TTL_SECONDS,
                $callback,
            );
        } catch (Throwable) {
            // A cache outage must degrade to a slower page, never an error.
            return $callback();
        }
    }

    /**
     * Drop every cached public value.
     *
     * Called from model events whenever content an admin can edit changes, so
     * an edit is visible on the very next request. The whole public namespace
     * is invalidated together because a single edit can legitimately affect
     * several cached values (e.g. renaming a category changes the blog listing
     * and every post's rendered category name).
     */
    public function invalidate(): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            $next = $this->nextVersion();

            $this->store()->forever(self::VERSION_KEY, $next);

            // Keep the in-process memo consistent with what was just stored,
            // so a long-running worker does not read the old namespace.
            $this->version = $next;
        } catch (Throwable) {
            // Nothing to do: the backstop TTL still bounds staleness.
        }
    }

    /**
     * Namespaced cache key for the current version.
     */
    private function namespaced(string $key): string
    {
        return 'content:v'.$this->version().':'.$key;
    }

    /**
     * Current namespace version (0 before anything has been invalidated).
     *
     * Memoised for the request. Without this, every cached value costs two
     * cache round-trips — one to read the version and one to read the value —
     * which doubled the cache traffic on a page that reads several values.
     */
    private function version(): int
    {
        if ($this->version !== null) {
            return $this->version;
        }

        try {
            $this->version = (int) $this->store()->get(self::VERSION_KEY, 0);
        } catch (Throwable) {
            $this->version = 0;
        }

        return $this->version;
    }

    /**
     * A version that differs from the current one.
     *
     * A monotonic counter is used instead of a random value so the cache keys
     * stay readable and debuggable.
     */
    private function nextVersion(): int
    {
        return $this->version() + 1;
    }

    /**
     * Namespace version, resolved at most once per request.
     */
    private ?int $version = null;

    private function store(): CacheRepository
    {
        return Cache::store();
    }
}
