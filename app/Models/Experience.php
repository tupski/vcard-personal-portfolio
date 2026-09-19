<?php

namespace App\Models;

/**
 * Work history on the resume timeline (table: experiences).
 */
class Experience extends TimelineEntry
{
    protected $table = 'experiences';

    protected $fillable = [
        'title',
        'company',
        'period',
        'description',
        'sort_order',
        'is_visible',
    ];
}
