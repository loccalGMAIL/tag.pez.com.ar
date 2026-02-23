<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Registro general.
     */
    public static function log(
        string $type,
        string $level,
        string $event,
        string $description,
        array $properties = [],
        ?Model $subject = null,
        ?int $organizationId = null,
        ?int $userId = null
    ): void {
        try {
            // Resolver org desde TenantManager si no se provee
            if ($organizationId === null) {
                $tenantManager = app(TenantManager::class);
                $organizationId = $tenantManager->getTenantId();
            }

            // Resolver usuario desde auth si no se provee
            if ($userId === null) {
                $userId = auth()->id();
            }

            ActivityLog::create([
                'organization_id' => $organizationId,
                'user_id'         => $userId,
                'type'            => $type,
                'level'           => $level,
                'event'           => $event,
                'description'     => $description,
                'subject_type'    => $subject ? get_class($subject) : null,
                'subject_id'      => $subject?->getKey(),
                'properties'      => $properties ?: null,
                'ip_address'      => request()->ip(),
                'created_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            // Nunca romper el flujo principal por un error de log
            \Log::error('ActivityLogger::log failed: ' . $e->getMessage());
        }
    }

    /**
     * Evento de autenticación.
     * Captura ip_address y user_agent automáticamente.
     */
    public static function auth(string $event, string $description, array $properties = []): void
    {
        $properties = array_merge([
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ], $properties);

        static::log('auth', static::levelForAuthEvent($event), $event, $description, $properties);
    }

    /**
     * Evento de upload.
     */
    public static function upload(string $event, string $description, Upload $upload, array $extra = []): void
    {
        $properties = array_merge([
            'upload_id' => $upload->id,
            'filename'  => $upload->original_filename,
        ], $extra);

        static::log(
            'upload',
            static::levelForUploadEvent($event),
            $event,
            $description,
            $properties,
            $upload,
            $upload->organization_id
        );
    }

    /**
     * Evento de etiquetas.
     */
    public static function tags(string $event, string $description, array $extra = []): void
    {
        static::log('tags', 'info', $event, $description, $extra);
    }

    /**
     * Error de eRetail API.
     */
    public static function eretailError(string $event, string $description, array $extra = []): void
    {
        static::log('eretail', 'error', $event, $description, $extra);
    }

    /**
     * Error de sistema.
     */
    public static function systemError(string $event, string $description, array $extra = []): void
    {
        static::log('system', 'error', $event, $description, $extra);
    }

    // ---- Helpers de nivel ----

    private static function levelForAuthEvent(string $event): string
    {
        return match ($event) {
            'login_failed' => 'warning',
            default        => 'info',
        };
    }

    private static function levelForUploadEvent(string $event): string
    {
        return match ($event) {
            'upload_failed' => 'error',
            default         => 'info',
        };
    }
}
