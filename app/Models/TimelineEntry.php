<?php

namespace App\Models;

use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for the two resume timelines (education / experience), which
 * share the same shape. Laravel's plural guess would land on `education` /
 * `experience`, so the table names stay explicit here.
 */
abstract class TimelineEntry extends Model
{
    use SortableAndVisible;

    protected $table = 'educations';

    protected $fillable = [
        'title',
        'period',
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
