<?php
/**
 * eSewa success return – forwards response to FlyWings callback
 * eSewa redirects here after successful payment with response in GET/POST (e.g. data=base64)
 */
$data = $_GET['data'] ?? $_POST['data'] ?? '';

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/flight';
$callbackUrl = rtrim($baseUrl, '/') . '/user/esewa_callback.php';

if ($data !== '') {
    header('Location: ' . $callbackUrl . '?data=' . urlencode($data));
} else {
    // No data (e.g. direct visit) – send to dashboard
    header('Location: ' . rtrim($baseUrl, '/') . '/user/dashboard.php');
}
exit;
