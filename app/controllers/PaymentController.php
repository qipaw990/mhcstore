<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\MidtransService;
use App\Models\Wallet;
use Exception;

class PaymentController extends Controller
{
    private MidtransService $midtransService;

    public function __construct()
    {
        $this->midtransService = new MidtransService();
    }

    /**
     * Generate Snap Token for CicalengkaPay Wallet Top-Up
     */
    public function topupSnap(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $user = auth_user();
        $data = $this->getPost();
        $amount = (float)($data['amount'] ?? 0);

        if ($amount < 10000) {
            $this->errorResponse('Nominal top up minimal Rp 10.000.');
            return;
        }

        $orderId = 'TOPUP-' . $userId . '-' . time() . '-' . rand(100, 999);

        try {
            $appConfig = require APP_PATH . '/config/app.php';
            $publicUrl = rtrim($appConfig['public_url'] ?? '', '/');

            $params = [
                'transaction_details' => [
                    'order_id'     => $orderId,
                    'gross_amount' => (int)$amount
                ],
                'customer_details' => [
                    'first_name' => $user['name'] ?? 'Pengguna CicalengkaGO',
                    'email'      => $user['email'] ?? 'customer@cicalengkago.id',
                    'phone'      => $user['phone'] ?? '081234567890'
                ],
                'item_details' => [
                    [
                        'id'       => 'TOPUP_CICALENGKAPAY',
                        'price'    => (int)$amount,
                        'quantity' => 1,
                        'name'     => 'Top Up Saldo CicalengkaPay'
                    ]
                ],
                'callbacks' => [
                    'finish'   => $publicUrl . '/wallet',
                    'error'    => $publicUrl . '/wallet',
                    'unfinish' => $publicUrl . '/wallet'
                ]
            ];

            $snapResult = $this->midtransService->createSnapToken($params);

            $this->successResponse('Snap token berhasil dibuat', [
                'snap_token'   => $snapResult['token'],
                'order_id'     => $orderId,
                'client_key'   => $snapResult['client_key'],
                'redirect_url' => $snapResult['redirect_url']
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function verifyClientCallback(): void
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $this->getPost();

        if (empty($data['order_id'])) {
            $this->errorResponse('Data pembayaran tidak lengkap.');
            return;
        }

        $orderId = $data['order_id'];

        // Extract base order code
        $cleanCode = $orderId;
        if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $orderId, $matches)) {
            $cleanCode = $matches[1];
        }

        try {
            // Check if already paid in DB
            $dbOrder = \App\Core\Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$cleanCode]);
            if ($dbOrder && $dbOrder['payment_status'] === 'paid') {
                $this->successResponse('Pesanan sudah lunas', [
                    'status'         => 'settled',
                    'payment_status' => 'paid',
                    'order_code'     => $cleanCode
                ]);
                return;
            }

            if (!empty($data['transaction_status'])) {
                // If in sandbox and client completed payment or requested settlement
                $txStatus = $data['transaction_status'];
                if ($this->midtransService->isSandbox() && in_array($txStatus, ['settlement', 'capture', 'success', 'accept', 'pending'])) {
                    if ($txStatus === 'pending' && !empty($data['force_sandbox_settle'])) {
                        $data['transaction_status'] = 'settlement';
                    }
                }

                $result = $this->midtransService->processNotification($data);
                $this->successResponse('Status pembayaran berhasil diproses', $result);
                return;
            }

            // Fallback: check live Midtrans status API
            $liveStatus = $this->midtransService->getTransactionStatus($orderId);
            if (!empty($liveStatus['success']) && !empty($liveStatus['data'])) {
                $result = $this->midtransService->processNotification($liveStatus['data']);
                $this->successResponse('Status pembayaran berhasil diproses', $result);
                return;
            }

            // In Sandbox mode fallback: if user calls verify, mark as settled
            if ($this->midtransService->isSandbox()) {
                $data['transaction_status'] = 'settlement';
                $result = $this->midtransService->processNotification($data);
                $this->successResponse('Pembayaran diselesaikan (Mode Sandbox)', $result);
                return;
            }

            $this->errorResponse($liveStatus['message'] ?? 'Belum ada data pembayaran terkonfirmasi.');
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Instantly mark payment as success in Sandbox Mode (for Orders or Wallet Top-Up)
     */
    public function simulateSandboxSuccess(): void
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $this->getPost();

        $orderId = trim($data['order_id'] ?? '');
        $paymentType = sanitize($data['payment_type'] ?? 'midtrans_sandbox');

        if (empty($orderId)) {
            $this->errorResponse('Order ID atau kode transaksi tidak valid.');
            return;
        }

        try {
            // Handle Top-Up (TOPUP-{userId}-{timestamp}-{rand})
            if (str_starts_with($orderId, 'TOPUP-')) {
                $parts = explode('-', $orderId);
                $userId = (int)($parts[1] ?? auth_id());
                $amount = (float)($data['amount'] ?? 0);

                if ($amount <= 0 && !empty($data['gross_amount'])) {
                    $amount = (float)$data['gross_amount'];
                }

                if ($amount < 10000) {
                    $amount = 50000;
                }

                $walletModel = new Wallet();
                $walletModel->credit(
                    $userId,
                    $amount,
                    'topup',
                    "Top Up CicalengkaPay via Midtrans Sandbox ({$paymentType})",
                    $orderId
                );

                (new \App\Models\Notification())->createNotification(
                    $userId,
                    'Top Up Midtrans Berhasil! 🎉',
                    "Saldo CicalengkaPay sebesar " . format_rupiah($amount) . " berhasil ditambahkan (Mode Sandbox).",
                    'wallet'
                );

                $this->successResponse('Top Up berhasil diselesaikan (Sandbox Mode)', [
                    'status'         => 'settled',
                    'order_id'       => $orderId,
                    'amount'         => $amount,
                    'payment_status' => 'paid'
                ]);
                return;
            }

            // Handle Order Checkout (CCG-xxx or PCL-xxx)
            $cleanCode = $orderId;
            if (preg_match('/^((?:CCG|PCL)-[A-Za-z0-9]+)/i', $orderId, $matches)) {
                $cleanCode = $matches[1];
            }

            $order = \App\Core\Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? LIMIT 1", [$cleanCode]);
            if (!$order) {
                $this->errorResponse("Pesanan #{$cleanCode} tidak ditemukan.");
                return;
            }

            // Update order status to paid and confirmed
            \App\Core\Database::update('orders', [
                'payment_status' => 'paid',
                'payment_method' => 'midtrans',
                'order_status'   => ($order['order_status'] === 'pending' || $order['order_status'] === 'unpaid') ? 'confirmed' : $order['order_status'],
                'confirmed_at'   => date('Y-m-d H:i:s')
            ], 'id = ?', [$order['id']]);

            // Notify customer
            (new \App\Models\Notification())->createNotification(
                (int)$order['customer_id'],
                'Pembayaran Berhasil! 💳',
                "Pembayaran pesanan #{$order['order_code']} via Midtrans Sandbox berhasil dikonfirmasi.",
                'order'
            );

            $this->successResponse('Pembayaran pesanan berhasil diselesaikan (Sandbox Mode)', [
                'status'         => 'settled',
                'order_code'     => $order['order_code'],
                'order_id'       => $orderId,
                'payment_status' => 'paid',
                'order_status'   => ($order['order_status'] === 'pending') ? 'confirmed' : $order['order_status']
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Webhook Notification Handler from Midtrans Server
     */
    public function notification(): void
    {
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);

        if (!$payload) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
            return;
        }

        try {
            $result = $this->midtransService->processNotification($payload);
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'result' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
