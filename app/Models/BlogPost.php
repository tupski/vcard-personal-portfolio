<?php

namespace App\Models;

use App\Models\Concerns\Sluggable;
use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blog card entry. Full-post rendering and tags/search arrive in Phase 8;
 * today the template only renders the card listing.
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
}
