<?php
/**
 * Migration Script: Add total_seats column to flights table
 * Run this once: http://localhost/flight/add_total_seats_column.php
 */

require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Total Seats Column - Migration</title>
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
    <h1>Database Migration: Add total_seats Column</h1>
    
<?php
try {
    // Check if column already exists
    $checkResult = $conn->query("SHOW COLUMNS FROM flights LIKE 'total_seats'");
    
    if ($checkResult && $checkResult->num_rows > 0) {
        echo '<div class="info">✅ Column "total_seats" already exists in the flights table.</div>';
        echo '<p>No migration needed. You can delete this file.</p>';
    } else {
        // Add the column
        $sql = "ALTER TABLE flights ADD COLUMN total_seats INT NOT NULL DEFAULT 180 AFTER base_fare";
        
        if ($conn->query($sql)) {
            echo '<div class="success">✅ Successfully added "total_seats" column to flights table!</div>';
            
            // Update existing flights with default value
            $updateResult = $conn->query("UPDATE flights SET total_seats = 180 WHERE total_seats IS NULL OR total_seats = 0");
            $affected = $conn->affected_rows;
            
            echo '<div class="success">✅ Updated ' . $affected . ' existing flight(s) with default seat capacity (180 seats).</div>';
            echo '<p><strong>Migration completed successfully!</strong> You can now delete this file.</p>';
        } else {
            throw new Exception('Failed to add column: ' . $conn->error);
        }
    }
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p>Please check your database connection and permissions.</p>';
}
?>

    <hr style="border-color: #334155; margin: 20px 0;">
    <p style="color: #94a3b8; font-size: 14px;">
        <strong>Note:</strong> After running this migration successfully, you can safely delete this file (add_total_seats_column.php) for security.
    </p>
</body>
</html>
