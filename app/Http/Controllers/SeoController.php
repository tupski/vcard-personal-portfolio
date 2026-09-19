<?php

namespace App\Http\Controllers;

use App\Support\Seo\SeoDefaults;
use App\Support\Seo\SitemapService;
use Illuminate\Http\Response;

/**
 * Crawler-facing endpoints.
 *
 * Both documents are generated from the configured site URL, so the
 * production domain is never hard-coded and the sitemap reference inside
 * robots.txt always matches the sitemap that is actually served.
 */
class SeoController extends Controller
{
    public function __construct(
        private readonly SeoDefaults $defaults,
        private readonly SitemapService $sitemap,
    ) {}

    /**
     * /robots.txt
     *
     * Disallowing /admin here is a courtesy to well-behaved crawlers; it is
     * not the security boundary — the auth middleware is. Non-production
     * environments are disallowed entirely so a staging copy is never indexed.
     */
    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (! app()->environment('production')) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /login';
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$this->defaults->baseUrl().'/sitemap.xml';

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * /sitemap.xml
     */
    public function sitemap(): Response
    {
        return response($this->sitemap->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
