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

function get_store_schedule_status(int $storeId, int $vendorIsOpen = 1): array
{
    $dayOfWeek = (int)date('w');
    $currentTime = date('H:i:s');

    try {
        $schedules = \App\Core\Database::query(
            "SELECT opening_time, closing_time FROM store_schedules WHERE store_id = ? AND day_of_week = ? ORDER BY opening_time ASC",
            [$storeId, $dayOfWeek]
        );
    } catch (\Throwable $e) {
        $schedules = [];
    }

    if (empty($schedules)) {
        $schedules = [
            ['opening_time' => '08:00:00', 'closing_time' => '22:00:00']
        ];
    }

    $isWithinHours = false;
    $formattedShifts = [];
    $firstOpen = null;
    $lastClose = null;

    foreach ($schedules as $idx => $sch) {
        $op = $sch['opening_time'] ?? '08:00:00';
        $cl = $sch['closing_time'] ?? '22:00:00';

        if ($idx === 0) $firstOpen = date('H:i', strtotime($op));
        $lastClose = date('H:i', strtotime($cl));

        if ($currentTime >= $op && $currentTime <= $cl) {
            $isWithinHours = true;
        }

        $formattedShifts[] = date('H:i', strtotime($op)) . ' - ' . date('H:i', strtotime($cl));
    }

    $operatingHours  = implode(', ', $formattedShifts);
    $isCurrentlyOpen = ($isWithinHours && (int)$vendorIsOpen === 1);

    return [
        'is_open'          => $isCurrentlyOpen,
        'is_within_hours'  => $isWithinHours,
        'vendor_is_open'   => ((int)$vendorIsOpen === 1),
        'opening_time'     => $firstOpen,
        'closing_time'     => $lastClose,
        'operating_hours'  => $operatingHours
    ];
}

function attach_store_schedule_data(&$store): void
{
    if (empty($store['id'])) return;
    $storeId = (int)$store['id'];
    $scheduleData = get_store_schedule_status($storeId, (int)($store['is_open'] ?? 1));
    
    $isOpen = $scheduleData['is_open'];

    // If store has a Grab URL and is within normal operating hours, perform live Grab check with 5-minute session caching
    if (!empty($store['grab_url']) && $scheduleData['is_within_hours']) {
        $liveStatus = check_grab_url_is_open($store['grab_url']);
        if ($liveStatus !== null) {
            $isOpen = $liveStatus && ((int)($store['is_open'] ?? 1) === 1);
        }
    }

    $store['is_open']         = $isOpen ? 1 : 0;
    $store['opening_time']    = $scheduleData['opening_time'];
    $store['closing_time']    = $scheduleData['closing_time'];
    $store['operating_hours'] = $scheduleData['operating_hours'];
}

function check_grab_url_is_open(string $url): ?bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) return null;

    $cacheKey = 'grab_live_status_' . md5($url);
    if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheKey . '_time']) && (time() - $_SESSION[$cacheKey . '_time']) < 300) {
        return (bool)$_SESSION[$cacheKey];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($html)) {
        return null;
    }

    $htmlLower = strtolower($html);
    $closedPhrases = [
        'sedang tutup',
        'resto tutup',
        'toko tutup',
        'restoran tutup',
        'tutup sementara',
        'currently closed',
        'temporarily closed',
        'tidak menerima pesanan',
        'closedbanner',
        'closedtag'
    ];

    $isOpen = true;
    foreach ($closedPhrases as $phrase) {
        if (str_contains($htmlLower, $phrase)) {
            $isOpen = false;
            break;
        }
    }

    if (str_contains($htmlLower, '"isopen":false') || str_contains($htmlLower, '"closed":true')) {
        $isOpen = false;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$cacheKey] = $isOpen;
        $_SESSION[$cacheKey . '_time'] = time();
    }

    return $isOpen;
}


