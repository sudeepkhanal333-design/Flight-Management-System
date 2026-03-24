<?php
/**
 * eSewa failure return – redirect user to FlyWings dashboard with payment=failed
 */
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/flight';
$dashboardUrl = rtrim($baseUrl, '/') . '/user/dashboard.php?payment=failed';

header('Location: ' . $dashboardUrl);
exit;
