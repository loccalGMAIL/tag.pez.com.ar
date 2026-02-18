<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Versión de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Seguimos el esquema de versionado semántico (SemVer):
    | MAJOR.MINOR.PATCH
    |
    | MAJOR: Cambios incompatibles con versiones anteriores
    | MINOR: Nueva funcionalidad compatible con versiones anteriores
    | PATCH: Correcciones de bugs compatibles con versiones anteriores
    |
    */

    'major' => 2,
    'minor' => 0,
    'patch' => 0,

    /*
    |--------------------------------------------------------------------------
    | Información adicional
    |--------------------------------------------------------------------------
    */

    'release_date' => '2026-02-18',
    'codename' => 'SaaS',

    /*
    |--------------------------------------------------------------------------
    | Changelog resumido
    |--------------------------------------------------------------------------
    */

    'changelog' => [
        '2.0.0' => [
            'date' => '2026-02-18',
            'changes' => [
                'Conversión a arquitectura SaaS multi-tenant',
                'Tabla organizations con credenciales eRetail (API + DB) cifradas por tenant',
                'Aislamiento automático de datos por organización via global scopes (BelongsToTenant)',
                'TenantManager: gestión del contexto de tenant y override de conexión DB en runtime',
                'Panel de super-administración (/admin): organizaciones, usuarios, uploads cross-tenant',
                'Impersonación de organizaciones desde el panel admin con banner visible',
                'Diagnóstico de conexión BD por organización desde el panel admin',
                'ProcessUploadJob restaura contexto de tenant en workers de cola',
                'ERetailService lee credenciales del tenant activo (token cache per-tenant)',
                'AppSetting con cache keys aisladas por tenant',
                'Corrección: credenciales hardcodeadas eliminadas de config/database.php',
                'Corrección de nombre: ELS → ESL en títulos de página',
            ],
        ],
        '1.2.2' => [
            'date' => '2026-02-05',
            'changes' => [
                'Actividades recientes del dashboard: resumen por upload en vez de logs individuales',
                'Títulos diferenciados por estado de upload (completado, procesando, error, en espera)',
                'Estadísticas de progreso (procesados/total) en actividades recientes',
                'Corrección de campos inexistentes en logs de procesamiento (product_id, message)',
            ],
        ],
        '1.2.1' => [
            'date' => '2026-02-05',
            'changes' => [
                'Queue worker vía ruta HTTP (reemplazo de cron bloqueado en Hostinger)',
                'Límite de productos por upload con estado pending_approval',
                'Advertencia informativa en vista de carga de archivos',
                'Instrucciones colapsables en formulario de upload',
                'Comando upload:reprocess con flag --force para consola',
                'Redirect a lista de uploads después de carga exitosa',
                'Corrección de timeout en queue:listen local',
            ],
        ],
        '1.2.0' => [
            'date' => '2026-02-03',
            'changes' => [
                'Nuevo módulo de gestión de Etiquetas (Tags)',
                'Vista con DataTable de etiquetas con filtros y búsqueda',
                'Estadísticas de etiquetas: total, vinculadas, batería baja, offline',
                'Detalle de etiqueta con información de batería, señal y comunicación',
                'Función para refrescar etiquetas individuales o múltiples',
                'Enlace de navegación a Etiquetas en el menú principal',
            ],
        ],
        '1.1.0' => [
            'date' => '2026-02-03',
            'changes' => [
                'Corrección de detección de APs en línea (estados 1 y 2)',
                'Nombre de AP ahora se toma del campo Remark',
                'ShopCode se obtiene desde configuración en lugar de .env',
                'Actividades recientes solo muestran desconexiones de AP',
                'Agregado sistema de versionado',
            ],
        ],
        '1.0.0' => [
            'date' => '2026-01-15',
            'changes' => [
                'Versión inicial del sistema',
                'Dashboard con métricas de eRetail',
                'Carga y procesamiento de archivos Excel',
                'Integración con API de eRetail',
                'Gestión de productos y variantes',
            ],
        ],
    ],
];
