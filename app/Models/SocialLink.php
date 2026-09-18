<?php

namespace App\Models;

use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;

/**
 * Sidebar social profile links (Facebook, Twitter, Instagram, ...).
 */
class SocialLink extends Model
{
    use SortableAndVisible;

    protected $fillable = [
        'label',
        'icon',
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
}
