<?php

namespace App\Models;

use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;

/**
 * "What i'm doing" cards on the About page.
 */
class Service extends Model
{
    use SortableAndVisible;

    protected $fillable = [
        'title',
        'icon_path',
        'icon_alt',
        'description',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }
}
