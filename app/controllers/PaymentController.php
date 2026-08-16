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
                    'payment_status' => 'paid'
                ]);
                return;
            }

            if (!empty($data['transaction_status'])) {
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

            $this->errorResponse($liveStatus['message'] ?? 'Belum ada data pembayaran terkonfirmasi.');
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
