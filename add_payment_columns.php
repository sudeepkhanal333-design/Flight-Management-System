<?php
/**
 * Migration Script: Add payment columns to bookings table
 * Run this once: http://localhost/flight/add_payment_columns.php
 */

require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payment Columns - Migration</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #0f172a;
            color: #e2e8f0;
        }
        .success { color: #4ade80; padding: 15px; background: #065f46; border-radius: 8px; margin: 10px 0; }
        .error { color: #f87171; padding: 15px; background: #7f1d1d; border-radius: 8px; margin: 10px 0; }
        .info { color: #93c5fd; padding: 15px; background: #1e3a8a; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Database Migration: Add Payment Columns</h1>
    
<?php
try {
    $columnsAdded = [];
    $errors = [];
    
    // Check and add payment_method
    $check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
    if ($check && $check->num_rows > 0) {
        $columnsAdded[] = 'payment_method (already exists)';
    } else {
        if ($conn->query("ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER status")) {
            $columnsAdded[] = 'payment_method';
        } else {
            $errors[] = 'payment_method: ' . $conn->error;
        }
    }
    
    // Check and add payment_status
    $check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
    if ($check && $check->num_rows > 0) {
        $columnsAdded[] = 'payment_status (already exists)';
    } else {
        if ($conn->query("ALTER TABLE bookings ADD COLUMN payment_status ENUM('PENDING', 'COMPLETED', 'FAILED', 'REFUNDED') DEFAULT 'PENDING' AFTER payment_method")) {
            $columnsAdded[] = 'payment_status';
        } else {
            $errors[] = 'payment_status: ' . $conn->error;
        }
    }
    
    // Check and add payment_transaction_id
    $check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_transaction_id'");
    if ($check && $check->num_rows > 0) {
        $columnsAdded[] = 'payment_transaction_id (already exists)';
    } else {
        if ($conn->query("ALTER TABLE bookings ADD COLUMN payment_transaction_id VARCHAR(100) DEFAULT NULL AFTER payment_status")) {
            $columnsAdded[] = 'payment_transaction_id';
        } else {
            $errors[] = 'payment_transaction_id: ' . $conn->error;
        }
    }
    
    // Check and add payment_amount
    $check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_amount'");
    if ($check && $check->num_rows > 0) {
        $columnsAdded[] = 'payment_amount (already exists)';
    } else {
        if ($conn->query("ALTER TABLE bookings ADD COLUMN payment_amount DECIMAL(10,2) DEFAULT NULL AFTER payment_transaction_id")) {
            $columnsAdded[] = 'payment_amount';
        } else {
            $errors[] = 'payment_amount: ' . $conn->error;
        }
    }
    
    // Check and add payment_date
    $check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_date'");
    if ($check && $check->num_rows > 0) {
        $columnsAdded[] = 'payment_date (already exists)';
    } else {
        if ($conn->query("ALTER TABLE bookings ADD COLUMN payment_date TIMESTAMP NULL DEFAULT NULL AFTER payment_amount")) {
            $columnsAdded[] = 'payment_date';
        } else {
            $errors[] = 'payment_date: ' . $conn->error;
        }
    }
    
    if (count($errors) > 0) {
        echo '<div class="error">❌ Errors occurred:</div>';
        foreach ($errors as $error) {
            echo '<div class="error">' . htmlspecialchars($error) . '</div>';
        }
    }
    
    if (count($columnsAdded) > 0) {
        echo '<div class="success">✅ Successfully processed columns:</div>';
        foreach ($columnsAdded as $col) {
            echo '<div class="success">• ' . htmlspecialchars($col) . '</div>';
        }
        echo '<p><strong>Migration completed!</strong> You can now delete this file.</p>';
    } else {
        echo '<div class="info">All payment columns already exist. No migration needed.</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p>Please check your database connection and permissions.</p>';
}
?>

    <hr style="border-color: #334155; margin: 20px 0;">
    <p style="color: #94a3b8; font-size: 14px;">
        <strong>Note:</strong> After running this migration successfully, you can safely delete this file (add_payment_columns.php) for security.
    </p>
    <p style="color: #94a3b8; font-size: 14px;">
        <a href="/flight/user/dashboard.php" style="color: #60a5fa;">← Back to Dashboard</a>
    </p>
</body>
</html>
