<?php

namespace App\Models;

use App\Models\Concerns\Sluggable;
use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blog post: a card in the listing and a full article on its own detail page.
 *
 * Publication state is `is_visible` — the flag Phase 3 already shipped for
 * every sortable content table. There is deliberately no second "published"
 * column and no scheduling/revision workflow.
 *
 * The `content` column holds PLAIN TEXT (Phase 4 shipped it as a textarea for
 * exactly that). It is escaped on output and never treated as HTML; see the
 * detail view for the rendering contract.
 */
class BlogPost extends Model
{
    use Sluggable;
    use SortableAndVisible;

    protected $fillable = [
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'image_path',
        'image_alt',
        'published_at',
        'display_date',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date:Y-m-d',
            'is_visible' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * Posts that are publicly reachable.
     *
     * `is_visible` is the publication flag, so "published" and "listed" are
     * the same state — a post cannot appear in the listing but 404 on its own
     * page, or vice versa.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Resolve a post for a public URL, or fail.
     *
     * Drafts and missing slugs both produce a 404 with no distinguishing
     * detail, so the response never reveals whether an unpublished post
     * exists. Only the slug is used — no numeric id ever appears in a public
     * URL.
     */
    public static function findPublishedBySlug(string $slug): self
    {
        return static::query()
            ->published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * The article body, as plain text.
     *
     * Falls back to the excerpt so a post published without a full body still
     * renders something meaningful instead of an empty page.
     */
    public function body(): string
    {
        $content = trim((string) $this->content);

        return $content !== '' ? $content : trim((string) $this->excerpt);
    }
}
