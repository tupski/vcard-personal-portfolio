<?php

use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectCategoryController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Admin\TestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
|
| Loaded by routes/web.php inside the `admin` prefix, `admin.` name prefix
| and the `auth` middleware group.
|
*/

Route::get('/', DashboardController::class)->name('dashboard');

// Single-row resources.
Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

// Ordered content resources.
Route::resource('services', ServiceController::class)->except('show');
Route::resource('experience', ExperienceController::class)->except('show')->parameters(['experience' => 'entry']);
Route::resource('education', EducationController::class)->except('show')->parameters(['education' => 'entry']);
Route::resource('skills', SkillController::class)->except('show');
Route::resource('clients', ClientController::class)->except('show');
Route::resource('testimonials', TestimonialController::class)->except('show');
Route::resource('project-categories', ProjectCategoryController::class)->except('show');
Route::resource('projects', ProjectController::class)->except('show');
Route::resource('blog-categories', BlogCategoryController::class)->except('show');
Route::resource('blog-posts', BlogPostController::class)->except('show');
Route::resource('social-links', SocialLinkController::class)->except('show');
Route::resource('media', MediaController::class)->except('show')->parameters(['media' => 'medium']);

// Inbox (custom, not a CRUD resource).
Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
Route::get('contact-messages/{message}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
Route::put('contact-messages/{message}', [ContactMessageController::class, 'update'])->name('contact-messages.update');
Route::delete('contact-messages/{message}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
