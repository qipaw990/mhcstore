<?php
/**
 * Format Helpers (Indonesian Rupiah & Date/Time)
 */

function format_rupiah($amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function format_date_id($datetime, bool $withTime = true): string
{
    if (empty($datetime)) return '-';
    $time = strtotime($datetime);
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $day = date('d', $time);
    $month = $months[(int)date('m', $time)];
    $year = date('Y', $time);
    
    if ($withTime) {
        $hour = date('H:i', $time);
        return "{$day} {$month} {$year}, {$hour} WIB";
    }
    return "{$day} {$month} {$year}";
}

function time_elapsed_id($datetime): string
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' mnt lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    return floor($diff / 86400) . ' hari lalu';
}
