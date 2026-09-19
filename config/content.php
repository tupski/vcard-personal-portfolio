<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public content cache
    |--------------------------------------------------------------------------
    |
    | Caches content that is identical for every visitor (profile, services,
    | skills, projects, blog posts, settings) and is invalidated the moment an
    | admin edits the underlying record.
    |
    | Uses whatever cache store the application is configured with — the
    | `database` store by default, `file` on shared hosting, Redis if a
    | deployment provides one. No driver is required.
    |
    | Set CONTENT_CACHE=false to disable caching entirely (useful while
    | debugging, or on a host where no cache store is writable). The site keeps
    | working; it just does more work per request.
    |
    */

    'cache' => env('CONTENT_CACHE', true),
];
