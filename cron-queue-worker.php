<?php
/**
 * Cron Queue Worker
 *
 * Ejecutado por el cron de Hostinger cada minuto.
 * Procesa los jobs pendientes en la queue y se detiene.
 *
 * Configuración en Hostinger hPanel → Cron Jobs:
 * Comando: /usr/bin/php /home/u818011022/domains/tag.pez.com.ar/public_html/cron-queue-worker.php
 * Frecuencia: Cada minuto (o cada 5 minutos)
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('queue:work', [
    '--stop-when-empty' => true,
    '--timeout' => 660,
    '--memory' => 512,
]);
