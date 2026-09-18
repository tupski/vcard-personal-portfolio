<?php

namespace App\Models;

use App\Models\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Portfolio filter category (All / Web design / Applications / ...).
 *
 * "All" is a UI concept, not a stored row — categories only exist for real
 * filter values.
 */
class ProjectCategory extends Model
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
     * Published projects in this category, in display order.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->listed();
    }
}
