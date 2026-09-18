<?php

namespace App\Models;

use App\Models\Concerns\Sluggable;
use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Portfolio card. The slug is the stable public identifier used by the
 * future admin CRUD and deep links.
 */
class Project extends Model
{
    use Sluggable;
    use SortableAndVisible;

    protected $fillable = [
        'project_category_id',
        'title',
        'slug',
        'image_path',
        'image_alt',
        'url',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }
}
