<?php

namespace App\Support\Seo;

use App\Models\Media;
use App\Models\Setting;
use App\Support\MediaService;

/**
 * Resolves global SEO defaults from the existing settings table.
 *
 * No new table and no invented configuration: every key is optional and each
 * one falls back to something that already exists (the configured
 * application URL, the Phase 5 media layer, the Phase 2 static assets).
 * A site with no SEO rows at all still emits a complete, valid head.
 */
class SeoDefaults
{
    /**
     * Settings this class reads. Declared once so the admin form, the
     * validator and the reader can never drift apart.
     *
     * @var array<string, array<int, string>>
     */
    public const EDITABLE = [
        'seo.default_description' => ['nullable', 'string', 'max:320'],
        'seo.og_image' => ['nullable', 'string', 'max:2048'],
        'seo.site_url' => ['nullable', 'url', 'max:2048'],
        'seo.robots' => ['nullable', 'string', 'max:64'],
    ];

    public function __construct(
        private readonly MediaService $media,
    ) {}

    /**
     * Site name shown in og:site_name and appended to page titles.
     */
    public function siteName(): string
    {
        return (string) (Setting::get('site.name') ?: config('app.name'));
    }

    /**
     * Configured default meta description, or null.
     *
     * When absent the SeoManager falls back to the profile's own about text,
     * so real content is preferred over invented copy.
     */
    public function description(): ?string
    {
        return $this->setting('seo.default_description');
    }

    /**
     * Base URL for canonical links, sitemap entries and robots references.
     *
     * Always derived from configuration (the `seo.site_url` override, else
     * APP_URL), never from the request host — so a preview or staging domain
     * can never leak into a production canonical. Trailing slashes are
     * stripped so URLs never double up.
     */
    public function baseUrl(): string
    {
        $url = $this->setting('seo.site_url') ?? (string) config('app.url', url('/'));

        return rtrim($url, '/');
    }

    /**
     * Default robots directive for public pages.
     *
     * Local/staging environments are noindex by default so a half-built site
     * is never accidentally indexed; production is index,follow. This is a
     * discoverability guard only — access control stays with the auth
     * middleware and the admin's own noindex,nofollow head.
     */
    public function robots(): string
    {
        return $this->setting('seo.robots')
            ?? (app()->environment('production') ? 'index, follow' : 'noindex, nofollow');
    }

    /**
     * Default social/OG image as a public URL, or null.
     *
     * Accepts an uploaded media path (resolved through the Phase 5 media
     * layer) or a static template asset path. Never concatenates /storage/.
     */
    public function imageUrl(): ?string
    {
        $path = $this->setting('seo.og_image');

        return $path === null ? null : $this->media->url($path);
    }

    /**
     * Dimensions of the default social image, when the media table knows them.
     *
     * Read from the Phase 5 record rather than re-measuring the file, so no
     * extra I/O happens per request.
     *
     * @return array{0: int|null, 1: int|null}
     */
    public function imageDimensions(): array
    {
        $path = $this->setting('seo.og_image');

        if ($path === null) {
            return [null, null];
        }

        $media = Media::query()->where('path', $path)->first();

        return [$media?->width, $media?->height];
    }

    /**
     * Read a setting, treating empty strings as absent.
     */
    private function setting(string $key): ?string
    {
        $value = Setting::get($key);

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }
}
