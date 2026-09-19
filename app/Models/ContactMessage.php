<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Inbox entry created by the public contact form.
 *
 * The row is the source of truth: it is persisted before any notification is
 * attempted, and a mail failure never removes it. Email is a secondary
 * notification mechanism only.
 */
class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * Messages the admin has not read yet.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Flag the message as read.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->forceFill(['is_read' => true])->save();
        }
    }

    /**
     * Subject for the admin inbox, falling back to the message body for rows
     * created before the subject column existed (or by other code paths).
     */
    public function displaySubject(): string
    {
        $subject = trim((string) $this->subject);

        if ($subject !== '') {
            return $subject;
        }

        return str($this->message)->squish()->limit(60)->value();
    }
}
