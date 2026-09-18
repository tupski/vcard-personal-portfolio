<?php

namespace App\Models\Concerns;

/**
 * Shared behaviour for portfolio content that the frontend orders explicitly
 * and that admins can hide without deleting.
 *
 * Every sortable table carries `sort_order` (ascending) and `is_visible`,
 * with a composite `(is_visible, sort_order)` index.
 */
trait SortableAndVisible
{
    /**
     * Public listing order: explicit sort order, then creation order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Only rows the admin has published.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Default state for public queries: visible, in order.
     */
    public function scopeListed($query)
    {
        return $query->visible()->ordered();
    }
}
