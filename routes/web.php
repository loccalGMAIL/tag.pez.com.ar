<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\UploadStatusController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TagController;

// ✅ Rutas de autenticación (sin middleware)
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('auth/check', [AuthController::class, 'checkAuth'])->name('auth.check');

// ✅ Redirigir raíz al dashboard si está autenticado
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard.index');
    }
    return redirect()->route('login');
})->name('home');

// ✅ Rutas protegidas con middleware auth
Route::middleware(['auth'])->group(function () {
    
    // ✅ Rutas del Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('dashboard/metrics', [DashboardController::class, 'getMetrics'])->name('dashboard.metrics');
    Route::get('dashboard/activities', [DashboardController::class, 'getActivities'])->name('dashboard.activities');
    Route::get('dashboard/test-connection', [DashboardController::class, 'testConnection'])->name('dashboard.test-connection');
    
    // Rutas de uploads
    Route::resource('uploads', UploadController::class)->only([
        'index', 'create', 'store', 'show'
    ]);
    Route::get('uploads/{upload}/download', [UploadController::class, 'download'])->name('uploads.download');
    Route::get('uploads/{upload}/report', [UploadController::class, 'report'])->name('uploads.report');
    Route::get('uploads/{upload}/status', [UploadStatusController::class, 'show'])
        ->name('api.uploads.status');
        Route::post('uploads/{upload}/refresh-tags', [UploadController::class, 'refreshTags'])->name('uploads.refresh-tags');

    // Rutas de configuración
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Rutas de usuarios
    Route::resource('users', UserController::class)->only([
        'index', 'store', 'show', 'update', 'destroy'
    ]);

    // Rutas de etiquetas
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::get('tags/data', [TagController::class, 'getData'])->name('tags.data');
    Route::post('tags/refresh-multiple', [TagController::class, 'refreshMultiple'])->name('tags.refresh.multiple');
    Route::get('tags/{tagId}', [TagController::class, 'show'])->name('tags.show');
    Route::post('tags/{tagId}/refresh', [TagController::class, 'refresh'])->name('tags.refresh');

});