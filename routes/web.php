<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
|
| The original template switched between sections with JavaScript
| (`data-nav-link` / `data-page`). Each section is now a real route so Turbo
| Drive can navigate, browser history works, and every page is directly
| linkable and refreshable.
|
*/

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/resume', [PageController::class, 'resume'])->name('resume');
Route::get('/portfolio', [PageController::class, 'portfolio'])->name('portfolio');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

/*
|--------------------------------------------------------------------------
| Crawler endpoints
|--------------------------------------------------------------------------
|
| Generated from the configured site URL rather than committed as static
| files, so the production domain is never hard-coded and the sitemap can
| grow with the content (Phase 8 adds blog detail URLs here).
|
*/

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));
