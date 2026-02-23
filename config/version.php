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
    'minor' => 3,
    'patch' => 2,

    /*
    |--------------------------------------------------------------------------
    | Información adicional
    |--------------------------------------------------------------------------
    */

    'release_date' => '2026-02-23',
    'codename' => '',

    /*
    |--------------------------------------------------------------------------
    | Changelog resumido
    |--------------------------------------------------------------------------
    */

    'changelog' => [
        '1.3.2' => [
            'date' => '2026-02-23',
            'changes' => [
                'Diseño responsivo completo para mobile y tablet',
                'Navbar con hamburger menu (Alpine.js) — la app era inutilizable en mobile',
                'Menú mobile desplegable con todos los links, usuario y acciones',
                'Nombre de usuario oculto en desktop < 768px para ahorrar espacio',
                'Botones de header con flex-wrap en todas las vistas (show, report, create, tags, dashboard, users)',
                'Filtros de uploads/show y tags/index apilan en mobile',
                'Grid de resumen en uploads/report pasa a 1 columna en mobile',
                'Assets de Vite incluidos en el repo (public/build trackeado) para Hostinger',
            ],
        ],
        '1.3.1' => [
            'date' => '2026-02-23',
            'changes' => [
                'Refresh automático de etiquetas mejorado: muestra resultado en banner informativo',
                'Tabla de uploads: columna de etiquetas refrescadas con ícono y contador',
                'Indicador visual de tags actualizados en la vista de detalle de upload',
            ],
        ],
        '1.3.0' => [
            'date' => '2026-02-23',
            'changes' => [
                'Señal LED flash para etiquetas ESL (individual y múltiple)',
                'Modal de selección de color (rojo, verde, azul) y duración del flash',
                'Botón Flash LED en toolbar de etiquetas y en acciones por fila',
                'Integración con endpoint LED de la API eRetail',
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
