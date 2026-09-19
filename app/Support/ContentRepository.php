<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Client;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Testimonial;

/**
 * Database-backed source of every piece of public portfolio content.
 *
 * This is the ONLY place that talks Eloquent for the frontend: Blade
 * components keep calling the PortfolioContent facade with the same array
 * shapes Phase 2 shipped, so presentation stays free of query logic.
 *
 * Registered as a singleton; results are memoised per request so a page
 * render issues each query exactly once (no N+1 across components).
 */
class ContentRepository
{
    /**
     * Per-request result cache, keyed by method.
     *
     * @var array<string, mixed>
     */
    private array $memo = [];

    /**
     * Owner identity rendered in the sidebar.
     *
     * @return array<string, string>
     */
    public function profile(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            $profile = Profile::query()->firstOrFail();

            return [
                'name' => $profile->name,
                'title' => $profile->title,
                'avatar' => $profile->avatar_path,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'phone_href' => $profile->phone_href,
                'birthday' => $profile->birthday->format('F j, Y'),
                'birthday_iso' => $profile->birthday->format('Y-m-d'),
                'location' => $profile->location,
            ];
        });
    }

    /**
     * Primary navigation.
     *
     * Deliberately NOT database-backed: each entry is bound to a named route
     * that must exist in code, so a content row could only ever produce a
     * dead link.
     *
     * @return list<array{label: string, route: string}>
     */
    public function navigation(): array
    {
        return [
            ['label' => 'About', 'route' => 'home'],
            ['label' => 'Resume', 'route' => 'resume'],
            ['label' => 'Portfolio', 'route' => 'portfolio'],
            ['label' => 'Blog', 'route' => 'blog'],
            ['label' => 'Contact', 'route' => 'contact'],
        ];
    }

    /**
     * Sidebar social links, in display order.
     *
     * @return list<array{label: string, icon: string, url: string}>
     */
    public function socialLinks(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return SocialLink::query()
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (SocialLink $link): array => [
                    'label' => $link->label,
                    'icon' => $link->icon,
                    'url' => $link->url,
                ])
                ->all();
        });
    }

    /**
     * "About me" paragraphs, split on blank lines.
     *
     * @return list<string>
     */
    public function about(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            $about = Profile::query()->value('about') ?? '';

            return array_values(array_filter(
                preg_split('/\R{2,}/u', (string) $about) ?: [],
                fn (string $paragraph) => trim($paragraph) !== '',
            ));
        });
    }

    /**
     * Service cards, in display order.
     *
     * @return list<array{title: string, icon: string, icon_alt: string, text: string}>
     */
    public function services(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return Service::query()
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Service $service): array => [
                    'title' => $service->title,
                    'icon' => $service->icon_path,
                    'icon_alt' => $service->icon_alt,
                    'text' => $service->description,
                ])
                ->all();
        });
    }

    /**
     * Testimonials, in display order.
     *
     * @return list<array{name: string, avatar: string, date: string, date_iso: string, text: string}>
     */
    public function testimonials(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return Testimonial::query()
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Testimonial $testimonial): array => [
                    'name' => $testimonial->name,
                    'avatar' => $testimonial->avatar_path,
                    'date' => $testimonial->display_date,
                    'date_iso' => $testimonial->testimonial_date->format('Y-m-d'),
                    'text' => $testimonial->content,
                ])
                ->all();
        });
    }

    /**
     * Client logos, in display order.
     *
     * @return list<string>
     */
    public function clients(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return Client::query()
                ->visible()
                ->ordered()
                ->pluck('logo_path')
                ->all();
        });
    }

    /**
     * Education timeline, in display order.
     *
     * @return list<array{title: string, period: string, text: string}>
     */
    public function education(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return Education::query()
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Education $entry): array => [
                    'title' => $entry->title,
                    'period' => $entry->period,
                    'text' => $entry->description,
                ])
                ->all();
        });
    }

    /**
     * Experience timeline, in display order.
     *
     * @return list<array{title: string, period: string, text: string}>
     */
    public function experience(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return Experience::query()
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Experience $entry): array => [
                    'title' => $entry->title,
                    'period' => $entry->period,
                    'text' => $entry->description,
                ])
                ->all();
        });
    }

    /**
     * Skill bars, in display order.
     *
     * @return list<array{title: string, percent: int}>
     */
    public function skills(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return Skill::query()
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Skill $skill): array => [
                    'title' => $skill->title,
                    'percent' => $skill->percent,
                ])
                ->all();
        });
    }

    /**
     * Visible projects with their category, in display order.
     *
     * @return list<array{title: string, category: string, image: string, alt: string}>
     */
    public function projects(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return Project::query()
                ->visible()
                ->ordered()
                ->with('category')
                ->get()
                ->map(fn (Project $project): array => [
                    'title' => $project->title,
                    'category' => $project->category->name,
                    'image' => $project->image_path,
                    'alt' => $project->image_alt,
                ])
                ->all();
        });
    }

    /**
     * Portfolio filter categories, in display order.
     *
     * "All" is a UI concept — it is not a stored row, but the filter contract
     * established in Phase 2 returns it as the first entry so the component
     * can mark it active by default.
     *
     * @return list<string>
     */
    public function projectCategories(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return [
                'All',
                ...ProjectCategory::query()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('name')
                    ->all(),
            ];
        });
    }

    /**
     * Visible blog posts with their category, in display order.
     *
     * @return list<array{title: string, category: string, date: string, date_iso: string, image: string, alt: string, text: string}>
     */
    public function posts(): array
    {
        return $this->memo(__FUNCTION__, function (): array {
            return BlogPost::query()
                ->visible()
                ->ordered()
                ->with('category')
                ->get()
                ->map(fn (BlogPost $post): array => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'url' => route('blog.show', $post->slug, absolute: false),
                    'category' => $post->category->name,
                    'date' => $post->display_date,
                    'date_iso' => $post->published_at->format('Y-m-d'),
                    'image' => $post->image_path,
                    'alt' => $post->image_alt,
                    'text' => $post->excerpt,
                ])
                ->all();
        });
    }

    /**
     * One published post, shaped for the detail page.
     *
     * Resolved by slug through the model scope so drafts and unknown slugs are
     * indistinguishable (both 404). The category comes along on the same
     * query, and the related posts below need no further lookups per post.
     *
     * @return array<string, mixed>
     */
    public function post(string $slug): array
    {
        return $this->memo('post:'.$slug, function () use ($slug): array {
            $post = BlogPost::findPublishedBySlug($slug);

            return [
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category->name,
                'category_slug' => $post->category->slug,
                'date' => $post->display_date,
                'date_iso' => $post->published_at->format('Y-m-d'),
                'date_human' => $post->published_at->format('F j, Y'),
                'created_iso' => $post->created_at?->toDateString(),
                'updated_iso' => $post->updated_at?->toDateString(),
                'image' => $post->image_path,
                'alt' => $post->image_alt,
                'excerpt' => $post->excerpt,
                'body' => $post->body(),
            ];
        });
    }

    /**
     * A small set of other published posts in the same category.
     *
     * Deterministic (explicit sort order, then id), excludes the current post,
     * and capped so the detail page stays one cheap query. Empty when the
     * category has nothing else — no fallback to unrelated content.
     *
     * @return list<array<string, string>>
     */
    public function relatedPosts(string $slug, int $limit = 3): array
    {
        return $this->memo('related:'.$slug.':'.$limit, function () use ($slug, $limit): array {
            $post = BlogPost::findPublishedBySlug($slug);

            return BlogPost::query()
                ->published()
                ->with('category')
                ->where('blog_category_id', $post->blog_category_id)
                ->whereKeyNot($post->getKey())
                ->ordered()
                ->limit($limit)
                ->get()
                ->map(fn (BlogPost $related): array => [
                    'title' => $related->title,
                    'slug' => $related->slug,
                    'url' => route('blog.show', $related->slug, absolute: false),
                    'category' => $related->category->name,
                    'date' => $related->display_date,
                    'date_iso' => $related->published_at->format('Y-m-d'),
                    'image' => $related->image_path,
                    'alt' => $related->image_alt,
                    'text' => $related->excerpt,
                ])
                ->all();
        });
    }

    /**
     * Contact page map embed, from settings.
     */
    public function mapEmbedUrl(): string
    {
        return (string) Setting::get('contact.map_embed_url', '');
    }

    /**
     * Resolve a content image path to a public URL through the media layer.
     * Static template paths (assets/…) pass through unchanged, so the Phase 2
     * output is byte-identical for untouched content.
     */
    public function mediaUrl(?string $path): string
    {
        return app(MediaService::class)->url((string) $path);
    }

    /**
     * Thumb variant URL for a content image path.
     */
    public function mediaThumbUrl(?string $path): string
    {
        return app(MediaService::class)->thumbUrl((string) $path);
    }

    /**
     * Memoise a repository result for the current request.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function memo(string $key, callable $callback): mixed
    {
        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $callback();
        }

        return $this->memo[$key];
    }
}
