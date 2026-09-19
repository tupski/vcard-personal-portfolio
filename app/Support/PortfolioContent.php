<?php

namespace App\Support;

/**
 * Facade for public portfolio content.
 *
 * Phase 2 introduced this class as the static source of truth; since Phase 3
 * the content lives in the database and this class is a thin delegation to
 * the ContentRepository singleton. The static API and array shapes are
 * unchanged, so Blade components never learn where content comes from.
 */
class PortfolioContent
{
    /**
     * Owner identity rendered in the sidebar.
     *
     * @return array<string, string>
     */
    public static function profile(): array
    {
        return app(ContentRepository::class)->profile();
    }

    /**
     * Primary navigation.
     *
     * @return list<array{label: string, route: string}>
     */
    public static function navigation(): array
    {
        return app(ContentRepository::class)->navigation();
    }

    /**
     * Sidebar social links, in display order.
     *
     * @return list<array{label: string, icon: string, url: string}>
     */
    public static function socialLinks(): array
    {
        return app(ContentRepository::class)->socialLinks();
    }

    /**
     * "About me" paragraphs.
     *
     * @return list<string>
     */
    public static function about(): array
    {
        return app(ContentRepository::class)->about();
    }

    /**
     * Service cards, in display order.
     *
     * @return list<array{title: string, icon: string, icon_alt: string, text: string}>
     */
    public static function services(): array
    {
        return app(ContentRepository::class)->services();
    }

    /**
     * Testimonials, in display order.
     *
     * @return list<array{name: string, avatar: string, date: string, date_iso: string, text: string}>
     */
    public static function testimonials(): array
    {
        return app(ContentRepository::class)->testimonials();
    }

    /**
     * Client logos, in display order.
     *
     * @return list<string>
     */
    public static function clients(): array
    {
        return app(ContentRepository::class)->clients();
    }

    /**
     * Education timeline, in display order.
     *
     * @return list<array{title: string, period: string, text: string}>
     */
    public static function education(): array
    {
        return app(ContentRepository::class)->education();
    }

    /**
     * Experience timeline, in display order.
     *
     * @return list<array{title: string, period: string, text: string}>
     */
    public static function experience(): array
    {
        return app(ContentRepository::class)->experience();
    }

    /**
     * Skill bars, in display order.
     *
     * @return list<array{title: string, percent: int}>
     */
    public static function skills(): array
    {
        return app(ContentRepository::class)->skills();
    }

    /**
     * Visible projects with their category, in display order.
     *
     * @return list<array{title: string, category: string, image: string, alt: string}>
     */
    public static function projects(): array
    {
        return app(ContentRepository::class)->projects();
    }

    /**
     * Portfolio filter categories, in display order.
     *
     * @return list<string>
     */
    public static function projectCategories(): array
    {
        return app(ContentRepository::class)->projectCategories();
    }

    /**
     * Visible blog posts with their category, in display order.
     *
     * @return list<array{title: string, category: string, date: string, date_iso: string, image: string, alt: string, text: string}>
     */
    public static function posts(): array
    {
        return app(ContentRepository::class)->posts();
    }

    /**
     * One published blog post, shaped for the detail page.
     *
     * @return array<string, mixed>
     */
    public static function post(string $slug): array
    {
        return app(ContentRepository::class)->post($slug);
    }

    /**
     * Other published posts in the same category, for the detail page.
     *
     * @return list<array<string, string>>
     */
    public static function relatedPosts(string $slug, int $limit = 3): array
    {
        return app(ContentRepository::class)->relatedPosts($slug, $limit);
    }

    /**
     * Resolve a content image path to a public URL through the media layer.
     */
    public static function mediaUrl(?string $path): string
    {
        return app(ContentRepository::class)->mediaUrl($path);
    }

    /**
     * Thumb variant URL for a content image path.
     */
    public static function mediaThumbUrl(?string $path): string
    {
        return app(ContentRepository::class)->mediaThumbUrl($path);
    }

    /**
     * Intrinsic dimensions for a content image path.
     *
     * @return array{0: int|null, 1: int|null}
     */
    public static function mediaDimensions(?string $path): array
    {
        return app(ContentRepository::class)->mediaDimensions($path);
    }

    /**
     * Responsive srcset for a content image path, or null.
     */
    public static function mediaSrcset(?string $path): ?string
    {
        return app(ContentRepository::class)->mediaSrcset($path);
    }

    /**
     * Contact page map embed.
     */
    public static function mapEmbedUrl(): string
    {
        return app(ContentRepository::class)->mapEmbedUrl();
    }
}
