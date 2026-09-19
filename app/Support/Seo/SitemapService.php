<?php

namespace App\Support\Seo;

use App\Models\BlogPost;
use App\Support\ContentCache;

/**
 * Builds the XML sitemap from indexable public URLs only.
 *
 * URL sources are providers: the static public route list, and published blog
 * detail URLs. Phase 10+ can add another content type by adding one provider
 * method, without touching the XML assembly or the route.
 *
 * Guarantees: absolute URLs from the configured site URL, no admin/login or
 * authenticated pages, no query strings, no duplicates, deterministic
 * ordering, and `lastmod` only where real data provides it (never fabricated).
 *
 * The generated document is cached under the public content namespace, so it
 * is rebuilt only when content changes — a crawler hammering /sitemap.xml does
 * not re-run the queries behind it.
 */
class SitemapService
{
    public function __construct(
        private readonly SeoManager $seo,
        private readonly ContentCache $cache,
    ) {}

    /**
     * Build the sitemap document.
     */
    public function xml(): string
    {
        return $this->cache->remember('sitemap.xml', fn (): string => $this->build());
    }

    /**
     * Assemble the XML.
     */
    private function build(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->urls() as $entry) {
            $lines[] = '    <url>';
            $lines[] = '        <loc>'.e($entry['loc']).'</loc>';

            if (($entry['lastmod'] ?? null) !== null) {
                $lines[] = '        <lastmod>'.$entry['lastmod'].'</lastmod>';
            }

            $lines[] = '    </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    /**
     * Indexable URLs, deterministically ordered, de-duplicated.
     *
     * @return list<array{loc: string, lastmod?: string}>
     */
    public function urls(): array
    {
        $entries = [];

        foreach (SeoManager::pageNames() as $name) {
            $entries[] = ['loc' => $this->seo->canonical($name)];
        }

        foreach ($this->blogEntries() as $entry) {
            $entries[] = $entry;
        }

        // Deterministic order (loc ascending) and a hard de-dupe guard.
        usort($entries, static fn (array $a, array $b): int => strcmp($a['loc'], $b['loc']));

        $seen = [];
        $unique = [];

        foreach ($entries as $entry) {
            if (isset($seen[$entry['loc']])) {
                continue;
            }

            $seen[$entry['loc']] = true;
            $unique[] = $entry;
        }

        return $unique;
    }

    /**
     * Published blog detail URLs.
     *
     * @return list<array{loc: string, lastmod?: string}>
     */
    private function blogEntries(): array
    {
        try {
            $posts = BlogPost::query()
                ->published()
                ->ordered()
                ->get(['slug', 'published_at', 'created_at', 'updated_at']);
        } catch (\Throwable) {
            return [];
        }

        $entries = [];

        foreach ($posts as $post) {
            $slug = trim((string) $post->slug);

            if ($slug === '') {
                continue;
            }

            $entry = ['loc' => $this->seo->canonicalForPath('blog/'.$slug)];

            $published = $post->published_at?->toDateString();
            $created = $post->created_at?->toDateString();
            $updated = $post->updated_at?->toDateString();

            // Only a genuine post-publication edit becomes lastmod; a freshly
            // seeded row must not claim it changed today.
            if (
                $published !== null && $created !== null && $updated !== null
                && $updated > $created
                && $updated > $published
            ) {
                $entry['lastmod'] = $updated;
            }

            $entries[] = $entry;
        }

        return $entries;
    }
}
