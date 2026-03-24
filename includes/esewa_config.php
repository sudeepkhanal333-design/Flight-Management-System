<?php
/**
 * eSewa payment gateway configuration
 * For UAT/Testing: use EPAYTEST, for production replace with your merchant credentials
 */

// Use eSewa live form (true = redirect to eSewa via esewa-test success/failure, false = manual entry only)
$ESEWA_LIVE = true;

// eSewa UAT (Testing) - same as esewa-test/pay.php
$ESEWA_CONFIG = [
    'product_code' => 'EPAYTEST',
    'secret_key'   => '8gBm/:&EnhH.1/q',  // UAT secret (match pay.php)
    'form_url'     => 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
];

// eSewa Production (uncomment and set when going live)
// $ESEWA_CONFIG = [
//     'product_code' => 'YOUR_MERCHANT_CODE',
//     'secret_key'   => 'YOUR_SECRET_KEY',
//     'form_url'     => 'https://epay.esewa.com.np/api/epay/main/v2/form',
// ];

/**
 * Generate eSewa HMAC-SHA256 signature for request
 * Signed fields order: total_amount, transaction_uuid, product_code
 */
function esewa_signature(float $totalAmount, string $transactionUuid, string $productCode, string $secretKey): string
{
    // Ensure total_amount uses two-decimal fixed format to match form inputs
    $totalStr = number_format($totalAmount, 2, '.', '');
    $message = "total_amount={$totalStr},transaction_uuid={$transactionUuid},product_code={$productCode}";
    $signature = hash_hmac('sha256', $message, $secretKey, true);
    return base64_encode($signature);
}
