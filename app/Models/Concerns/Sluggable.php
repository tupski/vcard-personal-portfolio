<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Fills the `slug` column from a human-readable source column on creating.
 *
 * Slug source defaults to `title`; taxonomy models override `slugSource()`
 * to use `name`. Collisions get a numeric suffix so the unique index can
 * never be violated.
 */
trait Sluggable
{
    protected static function bootSluggable(): void
    {
        static::creating(function ($model) {
            if (! blank($model->slug)) {
                return;
            }

            $base = Str::slug((string) $model->{$model->slugSource()});
            $slug = $base;
            $suffix = 2;

            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }

            $model->slug = $slug;
        });
    }

    protected function slugSource(): string
    {
        return 'title';
    }
}
