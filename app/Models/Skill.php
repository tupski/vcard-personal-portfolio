<?php

namespace App\Models;

use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;

/**
 * Skill progress bars on the resume page.
 */
class Skill extends Model
{
    use SortableAndVisible;

    protected $fillable = [
        'title',
        'percent',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'percent' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
