<?php
/**
 * Midtrans Payment Gateway Configuration (Sandbox Mode)
 */

return [
    'merchant_id'   => $_ENV['MIDTRANS_MERCHANT_ID'] ?? '',
    'client_key'    => $_ENV['MIDTRANS_CLIENT_KEY'] ?? '',
    'server_key'    => $_ENV['MIDTRANS_SERVER_KEY'] ?? '',
    'is_production' => filter_var($_ENV['MIDTRANS_IS_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'snap_url'      => 'https://app.sandbox.midtrans.com/snap/snap.js',
    'api_url'       => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
    'status_api_url'=> 'https://api.sandbox.midtrans.com/v2',
];
