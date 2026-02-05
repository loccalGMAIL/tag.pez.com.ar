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

    'major' => 1,
    'minor' => 2,
    'patch' => 1,

    /*
    |--------------------------------------------------------------------------
    | Información adicional
    |--------------------------------------------------------------------------
    */

    'release_date' => '2026-02-05',
    'codename' => '',

    /*
    |--------------------------------------------------------------------------
    | Changelog resumido
    |--------------------------------------------------------------------------
    */

    'changelog' => [
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
