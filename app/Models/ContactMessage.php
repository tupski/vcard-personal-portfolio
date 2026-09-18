<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Inbox entry created by the public contact form (wired up in Phase 7).
 *
 * The table ships in Phase 3 because PLAN.md lists it under "Phase 3 —
 * Database"; the UI lives with the rest of the admin in Phase 4.
 */
class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }
}
