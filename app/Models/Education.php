<?php

namespace App\Models;

/**
 * Education history on the resume timeline (table: educations).
 */
class Education extends TimelineEntry
{
    protected $table = 'educations';

    protected $fillable = [
        'title',
        'institution',
        'period',
        'description',
        'sort_order',
        'is_visible',
    ];
}
