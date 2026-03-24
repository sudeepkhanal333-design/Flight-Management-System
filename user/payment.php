<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/esewa_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'user') {
    header('Location: /flight/login.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$bookingId = (int)($_GET['booking_id'] ?? 0);
$paymentMethod = trim($_GET['method'] ?? '');

// Validate payment method
$allowedMethods = ['ESEWA', 'MOBILE_BANKING', 'UPI', 'CARD', 'NETBANKING'];
if (!in_array(strtoupper($paymentMethod), $allowedMethods)) {
    $paymentMethod = 'UPI';
}

// Fetch booking details
$booking = null;
$flight = null;
$totalAmount = 0;

if ($bookingId > 0) {
    $stmt = $conn->prepare("
        SELECT b.*, f.flight_code, f.origin, f.destination, f.departure_time, f.arrival_time, f.base_fare
        FROM bookings b
        JOIN flights f ON b.flight_id = f.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'PENDING'
    ");
    $stmt->bind_param('ii', $bookingId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if ($booking) {
        $flight = $booking;
        $totalAmount = (float)$booking['base_fare'] * (int)$booking['seats'];
    }
}

if (!$booking) {
    header('Location: /flight/user/dashboard.php?error=invalid_booking');
    exit;
}

// eSewa redirect URLs: use esewa-test folder (success.php / failure.php) then forward to app
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$flightRoot = dirname(dirname($_SERVER['SCRIPT_NAME'])); // /flight
$flightBaseUrl = $protocol . '://' . $host . $flightRoot;
$esewaSuccessUrl = rtrim($flightBaseUrl, '/') . '/esewa-test/success.php';
$esewaFailureUrl = rtrim($flightBaseUrl, '/') . '/esewa-test/failure.php';

$paymentError = '';
$paymentSuccess = false;

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_payment') {
    $transactionId = trim($_POST['transaction_id'] ?? '');
    $amountPaid = (float)($_POST['amount'] ?? 0);
    $paymentMethodPost = trim($_POST['payment_method'] ?? '');
    
    // Validate amount matches
    if (abs($amountPaid - $totalAmount) > 0.01) {
        $paymentError = 'Payment amount mismatch. Please try again.';
    } elseif (empty($transactionId)) {
        $paymentError = 'Transaction ID is required.';
    } else {
        // Simulate payment processing based on method
        $paymentStatus = 'COMPLETED';
        
        // For eSewa: Validate transaction ID format (eSewa format: usually starts with EP)
        if (strtoupper($paymentMethodPost) === 'ESEWA') {
            if (!preg_match('/^EP\d+/', $transactionId)) {
                $paymentError = 'Invalid eSewa transaction ID format. eSewa IDs usually start with EP.';
            } else {
                // In real implementation, verify with eSewa API here
                // For demo: accept any EP-prefixed ID
                $paymentStatus = 'COMPLETED';
            }
        }
        
        // For Mobile Banking: Validate transaction ID (usually numeric)
        if (strtoupper($paymentMethodPost) === 'MOBILE_BANKING') {
            if (!preg_match('/^\d{10,}$/', $transactionId)) {
                $paymentError = 'Invalid mobile banking transaction ID. Please enter a valid transaction reference number.';
            } else {
                // In real implementation, verify with bank API here
                $paymentStatus = 'COMPLETED';
            }
        }
        
        if (empty($paymentError)) {
            // Update booking with payment details
            $stmt = $conn->prepare("
                UPDATE bookings 
                SET payment_method = ?, 
                    payment_status = ?, 
                    payment_transaction_id = ?, 
                    payment_amount = ?,
                    payment_date = NOW(),
                    status = 'CONFIRMED'
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param('sssdi', 
                $paymentMethodPost, 
                $paymentStatus, 
                $transactionId, 
                $amountPaid,
                $bookingId, 
                $userId
            );
            
            if ($stmt->execute()) {
                $paymentSuccess = true;
                // Redirect after 2 seconds
                header('Refresh: 2; url=/flight/user/dashboard.php?payment_success=1');
            } else {
                $paymentError = 'Failed to process payment. Please contact support.';
            }
            $stmt->close();
        }
    }
}

fw_header("Payment - FlyWings");
?>

<section class="max-w-2xl mx-auto px-4 py-10 md:py-14">
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 md:p-8">
        <h1 class="text-2xl font-semibold mb-2">💳 Complete Payment</h1>
        <p class="text-sm text-slate-400 mb-6">Complete your booking by processing the payment</p>

        <?php if ($paymentError): ?>
            <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <?php echo htmlspecialchars($paymentError); ?>
            </div>
        <?php endif; ?>

        <?php if ($paymentSuccess): ?>
            <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                ✅ Payment processed successfully! Redirecting to dashboard...
            </div>
        <?php else: ?>
            <!-- Booking Summary -->
            <div class="mb-6 rounded-xl bg-slate-950 border border-slate-800 p-4">
                <h2 class="text-sm font-semibold mb-3">Booking Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Flight:</span>
                        <span class="text-slate-200 font-semibold"><?php echo htmlspecialchars($flight['flight_code']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Route:</span>
                        <span class="text-slate-200"><?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Departure:</span>
                        <span class="text-slate-200"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($flight['departure_time']))); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Seats:</span>
                        <span class="text-slate-200"><?php echo (int)$booking['seats']; ?></span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-slate-800">
                        <span class="text-slate-300 font-semibold">Total Amount:</span>
                        <span class="text-emerald-300 font-bold text-lg">Rs.<?php echo number_format($totalAmount, 2); ?></span>
                    </div>
                </div>
            </div>

            <?php $useEsewaRedirect = (strtoupper($paymentMethod) === 'ESEWA' && !empty($ESEWA_LIVE)); ?>

            <?php if ($useEsewaRedirect): ?>
                <!-- eSewa only: standalone form (no nested form – green button must be clickable) -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-300">Payment Method</label>
                        <div class="rounded-xl bg-slate-950 border border-slate-700 px-4 py-3">
                            <span class="text-sky-300 font-semibold">eSewa</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border-2 border-[#54a754] bg-gradient-to-b from-[#6bc46b]/20 to-slate-950 overflow-hidden">
                        <div class="bg-[#54a754] px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-xl font-bold text-[#54a754]">e</div>
                            <div>
                                <h3 class="font-bold text-white text-sm">Pay with eSewa</h3>
                                <p class="text-white/90 text-xs">Safe &amp; secure payment</p>
                            </div>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-400">Amount to pay</span>
                                <span class="text-xl font-bold text-[#54a754]">Rs. <?php echo number_format($totalAmount, 2); ?></span>
                            </div>
                            <?php
                            $transactionUuid = 'booking-' . $bookingId . '-' . preg_replace('/[^a-z0-9-]/', '', uniqid('', true));
                            $taxAmount = 0;
                            $serviceCharge = 0;
                            $deliveryCharge = 0;
                            $totalForEsewa = $totalAmount + $taxAmount + $serviceCharge + $deliveryCharge;
                            $signature = esewa_signature($totalForEsewa, $transactionUuid, $ESEWA_CONFIG['product_code'], $ESEWA_CONFIG['secret_key']);
                            ?>
                            <form id="esewaForm" action="<?php echo htmlspecialchars($ESEWA_CONFIG['form_url']); ?>" method="POST" class="space-y-0">
                                <input type="hidden" name="amount" value="<?php echo number_format($totalAmount, 2, '.', ''); ?>">
                                <input type="hidden" name="tax_amount" value="<?php echo number_format($taxAmount, 2, '.', ''); ?>">
                                <input type="hidden" name="total_amount" value="<?php echo number_format($totalForEsewa, 2, '.', ''); ?>">
                                <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transactionUuid); ?>">
                                <input type="hidden" name="product_code" value="<?php echo htmlspecialchars($ESEWA_CONFIG['product_code']); ?>">
                                <input type="hidden" name="product_service_charge" value="0">
                                <input type="hidden" name="product_delivery_charge" value="0">
                                <input type="hidden" name="success_url" value="<?php echo htmlspecialchars($esewaSuccessUrl); ?>">
                                <input type="hidden" name="failure_url" value="<?php echo htmlspecialchars($esewaFailureUrl); ?>">
                                <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                                <input type="hidden" name="signature" value="<?php echo htmlspecialchars($signature); ?>">
                                <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#54a754] hover:bg-[#4a964a] text-white font-bold py-4 px-6 transition shadow-lg cursor-pointer">
                                    <span class="text-2xl">💰</span>
                                    <span>Pay Rs. <?php echo number_format($totalForEsewa, 2); ?> with eSewa</span>
                                </button>
                            </form>
                            <p class="text-center text-xs text-slate-500">You will be redirected to eSewa to complete payment.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <p class="text-sm text-slate-400 flex-1">Click the green button above to pay. No transaction ID needed.</p>
                        <a href="/flight/user/dashboard.php" class="px-6 py-3 rounded-xl border border-slate-700 text-slate-300 text-sm font-medium hover:border-slate-600 hover:text-slate-200 transition">Cancel</a>
                    </div>
                </div>
            <?php else: ?>
            <!-- Payment Form for non-eSewa or eSewa demo (manual transaction ID) -->
            <form method="post" id="bookingForm" class="space-y-4">
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($paymentMethod); ?>">
                <input type="hidden" name="amount" value="<?php echo $totalAmount; ?>">

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-300">Payment Method</label>
                    <div class="rounded-xl bg-slate-950 border border-slate-700 px-4 py-3">
                        <span class="text-sky-300 font-semibold">
                            <?php
                            $methodNames = [
                                'ESEWA' => 'eSewa',
                                'MOBILE_BANKING' => 'Mobile Banking',
                                'UPI' => 'UPI',
                                'CARD' => 'Credit/Debit Card',
                                'NETBANKING' => 'Net Banking'
                            ];
                            echo $methodNames[strtoupper($paymentMethod)] ?? $paymentMethod;
                            ?>
                        </span>
                    </div>
                </div>

                <?php if (strtoupper($paymentMethod) === 'ESEWA'): ?>
                    <!-- eSewa demo mode: manual transaction ID -->
                    <div class="rounded-2xl border-2 border-[#54a754] bg-gradient-to-b from-[#6bc46b]/20 to-slate-950 overflow-hidden mb-4">
                        <div class="bg-[#54a754] px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-xl font-bold text-[#54a754]">e</div>
                            <div>
                                <h3 class="font-bold text-white text-sm">Pay with eSewa</h3>
                                <p class="text-white/90 text-xs">Safe &amp; secure payment</p>
                            </div>
                        </div>
                        <div class="p-4 space-y-4">
                            <?php if (!empty($ESEWA_LIVE)): ?>
                                <?php /* Live mode handled above; this branch is for demo only when LIVE is false */ ?>
                            <?php else: ?>
                                <div class="rounded-xl bg-amber-500/10 border border-amber-500/30 px-4 py-3 text-xs text-amber-200">
                                    <strong>Demo mode.</strong> Enable eSewa live in <code>includes/esewa_config.php</code> to redirect to eSewa. For now, pay via eSewa app and enter the transaction ID below.
                                </div>
                                <button type="button" onclick="document.getElementById('esewaDemoBtn').classList.add('hidden'); document.getElementById('esewaManualEntry').classList.remove('hidden');"
                                        id="esewaDemoBtn"
                                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#54a754] hover:bg-[#4a964a] text-white font-bold py-4 px-6 transition shadow-lg cursor-pointer">
                                    <span class="text-2xl">💰</span>
                                    <span>I've paid via eSewa – Enter Transaction ID</span>
                                </button>
                                <div id="esewaManualEntry" class="hidden space-y-2">
                                    <label class="block text-sm font-medium text-slate-300">eSewa Transaction ID <span class="text-red-400">*</span></label>
                                    <input type="text" name="transaction_id" form="bookingForm"
                                           pattern="^EP\d+"
                                           placeholder="e.g. EP1234567890"
                                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-[#54a754]"
                                           required>
                                    <p class="text-xs text-slate-500">Enter the transaction ID from your eSewa app (starts with EP).</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (empty($ESEWA_LIVE)): ?>
                    <p class="text-xs text-slate-500 mb-2">Pay via eSewa app, then click the green button above and enter your transaction ID to confirm.</p>
                    <?php endif; ?>
                <?php elseif (strtoupper($paymentMethod) === 'MOBILE_BANKING'): ?>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-300">
                            Mobile Banking Transaction Reference
                        </label>
                        <input type="text" name="transaction_id" required
                               pattern="^\d{10,}"
                               placeholder="Enter transaction reference number"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent">
                        <p class="text-xs text-slate-500">
                            Enter the transaction reference number from your mobile banking app (usually 10+ digits).
                        </p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-300">
                            Transaction ID / Reference Number
                        </label>
                        <input type="text" name="transaction_id" required
                               placeholder="Enter transaction ID"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent">
                    </div>
                <?php endif; ?>

                <div class="rounded-xl bg-amber-500/10 border border-amber-500/30 p-4">
                    <p class="text-xs text-amber-300 mb-2">
                        <strong>Note:</strong> In a production environment, this would integrate with real payment gateways.
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 text-white px-6 py-3 text-sm font-semibold hover:bg-emerald-600 transition">
                        <span>💳</span>
                        <span>Confirm Payment</span>
                    </button>
                    <a href="/flight/user/dashboard.php"
                       class="px-6 py-3 rounded-xl border border-slate-700 text-slate-300 text-sm font-medium hover:border-slate-600 hover:text-slate-200 transition">
                        Cancel
                    </a>
                </div>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
fw_footer();
?>
