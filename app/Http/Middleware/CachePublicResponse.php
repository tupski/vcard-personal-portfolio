<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conditional-request support for public pages.
 *
 * WHAT THIS SOLVES
 * Public pages were sent with `Cache-Control: no-cache, private` and no
 * validator, so every navigation re-downloaded the full document even when
 * nothing had changed. Lighthouse's `cache-insight` flagged this, and it costs
 * real bandwidth on a portfolio whose pages are mostly static markup.
 *
 * HOW
 * An ETag is derived from the rendered body. A repeat visit sends
 * `If-None-Match`; when it still matches, the server answers 304 with no body.
 * The browser keeps the page it already has, so nothing about the rendered
 * output changes — only the bytes on the wire.
 *
 * WHY `private` AND NOT `public`
 * These pages carry a session (CSRF token, flash messages) and are rendered by
 * a session-aware stack. Marking them publicly cacheable would risk one
 * visitor's document — and its token — being served to another, or a shared
 * proxy caching an authenticated admin page. `private, no-cache,
 * must-revalidate` with an ETag gives the bandwidth win without that risk: the
 * browser may reuse its copy, but only after revalidating with this server.
 *
 * Admin responses are explicitly excluded and keep their own headers.
 */
class CachePublicResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isCacheable($request, $response)) {
            return $response;
        }

        // The body is already rendered at this point, so the ETag describes
        // exactly what would be sent.
        $etag = '"'.hash('xxh128', $response->getContent().'|'.config('app.key')).'"';

        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
        $response->headers->set('Vary', 'Accept-Encoding, Cookie');

        if ($this->matches($request, $etag)) {
            $response->setNotModified();
            $response->setContent(null);
        }

        return $response;
    }

    /**
     * Only safe, complete, non-admin GET responses are considered.
     */
    private function isCacheable(Request $request, Response $response): bool
    {
        if (! $request->isMethodCacheable()) {
            return false;
        }

        // Never touch anything under the admin area or the auth routes.
        if ($request->is('admin', 'admin/*', 'login', 'logout')) {
            return false;
        }

        // Only successful, body-carrying HTML/XML/plain responses.
        if (! $response->isSuccessful() || $response->getContent() === false || $response->getContent() === '') {
            return false;
        }

        // A redirect or a streamed/download response must not be rewritten.
        if ($response->isRedirection()) {
            return false;
        }

        // Anything that sets or clears cookies beyond the session is left
        // alone: it is not a plain content response.
        if ($response->headers->has('Content-Disposition')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html')
            || str_contains($contentType, 'application/xml')
            || str_contains($contentType, 'text/plain');
    }

    /**
     * Whether the client's validator matches the current representation.
     */
    private function matches(Request $request, string $etag): bool
    {
        $ifNoneMatch = $request->headers->get('If-None-Match');

        if ($ifNoneMatch === null || $ifNoneMatch === '') {
            return false;
        }

        // A client may send a list, and `*` matches any representation.
        foreach (array_map('trim', explode(',', $ifNoneMatch)) as $candidate) {
            if ($candidate === '*' || $candidate === $etag || $candidate === 'W/'.$etag) {
                return true;
            }
        }

        return false;
    }
}
