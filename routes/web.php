<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\UploadStatusController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminUploadController;

// Queue worker vía HTTP (reemplazo de cron)
Route::get('/__run_queue', function () {
    abort_unless(request('key') === config('app.queue_key'), 403);

    // Procesar queue después de enviar la respuesta al cliente
    app()->terminating(function () {
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--timeout' => 600,
            '--memory' => 512,
        ]);
    });

    return response('ok', 200);
});

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

// ✅ Panel de super-administración (sin middleware 'tenant', sin scope de org)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'super_admin'])
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('organizations', OrganizationController::class);
        Route::post('organizations/{organization}/activate', [OrganizationController::class, 'activate'])
            ->name('organizations.activate');
        Route::post('organizations/{organization}/impersonate', [OrganizationController::class, 'impersonate'])
            ->name('organizations.impersonate');
        Route::get('organizations/{organization}/test-db', [OrganizationController::class, 'testDbConnection'])
            ->name('organizations.test-db');
        Route::post('impersonate/stop', function () {
            session()->forget(['impersonating_org', 'impersonating_org_name']);
            return redirect()->route('admin.organizations.index');
        })->name('impersonate.stop');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('uploads', [AdminUploadController::class, 'index'])->name('uploads.index');
    });

// ✅ Rutas protegidas con middleware auth + tenant context
Route::middleware(['auth', 'tenant'])->group(function () {
    
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
    Route::get('uploads/{upload}/progress', [UploadController::class, 'getProgress'])->name('uploads.progress');
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
    Route::post('tags/led-flash', [TagController::class, 'flashLed'])->name('tags.led.flash');
    Route::get('tags/{tagId}', [TagController::class, 'show'])->name('tags.show');
    Route::post('tags/{tagId}/refresh', [TagController::class, 'refresh'])->name('tags.refresh');

});