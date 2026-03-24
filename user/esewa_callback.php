<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/esewa_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'user') {
    header('Location: /flight/login.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

// eSewa sends response as base64-encoded JSON in 'data' query parameter
$dataParam = $_GET['data'] ?? $_POST['data'] ?? '';
$isFailure = (strpos($_SERVER['REQUEST_URI'], 'failure') !== false || isset($_GET['failure']));

if (empty($dataParam) && !$isFailure) {
    header('Location: /flight/user/dashboard.php?payment=error');
    exit;
}

$paymentOk = false;
$bookingId = null;
$transactionCode = '';

if (!$isFailure && $dataParam) {
    $decoded = base64_decode($dataParam, true);
    if ($decoded === false) {
        header('Location: /flight/user/dashboard.php?payment=error');
        exit;
    }
    $response = json_decode($decoded, true);
    
    if (!$response || empty($response['transaction_uuid'])) {
        header('Location: /flight/user/dashboard.php?payment=error');
        exit;
    }

    // transaction_uuid format we use: booking-{bookingId}-{uniqid}
    $uuid = $response['transaction_uuid'];
    $status = $response['status'] ?? '';
    $totalAmount = (float)($response['total_amount'] ?? 0);
    $transactionCode = $response['transaction_code'] ?? '';

    if ($status === 'COMPLETE' && preg_match('/^booking-(\d+)-/', $uuid, $m)) {
        $bookingId = (int)$m[1];
        
        // Verify booking belongs to user
        $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'PENDING'");
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $booking = $res->fetch_assoc();
        $stmt->close();
        
        if ($booking) {
            $stmt = $conn->prepare("
                UPDATE bookings 
                SET payment_method = 'ESEWA', 
                    payment_status = 'COMPLETED', 
                    payment_transaction_id = ?,
                    payment_amount = ?,
                    payment_date = NOW(),
                    status = 'CONFIRMED'
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param('sdii', $transactionCode, $totalAmount, $bookingId, $userId);
            if ($stmt->execute()) {
                $paymentOk = true;
            }
            $stmt->close();
        }
    }
}

if ($paymentOk) {
    header('Location: /flight/user/dashboard.php?payment_success=1');
} else {
    header('Location: /flight/user/dashboard.php?payment=failed');
}
exit;
