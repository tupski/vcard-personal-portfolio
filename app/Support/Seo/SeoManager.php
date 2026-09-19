<?php

namespace App\Support\Seo;

use App\Support\ContentRepository;
use App\Support\MediaService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Builds {@see SeoData} for every public page.
 *
 * This is the single place where page metadata is defined. Controllers ask
 * for a page by route name; Blade only renders the resulting value object, so
 * no template contains SEO conditionals and adding a route later means adding
 * one entry to {@see self::PAGES}.
 *
 * Copy is the Phase 2 template's own wording (the shipped contract), enriched
 * with real profile data where it exists. Nothing is invented, and nothing is
 * duplicated between the view and the metadata.
 */
class SeoManager
{
    /**
     * Per-route metadata definitions.
     *
     * `title` and `description` are the Phase 2 page copy; `og_image` is an
     * optional page-specific social image (a media path or static asset path).
     *
     * @var array<string, array{title: string, description: string, og_image?: string}>
     */
    private const PAGES = [
        'home' => [
            'title' => 'About',
            'description' => 'About Richard Hanrick — Creative Director and UI/UX Designer working in web development and print media.',
        ],
        'resume' => [
            'title' => 'Resume',
            'description' => 'Education, experience and skills of Richard Hanrick.',
        ],
        'portfolio' => [
            'title' => 'Portfolio',
            'description' => 'Selected projects by Richard Hanrick across web design, applications and web development.',
        ],
        'blog' => [
            'title' => 'Blog',
            'description' => 'Articles on design, typography and front-end craft by Richard Hanrick.',
        ],
        'contact' => [
            'title' => 'Contact',
            'description' => 'Get in touch with Richard Hanrick.',
        ],
    ];

    public function __construct(
        private readonly SeoDefaults $defaults,
        private readonly ContentRepository $content,
        private readonly MediaService $media,
    ) {}

    /**
     * Every page this manager knows how to describe, in deterministic order.
     *
     * The sitemap and the canonical builder both read this list, so a new
     * public route can never be forgotten in one place but not the other.
     *
     * @return list<string>
     */
    public static function pageNames(): array
    {
        return array_keys(self::PAGES);
    }

    /**
     * Metadata for one public page.
     *
     * Fallback chain for the title: page title → site name → configured app
     * name. For the description: page copy → configured default → none. For
     * the social image: page image → configured site image → profile avatar.
     */
    public function forPage(string $name): SeoData
    {
        $page = self::PAGES[$name] ?? ['title' => $this->defaults->siteName(), 'description' => ''];

        $siteName = $this->defaults->siteName();
        $title = trim((string) ($page['title'] ?? '')) ?: $siteName;

        $description = trim((string) ($page['description'] ?? ''))
            ?: $this->defaults->description()
            ?: $this->profileDescription();

        [$image, $width, $height] = $this->resolveImage($page['og_image'] ?? null);

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $this->canonical($name),
            robots: $this->defaults->robots(),
            ogImage: $image,
            ogImageWidth: $width,
            ogImageHeight: $height,
            ogImageAlt: $image !== null ? $title : null,
            ogType: 'website',
            ogSiteName: $siteName,
            schemas: $this->schemas($name, $title, $description),
            locale: str_replace('_', '-', app()->getLocale()),
        );
    }

    /**
     * Metadata for a published blog post's detail page.
     *
     * Built from the stored post rather than page copy: title, excerpt, image
     * and publication date are the author's own content. `og:type` is
     * `article` (the only page on the site that is one) and the JSON-LD adds a
     * BlogPosting graph plus a Home -> Blog -> Post breadcrumb trail.
     *
     * @param  array<string, mixed>  $post  Repository post shape.
     */
    public function forPost(array $post): SeoData
    {
        $siteName = $this->defaults->siteName();
        $title = trim((string) ($post['title'] ?? '')) ?: $siteName;
        $description = trim((string) ($post['excerpt'] ?? '')) ?: $this->defaults->description();

        $canonical = $this->canonicalForPath('blog/'.($post['slug'] ?? ''));

        [$image, $width, $height] = $this->resolveImage($post['image'] ?? null);

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $this->defaults->robots(),
            ogImage: $image,
            ogImageWidth: $width,
            ogImageHeight: $height,
            ogImageAlt: $image !== null ? (trim((string) ($post['alt'] ?? '')) ?: $title) : null,
            ogType: 'article',
            ogSiteName: $siteName,
            schemas: [
                $this->blogPostingSchema($post, $title, $description, $canonical, $image),
                $this->breadcrumbSchema([
                    [$siteName, $this->defaults->baseUrl().'/'],
                    [__('Blog'), $this->canonical('blog')],
                    [$title, $canonical],
                ]),
            ],
            locale: str_replace('_', '-', app()->getLocale()),
        );
    }

    /**
     * Absolute canonical URL for a literal public path.
     *
     * Used by content types whose URL is data-driven (blog slugs) rather than
     * a named route, so the canonical is still built from the configured base
     * URL and never from the request host.
     */
    public function canonicalForPath(string $path): string
    {
        $path = trim($path, '/');

        return $path === '' ? $this->defaults->baseUrl().'/' : $this->defaults->baseUrl().'/'.$path;
    }

    /**
     * BlogPosting JSON-LD for a real, published post.
     *
     * Every field comes from stored data: no fabricated dates (dateModified is
     * emitted only when the post was actually edited after publication) and no
     * invented author — the author is the profile row.
     *
     * @param  array<string, mixed>  $post
     * @return array<string, mixed>
     */
    private function blogPostingSchema(
        array $post,
        string $title,
        ?string $description,
        string $canonical,
        ?string $image,
    ): array {
        $published = trim((string) ($post['date_iso'] ?? ''));
        $created = trim((string) ($post['created_iso'] ?? ''));
        $updated = trim((string) ($post['updated_iso'] ?? ''));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $title,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonical,
            ],
            'url' => $canonical,
            'author' => $this->authorSchema(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->defaults->siteName(),
                'url' => $this->defaults->baseUrl().'/',
            ],
        ];

        if ($description !== null && $description !== '') {
            $schema['description'] = $description;
        }

        if ($published !== '') {
            $schema['datePublished'] = $published;
        }

        // Only when the row was genuinely edited after it was created AND
        // after it was published. A freshly created post has
        // updated_at === created_at, so seeding a post with a historical
        // publication date does not fabricate a "modified today" claim.
        if (
            $updated !== '' && $created !== '' && $published !== ''
            && $updated > $created
            && $updated > $published
        ) {
            $schema['dateModified'] = $updated;
        }

        if ($image !== null) {
            $schema['image'] = $image;
        }

        if (($post['category'] ?? '') !== '') {
            $schema['articleSection'] = $post['category'];
        }

        return $schema;
    }

    /**
     * Author node for article schemas, from the profile row.
     *
     * @return array<string, mixed>
     */
    private function authorSchema(): array
    {
        try {
            $profile = $this->content->profile();
        } catch (\Throwable) {
            return ['@type' => 'Organization', 'name' => $this->defaults->siteName()];
        }

        $author = [
            '@type' => 'Person',
            'name' => $profile['name'] ?? null,
            'url' => $this->canonical('home'),
        ];

        if (($profile['avatar'] ?? '') !== '') {
            $author['image'] = $this->media->url($profile['avatar']);
        }

        return array_filter($author, static fn (mixed $v): bool => $v !== null && $v !== '');
    }

    /**
     * BreadcrumbList from an ordered list of [name, url] pairs.
     *
     * @param  list<array{0: string, 1: string}>  $trail
     * @return array<string, mixed>
     */
    private function breadcrumbSchema(array $trail): array
    {
        $items = [];

        foreach (array_values($trail) as $i => [$name, $url]) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $name,
                'item' => $url,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Absolute canonical URL for a named public route.
     *
     * Built from the configured base URL (never the request host), so a
     * preview or staging host can never leak into production canonicals.
     * Query strings are dropped and the trailing slash is normalised, so
     * `/blog?page=2` and `/blog/` both canonicalise to `/blog`.
     */
    public function canonical(string $routeName): string
    {
        $base = $this->defaults->baseUrl();

        if ($routeName === 'home') {
            return $base.'/';
        }

        // An unknown route name — or one that needs parameters we do not have
        // (e.g. a detail route reached without a slug) — must never throw: the
        // layout's fallback path can be reached from an error page, and a
        // broken canonical is worse than a generic one.
        try {
            $path = Route::has($routeName)
                ? trim(route($routeName, absolute: false), '/')
                : trim($routeName, '/');
        } catch (\Throwable) {
            $path = '';
        }

        return $path === '' ? $base.'/' : $base.'/'.$path;
    }

    /**
     * Resolve the social image and its dimensions.
     *
     * @return array{0: string|null, 1: int|null, 2: int|null}
     */
    private function resolveImage(?string $pageImage): array
    {
        if ($pageImage !== null && $pageImage !== '') {
            return [$this->media->url($pageImage), null, null];
        }

        $default = $this->defaults->imageUrl();

        if ($default !== null) {
            [$width, $height] = $this->defaults->imageDimensions();

            return [$default, $width, $height];
        }

        // Last resort: the real profile avatar. Actual content, not a
        // generated variant — Phase 5's rule against SEO-only variants holds.
        try {
            $avatar = $this->content->profile()['avatar'] ?? null;
        } catch (\Throwable) {
            return [null, null, null];
        }

        if (is_string($avatar) && $avatar !== '') {
            return [$this->media->url($avatar), null, null];
        }

        return [null, null, null];
    }

    /**
     * JSON-LD graphs that the site's real content supports.
     *
     * WebSite is always emitted (it describes the site itself). Person is
     * emitted from the profile row, with `sameAs` limited to social links
     * that point at a real URL. BreadcrumbList is emitted for pages that
     * genuinely sit one level under the site root. BlogPosting is deliberately
     * absent: no public blog detail route exists yet.
     *
     * @return list<array<string, mixed>>
     */
    private function schemas(string $name, string $title, ?string $description): array
    {
        $siteName = $this->defaults->siteName();
        $base = $this->defaults->baseUrl();

        $schemas = [[
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $base.'/',
        ]];

        if ($description !== null && $description !== '') {
            $schemas[0]['description'] = $description;
        }

        $person = $this->personSchema();

        if ($person !== null) {
            $schemas[] = $person;
        }

        if ($name !== 'home') {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => $siteName,
                        'item' => $base.'/',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $title,
                        'item' => $this->canonical($name),
                    ],
                ],
            ];
        }

        return $schemas;
    }

    /**
     * First paragraph of the profile's about text, trimmed to meta length.
     *
     * Real content rather than invented copy, used only when neither the page
     * nor the configured default supplies a description.
     */
    private function profileDescription(): ?string
    {
        try {
            $about = $this->content->about();
        } catch (\Throwable) {
            return null;
        }

        $text = trim((string) ($about[0] ?? ''));

        if ($text === '') {
            return null;
        }

        return Str::limit($text, 160);
    }

    /**
     * Person schema from the profile row, or null when there is no profile.
     *
     * @return array<string, mixed>|null
     */
    private function personSchema(): ?array
    {
        try {
            $profile = $this->content->profile();
        } catch (\Throwable) {
            return null;
        }

        $person = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $profile['name'] ?? null,
            'jobTitle' => $profile['title'] ?? null,
        ];

        $sameAs = array_values(array_filter(
            array_map(
                static fn (array $link): string => (string) ($link['url'] ?? ''),
                $this->content->socialLinks(),
            ),
            static fn (string $url): bool => $url !== '' && $url !== '#' && filter_var($url, FILTER_VALIDATE_URL) !== false,
        ));

        if ($sameAs !== []) {
            $person['sameAs'] = $sameAs;
        }

        $url = $this->canonical('home');

        $person['url'] = $url;

        if (($profile['avatar'] ?? '') !== '') {
            $person['image'] = $this->media->url($profile['avatar']);
        }

        if (($profile['email'] ?? '') !== '') {
            $person['email'] = $profile['email'];
        }

        if (($profile['phone'] ?? '') !== '') {
            $person['telephone'] = $profile['phone'];
        }

        if (($profile['location'] ?? '') !== '') {
            $person['address'] = $profile['location'];
        }

        return array_filter($person, static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
