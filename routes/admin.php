<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
|
| Loaded by routes/web.php inside the `admin` prefix, `admin.` name prefix
| and the `auth` middleware group. CRUD modules are appended here in
| Phase 4 onwards.
|
*/

Route::get('/', DashboardController::class)->name('dashboard');
