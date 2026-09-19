<?php

namespace App\Support\Seo;

/**
 * Immutable snapshot of one page's SEO metadata.
 *
 * Built by {@see SeoManager} from route/controller/page input so no Blade
 * template ever assembles metadata conditionally. Everything here is already
 * resolved: URLs are absolute, the image is a public URL, and the JSON-LD
 * graphs are plain PHP arrays ready for safe encoding.
 */
final readonly class SeoData
{
    /**
     * @param  list<string>  $keywords
     * @param  list<array<string, mixed>>  $schemas  JSON-LD graphs.
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public string $robots = 'index, follow',
        public ?string $ogImage = null,
        public ?int $ogImageWidth = null,
        public ?int $ogImageHeight = null,
        public ?string $ogImageAlt = null,
        public string $ogType = 'website',
        public ?string $ogSiteName = null,
        public array $keywords = [],
        public array $schemas = [],
        public ?string $locale = null,
    ) {}

    /**
     * Twitter/X card type: a large image card when we have an image.
     */
    public function twitterCard(): string
    {
        return $this->ogImage !== null ? 'summary_large_image' : 'summary';
    }

    /**
     * Merge extra JSON-LD nodes into this snapshot.
     *
     * @param  array<int, array<string, mixed>>  $schemas
     */
    public function withSchemas(array $schemas): self
    {
        return new self(
            title: $this->title,
            description: $this->description,
            canonical: $this->canonical,
            robots: $this->robots,
            ogImage: $this->ogImage,
            ogImageWidth: $this->ogImageWidth,
            ogImageHeight: $this->ogImageHeight,
            ogImageAlt: $this->ogImageAlt,
            ogType: $this->ogType,
            ogSiteName: $this->ogSiteName,
            keywords: $this->keywords,
            schemas: [...$this->schemas, ...$schemas],
            locale: $this->locale,
        );
    }
}
