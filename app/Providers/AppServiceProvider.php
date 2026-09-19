<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\ContentRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Database-backed site name with a config fallback.
     */
    public static function siteName(): string
    {
        try {
            return (string) Setting::get('site.name', config('app.name'));
        } catch (QueryException) {
            return (string) config('app.name');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Single instance per request: the repository memoises its queries so
        // the many Blade components that consume it never duplicate work.
        $this->app->singleton(ContentRepository::class);

        // Intervention Image v4 with the GD driver (bundled with PHP, no
        // Imagick requirement — keeps shared hosting workable).
        $this->app->singleton(ImageManager::class, fn () => new ImageManager(new GdDriver));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Site name is database-backed with the configured app name as
        // fallback, so the seeded DB and .env agree out of the box. Requests
        // that run before the settings table exists (fresh app boot) fall
        // back silently instead of erroring.
        View::composer('*', function ($view): void {
            $view->with('siteName', self::siteName());
        });
    }
}
