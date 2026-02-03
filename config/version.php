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
    'minor' => 1,
    'patch' => 0,

    /*
    |--------------------------------------------------------------------------
    | Información adicional
    |--------------------------------------------------------------------------
    */

    'release_date' => '2026-02-03',
    'codename' => '',

    /*
    |--------------------------------------------------------------------------
    | Changelog resumido
    |--------------------------------------------------------------------------
    */

    'changelog' => [
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
