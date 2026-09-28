<?php
// Cron: kadaluarsakan tiket top up pending yang basi (>2 jam).
// Sesi DOKU payment_due_date cuma 60 menit - webhook takkan datang lagi.
// Pasang tiap 15 menit: php /var/www/html/scripts/expire_pending_topups.php
define('APP_PATH', dirname(__DIR__) . '/app');
require APP_PATH . '/autoload.php';

use App\Models\TopupLog;

try {
    $count = (new TopupLog())->expireStalePending(2);
    echo date('Y-m-d H:i:s') . " expired_stale_pending={$count}\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'expire_pending_topups FAILED: ' . $e->getMessage() . "\n");
    exit(1);
}
