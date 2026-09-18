<?php

namespace App\Models;

use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;

/**
 * Client quotes with avatar, name and a real date for the modal.
 */
class Testimonial extends Model
{
    use SortableAndVisible;

    protected $fillable = [
        'name',
        'avatar_path',
        'testimonial_date',
        'display_date',
        'content',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'testimonial_date' => 'date:Y-m-d',
            'is_visible' => 'boolean',
        ];
    }
}
