<?php
require_once __DIR__ . '/app/autoload.php';
require_once __DIR__ . '/app/config/constants.php';
require_once __DIR__ . '/app/helpers/format.php';

use App\Services\MidtransService;

echo "=== TESTING MIDTRANS SNAP API CONNECTION ===" . PHP_EOL;

try {
    $service = new MidtransService();
    $orderId = 'TEST-CCG-' . time();
    $params = [
        'transaction_details' => [
            'order_id'     => $orderId,
            'gross_amount' => 25000
        ],
        'customer_details' => [
            'first_name' => 'Budi Santoso',
            'email'      => 'budi@cicalengka.id',
            'phone'      => '081234567890'
        ],
        'item_details' => [
            [
                'id'       => 'ITEM-1',
                'price'    => 25000,
                'quantity' => 1,
                'name'     => 'Nasi Goreng Cicalengka Special'
            ]
        ]
    ];

    $result = $service->createSnapToken($params);
    echo "SUCCESS! Snap Token Generated Successfully." . PHP_EOL;
    echo "Snap Token  : " . $result['token'] . PHP_EOL;
    echo "Client Key  : " . $result['client_key'] . PHP_EOL;
    echo "Redirect URL: " . $result['redirect_url'] . PHP_EOL;

    echo PHP_EOL . "=== TESTING SIGNATURE VERIFICATION ===" . PHP_EOL;
    $serverKey = getenv('MIDTRANS_SERVER_KEY') ?: 'DUMMY_SERVER_KEY';
    $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
    $verified = $service->verifySignature($orderId, $statusCode, $grossAmount, $signature);
    echo "Signature Verified: " . ($verified ? "TRUE (VALID)" : "FALSE") . PHP_EOL;
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
}
