<?php

namespace App\Models;

use App\Models\Concerns\SortableAndVisible;
use Illuminate\Database\Eloquent\Model;

/**
 * Client logos in the About page carousel.
 */
class Client extends Model
{
    use SortableAndVisible;

    protected $fillable = [
        'name',
        'logo_path',
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
