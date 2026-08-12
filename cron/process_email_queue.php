<?php

declare(strict_types=1);

/**
 * Cron: procesar cola de emails pendientes vía SendGrid.
 *
 * Reintenta el envío de los correos que quedaron "pending" (por ejemplo, si el envío
 * síncrono al momento de la solicitud falló). Configurar en Hostinger para ejecutarse
 * cada 5 minutos:
 *
 *   php /home/usuario/likantor-masterclasses/cron/process_email_queue.php
 */

require dirname(__DIR__) . '/app/init.php';

use App\Core\ErrorHandler;
use App\Services\EmailService;

try {
    $processed = (new EmailService())->processPendingQueue(50);
    echo date('c') . " — Emails procesados: {$processed}" . PHP_EOL;
} catch (\Throwable $e) {
    ErrorHandler::log('Cron process_email_queue failed', ['error' => $e->getMessage()]);
    fwrite(STDERR, 'Error procesando cola de emails: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
