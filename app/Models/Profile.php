<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row owner identity rendered by the sidebar and about section.
 */
class Profile extends Model
{
    protected $fillable = [
        'name',
        'title',
        'avatar_path',
        'email',
        'phone',
        'phone_href',
        'birthday',
        'location',
        'about',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date:Y-m-d',
        ];
    }

    /**
     * The one profile row, or null before seeding.
     */
    public static function current(): ?self
    {
        return static::query()->first();
    }
}
