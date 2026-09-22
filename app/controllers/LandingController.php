<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * LandingController
 *
 * Menampilkan halaman landing page publik di cicago.store
 * Tidak memerlukan autentikasi.
 */
class LandingController extends Controller
{
    /**
     * GET /
     * Halaman utama landing page CicalengkaGO
     */
    public function index(): void
    {
        // Ambil data live dari DB untuk ditampilkan di landing page
        $stats = $this->getStats();
        $appConfig = require APP_PATH . '/config/app.php';
        $publicUrl = rtrim($appConfig['public_url'] ?? 'https://cicago.store', '/');

        // Render tanpa layout (landing page standalone, bukan admin panel)
        \App\Core\View::render('landing/index', [
            'stats'     => $stats,
            'publicUrl' => $publicUrl,
        ], null);
    }

    /**
     * Ambil statistik live platform untuk ditampilkan di hero section
     */
    private function getStats(): array
    {
        try {
            $storeCount = Database::fetchOne("SELECT COUNT(*) as c FROM `stores` WHERE `status` = 'active'")['c'] ?? 0;
            $orderCount = Database::fetchOne("SELECT COUNT(*) as c FROM `orders`")['c'] ?? 0;
            $userCount  = Database::fetchOne("SELECT COUNT(*) as c FROM `users` WHERE `role` = 'customer'")['c'] ?? 0;
            $driverCount = Database::fetchOne("SELECT COUNT(*) as c FROM `delivery_men` WHERE `status` = 'active'")['c'] ?? 0;
        } catch (\Throwable $e) {
            $storeCount = $orderCount = $userCount = $driverCount = 0;
        }

        return [
            'stores'  => (int)$storeCount,
            'orders'  => (int)$orderCount,
            'users'   => (int)$userCount,
            'drivers' => (int)$driverCount,
        ];
    }
}
