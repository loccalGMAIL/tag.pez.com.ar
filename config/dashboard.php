<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración específica para el Dashboard ELS Retail
    |
    */

    // Intervalo de auto-actualización en segundos
    'auto_refresh_interval' => env('DASHBOARD_REFRESH_INTERVAL', 30),

    // Timeout para considerar AP offline (en minutos)
    'ap_offline_timeout' => env('DASHBOARD_AP_OFFLINE_TIMEOUT', 5),

    // Límite de actividades recientes a mostrar
    'recent_activities_limit' => env('DASHBOARD_ACTIVITIES_LIMIT', 10),

    // Período para actividades recientes (en horas)
    'recent_activities_hours' => env('DASHBOARD_ACTIVITIES_HOURS',300),

    // Configuración de cache para métricas
    'cache' => [
        'enabled' => env('DASHBOARD_CACHE_ENABLED', true),
        'ttl' => env('DASHBOARD_CACHE_TTL', 30), // segundos
    ],

    // Configuración de notificaciones
    'notifications' => [
        'enabled' => env('DASHBOARD_NOTIFICATIONS_ENABLED', false),
        'webhook_url' => env('DASHBOARD_WEBHOOK_URL', null),
        'email' => env('DASHBOARD_EMAIL_NOTIFICATIONS', false),
    ],

    // Umbrales de alerta
    'alerts' => [
        'ap_offline_threshold' => env('DASHBOARD_AP_OFFLINE_THRESHOLD', 0.8), // 80% offline
        'etiquetas_unlinked_threshold' => env('DASHBOARD_ETIQUETAS_UNLINKED_THRESHOLD', 0.3), // 30% sin vincular
    ],

    // Configuración de logs
    'logging' => [
        'enabled' => env('DASHBOARD_LOGGING_ENABLED', true),
        'level' => env('DASHBOARD_LOG_LEVEL', 'info'),
        'max_files' => env('DASHBOARD_LOG_MAX_FILES', 30),
    ],

    // Configuración de métricas personalizadas
    'custom_metrics' => [
        'enabled' => env('DASHBOARD_CUSTOM_METRICS', false),
        'definitions' => [
            // Aquí se pueden definir métricas personalizadas
            // 'tienda_especifica' => [
            //     'query' => 'SELECT COUNT(*) FROM AP WHERE ShopCode = ?',
            //     'parameters' => ['TIENDA001']
            // ]
        ],
    ],

    // Configuración de exportación
    'export' => [
        'enabled' => env('DASHBOARD_EXPORT_ENABLED', true),
        'formats' => ['csv', 'xlsx', 'pdf'],
        'max_records' => env('DASHBOARD_EXPORT_MAX_RECORDS', 10000),
    ],

    // Configuración de widgets
    'widgets' => [
        'default_layout' => [
            'metrics' => [1, 2, 3, 4], // Grid de 4 columnas
            'activities' => [1, 2, 3, 4, 5, 6], // Grid de 6 filas
        ],
        'available' => [
            'metrics_cards',
            'activities_list',
            'connection_status',
            'real_time_chart',
            'system_health',
        ],
    ],
]; 