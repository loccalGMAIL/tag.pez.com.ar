<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // TenantManager: singleton para mantener el tenant activo durante el request/job
        $this->app->singleton(\App\Services\TenantManager::class, function ($app) {
            return new \App\Services\TenantManager();
        });

        // ERetailService: bind (no singleton) para que se instancie fresco por cada resolución,
        // tomando las credenciales del tenant activo en ese momento
        $this->app->bind(\App\Services\ERetailService::class, function ($app) {
            return new \App\Services\ERetailService();
        });

        // ExcelProcessorService: bind (depende de ERetailService)
        $this->app->bind(\App\Services\ExcelProcessorService::class, function ($app) {
            return new \App\Services\ExcelProcessorService(
                $app->make(\App\Services\ERetailService::class)
            );
        });

        // ProductService - Gestión de productos maestros
        $this->app->singleton(\App\Services\ProductService::class, function ($app) {
            return new \App\Services\ProductService();
        });

        // ProductVariantService - Gestión de variantes
        $this->app->singleton(\App\Services\ProductVariantService::class, function ($app) {
            return new \App\Services\ProductVariantService();
        });

        // UploadLogService - Gestión de logs de procesamiento
        $this->app->singleton(\App\Services\UploadLogService::class, function ($app) {
            return new \App\Services\UploadLogService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}