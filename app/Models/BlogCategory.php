<?php

namespace App\Models;

use App\Models\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Blog taxonomy. Posts currently render as cards only; the category name is
 * shown in each card's meta row.
 */
class BlogCategory extends Model
{
    use Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    protected function slugSource(): string
    {
        return 'name';
    }

    /**
     * Published posts in this category, oldest first as the template shows.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class)->listed();
    }
}
