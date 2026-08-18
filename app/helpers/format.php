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

function asset_url(?string $path, string $fallback = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80'): string
{
    $path = trim((string)$path);
    if (empty($path)) {
        return $fallback;
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $baseUrl = rtrim($GLOBALS['baseUrl'] ?? '', '/');
    return $baseUrl . '/' . ltrim($path, '/');
}

