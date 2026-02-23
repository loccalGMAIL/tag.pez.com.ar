<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'organization_id',
        'key',
        'value',
        'description',
        'type'
    ];

    private static function cacheKey(string $key): string
    {
        $tenantId = app(\App\Services\TenantManager::class)->getTenantId() ?? 'global';
        return "setting_{$tenantId}_{$key}";
    }

    private static function allSettingsCacheKey(): string
    {
        $tenantId = app(\App\Services\TenantManager::class)->getTenantId() ?? 'global';
        return "all_settings_{$tenantId}";
    }

    /**
     * Obtener el valor de una configuración
     */
    public static function get($key, $default = null)
    {
        return Cache::remember(static::cacheKey($key), 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Establecer el valor de una configuración
     */
    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget(static::cacheKey($key));

        return $setting;
    }

    /**
     * Convertir valor según su tipo
     */
    private static function castValue($value, $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'float':
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * Limpiar caché al actualizar
     */
    protected static function booted()
    {
        static::saved(function ($setting) {
            Cache::forget(static::cacheKey($setting->key));
            Cache::forget(static::allSettingsCacheKey());
        });

        static::deleted(function ($setting) {
            Cache::forget(static::cacheKey($setting->key));
            Cache::forget(static::allSettingsCacheKey());
        });
    }

    /**
     * Obtener todas las configuraciones como array
     */
    public static function getAllAsArray()
    {
        return Cache::remember(static::allSettingsCacheKey(), 3600, function () {
            $settings = [];
            $allSettings = self::query()->get();

            foreach ($allSettings as $setting) {
                $settings[$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $settings;
        });
    }
}