<?php

namespace App\Support\Seo;

use App\Models\BlogPost;
use App\Support\ContentRepository;
use Illuminate\Support\Carbon;

/**
 * Builds the XML sitemap from indexable public URLs only.
 *
 * URL sources are providers: today that is the static public route list, and
 * Phase 8 can add published blog detail URLs by extending {@see self::urls()}
 * without touching the XML assembly or the route.
 *
 * Guarantees: absolute URLs from the configured site URL, no admin/login or
 * authenticated pages, no query strings, no duplicates, deterministic
 * ordering, and `lastmod` only where real data provides it (never fabricated).
 */
class SitemapService
{
    public function __construct(
        private readonly SeoManager $seo,
        private readonly ContentRepository $content,
    ) {}

    /**
     * Build the sitemap document.
     */
    public function xml(): string
    {
        $urls = $this->urls();

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $entry) {
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

        // Published blog detail URLs. `lastmod` is only attached when the post
        // was genuinely edited after publication — the timestamp is read from
        // the row, never derived from "now".
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

    /**
     * Newest real content timestamp, or null when nothing is dated yet.
     */
    public function lastModified(): ?string
    {
        $timestamps = [];

        try {
            foreach ($this->content->posts() as $post) {
                if (($post['date_iso'] ?? '') !== '') {
                    $timestamps[] = $post['date_iso'];
                }
            }
        } catch (\Throwable) {
            return null;
        }

        if ($timestamps === []) {
            return null;
        }

        rsort($timestamps);

        return Carbon::parse($timestamps[0])->toDateString();
    }
}
