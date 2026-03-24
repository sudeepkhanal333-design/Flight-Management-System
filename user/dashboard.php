<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'user') {
    header('Location: /flight/login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Passenger';
$userId   = (int)($_SESSION['user_id'] ?? 0);

/**
 * Get available seats for a flight (if seat data is present in DB).
 * Returns null if calculation is not possible (e.g. missing columns).
 */
function fw_get_available_seats(mysqli $conn, int $flightId): ?int
{
    // Check if total_seats column exists first
    $checkResult = @$conn->query("SHOW COLUMNS FROM flights WHERE Field = 'total_seats'");
    if (!$checkResult || $checkResult->num_rows === 0) {
        // Column doesn't exist - return null
        return null;
    }
    
    // Now safely query with total_seats column
    $sql = "SELECT 
                COALESCE(f.total_seats, 180) - COALESCE(SUM(CASE WHEN b.status != 'CANCELLED' THEN b.seats ELSE 0 END), 0) AS available_seats
            FROM flights f
            LEFT JOIN bookings b ON b.flight_id = f.id
            WHERE f.id = ?
            GROUP BY f.id, f.total_seats";

    $stmt = @$conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $flightId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row || $row['available_seats'] === null) {
        return null;
    }

    $available = (int)$row['available_seats'];
    return $available >= 0 ? $available : 0;
}

// Handle booking & booking management
$bookingError = '';
$bookingSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'book_flight') {
        $flightId = (int)($_POST['flight_id'] ?? 0);
        $seats = (int)($_POST['seats'] ?? 1);
        $travelClass = $_POST['class'] ?? 'ECONOMY';
        $paymentMethod = $_POST['payment_method'] ?? 'UPI';

        if ($flightId <= 0 || $seats < 1) {
            $bookingError = 'Please select a valid flight and number of seats.';
        } else {
            // Check flight exists and is bookable
            $stmt = $conn->prepare("SELECT id, flight_code, origin, destination, departure_time, status, base_fare FROM flights WHERE id = ?");
            $stmt->bind_param('i', $flightId);
            $stmt->execute();
            $result = $stmt->get_result();
            $flight = $result->fetch_assoc();
            $stmt->close();

            if (!$flight) {
                $bookingError = 'Selected flight could not be found.';
            } elseif ($flight['status'] === 'CANCELLED') {
                $bookingError = 'This flight is cancelled and cannot be booked.';
            } elseif (strtotime($flight['departure_time']) < time()) {
                $bookingError = 'Cannot book flights that have already departed.';
            } else {
                // Check seat availability (if seat data exists)
                $availableSeats = fw_get_available_seats($conn, $flightId);
                if ($availableSeats !== null && $seats > $availableSeats) {
                    $bookingError = 'Not enough seats available on this flight. Available seats: ' . (int)$availableSeats . '.';
                } else {
                // Check if user already has a booking for this flight
                $checkBooking = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND flight_id = ? AND status != 'CANCELLED'");
                $checkBooking->bind_param('ii', $userId, $flightId);
                $checkBooking->execute();
                if ($checkBooking->get_result()->num_rows > 0) {
                    $bookingError = 'You already have an active booking for this flight.';
                    $checkBooking->close();
                } else {
                    $checkBooking->close();
                    
                    // Generate unique PNR
                    $pnr = '';
                    $pnrExists = true;
                    while ($pnrExists) {
                        $pnr = 'FW' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
                        $checkPnr = $conn->prepare("SELECT id FROM bookings WHERE pnr = ?");
                        $checkPnr->bind_param('s', $pnr);
                        $checkPnr->execute();
                        $pnrExists = $checkPnr->get_result()->num_rows > 0;
                        $checkPnr->close();
                    }

                    // Check if payment_method column exists
                    $checkPaymentCol = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
                    $hasPaymentCol = ($checkPaymentCol && $checkPaymentCol->num_rows > 0);
                    
                    // Create booking as PENDING first, then redirect to payment
                    if ($hasPaymentCol) {
                        $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, pnr, seats, status, payment_method) VALUES (?, ?, ?, ?, 'PENDING', ?)");
                        $stmt->bind_param('iisss', $userId, $flightId, $pnr, $seats, $paymentMethod);
                    } else {
                        // Fallback: create booking without payment_method column
                        $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, pnr, seats, status) VALUES (?, ?, ?, ?, 'CONFIRMED')");
                        $stmt->bind_param('iisi', $userId, $flightId, $pnr, $seats);
                    }
                    
                    if ($stmt->execute()) {
                        $newBookingId = $conn->insert_id;
                        $stmt->close();
                        
                        if ($hasPaymentCol) {
                            // Redirect to payment page
                            header('Location: /flight/user/payment.php?booking_id=' . $newBookingId . '&method=' . urlencode($paymentMethod));
                            exit;
                        } else {
                            // If payment columns don't exist, confirm booking directly
                            $bookingSuccess = '🎉 Booking confirmed successfully! Your PNR is <strong>' . htmlspecialchars($pnr) . '</strong> for ' . htmlspecialchars($travelClass) . ' class. '
                                . 'Note: Payment columns not set up. Run migration to enable payment processing.';
                            $_GET = [];
                        }
                    } else {
                        $bookingError = 'There was a problem saving your booking. Please try again.';
                        $stmt->close();
                    }
                }
                }
            }
        }
    } elseif ($action === 'cancel_booking') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        if ($bookingId > 0) {
            // Only allow cancelling own upcoming bookings
            $stmt = $conn->prepare("UPDATE bookings b 
                                    JOIN flights f ON b.flight_id = f.id 
                                    SET b.status = 'CANCELLED' 
                                    WHERE b.id = ? AND b.user_id = ? AND f.departure_time > NOW() AND b.status = 'CONFIRMED'");
            $stmt->bind_param('ii', $bookingId, $userId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $bookingSuccess = 'Your booking has been cancelled.';
            } else {
                $bookingError = 'Unable to cancel this booking. It may already be cancelled or departed.';
            }
            $stmt->close();
        }
    }
}

// Handle flight search
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = trim($_GET['date'] ?? '');
$passengers = (int)($_GET['passengers'] ?? 1);
if ($passengers < 1) {
    $passengers = 1;
}

$searchResults = [];

// Aggregates for available planes & seats (for current search results)
$availableFlightsCount = 0;
$totalAvailableSeatsForSearch = 0;

if ($from !== '' || $to !== '' || $date !== '') {
    // Build dynamic WHERE clause
    $where = [];
    $params = [];
    $types = '';

    if ($from !== '') {
        $where[] = 'origin LIKE ?';
        $params[] = '%' . $from . '%';
        $types .= 's';
    }
    if ($to !== '') {
        $where[] = 'destination LIKE ?';
        $params[] = '%' . $to . '%';
        $types .= 's';
    }
    if ($date !== '') {
        $where[] = 'DATE(departure_time) = ?';
        $params[] = $date;
        $types .= 's';
    }

    $sql = 'SELECT id, flight_code, origin, destination, departure_time, arrival_time, status, base_fare 
            FROM flights';
    if (count($where) > 0) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY departure_time ASC';

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
    $stmt->close();

    // Pre-calculate available seats per flight and global aggregates
    foreach ($searchResults as &$flightRow) {
        $availableSeats = fw_get_available_seats($conn, (int)$flightRow['id']);
        $flightRow['available_seats'] = $availableSeats;

        if ($availableSeats !== null && $availableSeats > 0 && $flightRow['status'] !== 'CANCELLED') {
            $availableFlightsCount++;
            $totalAvailableSeatsForSearch += (int)$availableSeats;
        }
    }
    unset($flightRow);
}

// Load booking stats and upcoming bookings for this passenger
$totalUpcoming = $totalCompleted = $totalCancelled = 0;
$upcomingBookings = [];

// Stats
$stmt = $conn->prepare("SELECT status, COUNT(*) AS c FROM bookings WHERE user_id = ? GROUP BY status");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if ($row['status'] === 'CONFIRMED') {
        $totalUpcoming += (int)$row['c'];
    } elseif ($row['status'] === 'PENDING') {
        $totalUpcoming += (int)$row['c'];
    } elseif ($row['status'] === 'CANCELLED') {
        $totalCancelled += (int)$row['c'];
    }
}
$stmt->close();

// Completed = all non-cancelled bookings with departure in the past
$stmt = $conn->prepare("SELECT COUNT(*) AS c 
                        FROM bookings b 
                        JOIN flights f ON b.flight_id = f.id 
                        WHERE b.user_id = ? AND b.status <> 'CANCELLED' AND f.departure_time <= NOW()");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$totalCompleted = (int)($res['c'] ?? 0);
$stmt->close();

// Upcoming bookings list
$stmt = $conn->prepare("SELECT b.id, b.pnr, b.seats, b.status, f.flight_code, f.origin, f.destination, f.departure_time 
                        FROM bookings b 
                        JOIN flights f ON b.flight_id = f.id 
                        WHERE b.user_id = ? AND f.departure_time > NOW() 
                        ORDER BY f.departure_time ASC 
                        LIMIT 5");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $upcomingBookings[] = $row;
}
$stmt->close();

// Check if payment columns exist
$hasPaymentColumns = false;
$checkColumns = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
if ($checkColumns && $checkColumns->num_rows > 0) {
    $hasPaymentColumns = true;
}

// Get ALL booked flights (all statuses - upcoming, completed, cancelled)
$allBookedFlights = [];
if ($hasPaymentColumns) {
    $stmt = $conn->prepare("SELECT b.id, b.pnr, b.seats, b.status, b.booked_at,
                                   b.payment_method, b.payment_status, b.payment_transaction_id, b.payment_amount, b.payment_date,
                                   f.flight_code, f.origin, f.destination, f.departure_time, f.arrival_time, f.base_fare, f.status as flight_status
                            FROM bookings b 
                            JOIN flights f ON b.flight_id = f.id 
                            WHERE b.user_id = ? 
                            ORDER BY f.departure_time DESC");
} else {
    // Fallback query without payment columns
    $stmt = $conn->prepare("SELECT b.id, b.pnr, b.seats, b.status, b.booked_at,
                                   NULL as payment_method, NULL as payment_status, NULL as payment_transaction_id, NULL as payment_amount, NULL as payment_date,
                                   f.flight_code, f.origin, f.destination, f.departure_time, f.arrival_time, f.base_fare, f.status as flight_status
                            FROM bookings b 
                            JOIN flights f ON b.flight_id = f.id 
                            WHERE b.user_id = ? 
                            ORDER BY f.departure_time DESC");
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $allBookedFlights[] = $row;
}
$stmt->close();

// Handle payment notification (transaction success / failure)
$paymentNotification = null; // 'success' | 'failed' | 'error'
$paymentNotificationMessage = '';
if (isset($_GET['payment_success']) && $_GET['payment_success'] == '1') {
    $paymentNotification = 'success';
    $paymentNotificationMessage = 'Your payment was successful! Your booking is now confirmed.';
    $bookingSuccess = '🎉 Payment completed successfully! Your booking is now confirmed.';
} elseif (isset($_GET['payment']) && $_GET['payment'] === 'failed') {
    $paymentNotification = 'failed';
    $paymentNotificationMessage = 'Payment could not be completed. Please try again or use another method.';
} elseif (isset($_GET['payment']) && $_GET['payment'] === 'error') {
    $paymentNotification = 'error';
    $paymentNotificationMessage = 'Something went wrong. Please try again or contact support.';
}

fw_header("FlyWings - User Dashboard");
?>

<!-- Transaction success / failure notification toast -->
<?php if ($paymentNotification): ?>
<div id="paymentNotification" role="alert" class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] max-w-md w-full mx-4 transition-all duration-300 ease-out" style="animation: paymentToastIn 0.35s ease-out;">
    <div class="<?php
        echo $paymentNotification === 'success'
            ? 'bg-emerald-500/95 border-emerald-400 text-white shadow-lg shadow-emerald-500/30'
            : ($paymentNotification === 'failed' ? 'bg-amber-500/95 border-amber-400 text-white shadow-lg shadow-amber-500/30' : 'bg-red-500/95 border-red-400 text-white shadow-lg shadow-red-500/30');
    ?> border rounded-2xl px-5 py-4 flex items-center gap-4">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-2xl">
            <?php if ($paymentNotification === 'success'): ?>✓<?php elseif ($paymentNotification === 'failed'): ?>⚠<?php else: ?>✕<?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm">
                <?php echo $paymentNotification === 'success' ? 'Transaction Successful' : ($paymentNotification === 'failed' ? 'Payment Incomplete' : 'Error'); ?>
            </p>
            <p class="text-sm opacity-95 mt-0.5"><?php echo htmlspecialchars($paymentNotificationMessage); ?></p>
        </div>
        <button type="button" onclick="dismissPaymentNotification()" class="flex-shrink-0 w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-lg leading-none transition" aria-label="Dismiss">×</button>
    </div>
</div>
<?php endif; ?>

<style>
@keyframes paymentToastIn {
    from { opacity: 0; transform: translate(-50%, -12px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}
</style>

<section class="max-w-6xl mx-auto px-4 py-10 md:py-14">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <p class="text-xs text-slate-400 mb-1">Welcome back,</p>
            <h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($userName); ?></h1>
            <p class="text-sm text-slate-300 mt-1">
                Use your FlyWings dashboard to search flights, manage bookings and view your travel history.
            </p>
        </div>
        <a href="/flight/logout.php"
           class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-slate-700 hover:border-accent hover:text-accent transition">
            Logout
        </a>
    </div>

    <div class="grid md:grid-cols-[2fr,1.5fr] gap-6">
        <!-- Flight search / booking -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5">
            <h2 class="text-sm font-semibold mb-1">Search & Book Flights</h2>

            <?php if ($bookingError): ?>
                <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-[11px] text-red-300">
                    <?php echo $bookingError; ?>
                </div>
            <?php endif; ?>
            <?php if ($bookingSuccess): ?>
                <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-[11px] text-emerald-300">
                    <?php echo $bookingSuccess; ?>
                </div>
            <?php endif; ?>

            <form method="get" class="grid md:grid-cols-2 gap-3 text-xs">
                <div class="space-y-1">
                    <label class="text-slate-300">From</label>
                    <input type="text" name="from"
                           value="<?php echo htmlspecialchars($from); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-accent"
                           placeholder="Mumbai (BOM)">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">To</label>
                    <input type="text" name="to"
                           value="<?php echo htmlspecialchars($to); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-accent"
                           placeholder="Dubai (DXB)">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">Departure Date</label>
                    <input type="date" name="date"
                           value="<?php echo htmlspecialchars($date); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-accent">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">Passengers</label>
                    <input type="number" name="passengers" min="1"
                           value="<?php echo htmlspecialchars($passengers); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-accent">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 text-sm font-medium px-4 py-2 hover:bg-sky-400 transition mt-1">
                        Search Flights
                    </button>
                </div>
            </form>

            <?php if ($from !== '' || $to !== '' || $date !== ''): ?>
                <div class="mt-4 rounded-xl bg-slate-950 border border-slate-800 p-3 text-xs overflow-x-auto">
                    <p class="text-[11px] text-slate-400 mb-2">
                        Showing results for your search criteria.
                        Total flights found:
                        <span class="text-sky-300 font-semibold"><?php echo count($searchResults); ?></span>
                        · Available planes to book:
                        <span class="text-emerald-300 font-semibold"><?php echo (int)$availableFlightsCount; ?></span>
                        · Total available seats:
                        <span class="text-amber-300 font-semibold">
                            <?php echo $totalAvailableSeatsForSearch > 0 ? (int)$totalAvailableSeatsForSearch : 0; ?>
                        </span>
                    </p>
                    <?php if (count($searchResults) === 0): ?>
                        <p class="text-slate-500">No flights match your search. Try changing origin, destination or date.</p>
                    <?php else: ?>
                        <table class="w-full text-left border-collapse">
                            <thead class="text-slate-400">
                            <tr>
                                <th class="pb-2 pr-3">Code</th>
                                <th class="pb-2 pr-3">Route</th>
                                <th class="pb-2 pr-3">Departure</th>
                                <th class="pb-2 pr-3">Arrival</th>
                                <th class="pb-2 pr-3">Fares (₹)</th>
                                <th class="pb-2 pr-3">Available Seats</th>
                                <th class="pb-2 pr-3">Company</th>
                                <th class="pb-2 pr-3">Status</th>
                                <th class="pb-2 pr-3">Book</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($searchResults as $flight): ?>
                                <?php
                                // Use pre-calculated available seats for this flight
                                $availableSeats = $flight['available_seats'] ?? null;
                                ?>
                                <tr class="border-t border-slate-800 align-top">
                                    <td class="py-2 pr-3 text-xs font-semibold"><?php echo htmlspecialchars($flight['flight_code']); ?></td>
                                    <td class="py-2 pr-3 text-xs">
                                        <?php echo htmlspecialchars($flight['origin']); ?>
                                        →
                                        <?php echo htmlspecialchars($flight['destination']); ?>
                                    </td>
                                    <td class="py-2 pr-3 text-xs">
                                        <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($flight['departure_time']))); ?>
                                    </td>
                                    <td class="py-2 pr-3 text-xs">
                                        <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($flight['arrival_time']))); ?>
                                    </td>
                                    <td class="py-2 pr-3 text-xs">
                                        <?php
                                        $economyFare = (float)$flight['base_fare'];
                                        $businessFare = $economyFare * 1.6; // simple multiplier demo
                                        ?>
                                        <div>Economy: Rs<?php echo number_format($economyFare, 2); ?></div>
                                        <div class="text-slate-400">Business: Rs.<?php echo number_format($businessFare, 2); ?></div>
                                    </td>
                                        <td class="py-2 pr-3 text-xs">
                                            <?php if ($availableSeats === null): ?>
                                                <span class="text-slate-500 text-[11px]">N/A</span>
                                            <?php elseif ($availableSeats <= 0): ?>
                                                <span class="text-red-300 text-[11px] font-semibold">Full</span>
                                            <?php else: ?>
                                                <span class="text-emerald-300 font-semibold"><?php echo (int)$availableSeats; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    <td class="py-2 pr-3 text-[11px] text-slate-300">
                                        <?php
                                        // Simple company mapping based on code prefix
                                        $company = 'FlyWings';
                                        if (str_starts_with($flight['flight_code'], 'FW')) {
                                            $company = 'FlyWings';
                                        } elseif (str_starts_with($flight['flight_code'], 'SJ')) {
                                            $company = 'SkyJet Airways';
                                        } elseif (str_starts_with($flight['flight_code'], 'BA')) {
                                            $company = 'BlueAir Lines';
                                        }
                                        echo htmlspecialchars($company);
                                        ?>
                                    </td>
                                    <td class="py-2 pr-3 text-xs">
                                        <?php
                                        $statusClass = 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30';
                                        if ($flight['status'] === 'DELAYED') {
                                            $statusClass = 'bg-amber-500/10 text-amber-300 border-amber-500/30';
                                        } elseif ($flight['status'] === 'CANCELLED') {
                                            $statusClass = 'bg-red-500/10 text-red-300 border-red-500/30';
                                        }
                                        ?>
                                        <span class="px-2 py-0.5 rounded-full border <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($flight['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 pr-3 text-xs">
                                        <?php if ($flight['status'] === 'CANCELLED' || ($availableSeats !== null && $availableSeats <= 0)): ?>
                                            <span class="text-[10px] text-slate-500">Not Available</span>
                                        <?php else: ?>
                                            <button onclick="openBookingModal(<?php echo htmlspecialchars(json_encode([
                                                'id' => $flight['id'],
                                                'code' => $flight['flight_code'],
                                                'origin' => $flight['origin'],
                                                'destination' => $flight['destination'],
                                                'departure' => date('d M Y, H:i', strtotime($flight['departure_time'])),
                                                'arrival' => date('d M Y, H:i', strtotime($flight['arrival_time'])),
                                                'economy_fare' => (float)$flight['base_fare'],
                                                'business_fare' => (float)$flight['base_fare'] * 1.6,
                                                'passengers' => $passengers
                                            ])); ?>)"
                                                    class="inline-flex items-center gap-1 rounded-xl bg-accent text-slate-950 px-3 py-1.5 text-[11px] font-semibold hover:bg-sky-400 transition shadow-lg shadow-sky-500/20">
                                                <span>🎫</span>
                                                <span>Book Now</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-[11px] text-slate-500 mt-3">
                    Enter origin, destination and optionally date, then click "Search Flights" to see matching options
                    from the FlyWings database.
                </p>
            <?php endif; ?>
        </div>

        <!-- Quick stats & upcoming trips -->
        <div class="space-y-3">
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
                <h2 class="text-sm font-semibold mb-3">Your booking summary</h2>
                <div class="grid grid-cols-3 gap-3 text-xs">
                    <div class="rounded-xl bg-slate-950 border border-slate-800 p-3">
                        <p class="text-[11px] text-slate-400 mb-1">Upcoming</p>
                        <p class="text-lg font-semibold"><?php echo (int)$totalUpcoming; ?></p>
                    </div>
                    <div class="rounded-xl bg-slate-950 border border-slate-800 p-3">
                        <p class="text-[11px] text-slate-400 mb-1">Completed</p>
                        <p class="text-lg font-semibold"><?php echo (int)$totalCompleted; ?></p>
                    </div>
                    <div class="rounded-xl bg-slate-950 border border-slate-800 p-3">
                        <p class="text-[11px] text-slate-400 mb-1">Cancelled</p>
                        <p class="text-lg font-semibold"><?php echo (int)$totalCancelled; ?></p>
                    </div>
                </div>
                <p class="mt-3 text-[11px] text-slate-500">
                    These numbers are calculated from your real bookings in the system.
                </p>
            </div>

            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
                <h2 class="text-sm font-semibold mb-2">Your upcoming trips</h2>
                <?php if (count($upcomingBookings) === 0): ?>
                    <p class="text-[11px] text-slate-500">
                        You don’t have any upcoming trips yet. Search for a flight on the left to get started.
                    </p>
                <?php else: ?>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($upcomingBookings as $b): ?>
                            <div class="rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-slate-100">
                                        <?php echo htmlspecialchars($b['flight_code']); ?>
                                        · <?php echo htmlspecialchars($b['origin'] . ' → ' . $b['destination']); ?>
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        Departure:
                                        <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($b['departure_time']))); ?>
                                        · Seats: <?php echo (int)$b['seats']; ?>
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        PNR: <span class="text-sky-300 font-mono"><?php echo htmlspecialchars($b['pnr']); ?></span>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <?php
                                    $badgeClass = 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30';
                                    if ($b['status'] === 'PENDING') {
                                        $badgeClass = 'bg-amber-500/10 text-amber-300 border-amber-500/30';
                                    } elseif ($b['status'] === 'CANCELLED') {
                                        $badgeClass = 'bg-red-500/10 text-red-300 border-red-500/30';
                                    }
                                    ?>
                                    <span class="px-2 py-0.5 rounded-full border text-[11px] <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($b['status']); ?>
                                    </span>
                                    <?php if ($b['status'] === 'CONFIRMED'): ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="cancel_booking">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                                            <button type="submit"
                                                    class="mt-1 inline-flex items-center gap-1 rounded-full bg-slate-800 text-slate-200 px-3 py-1 text-[11px] hover:bg-red-500 hover:text-white transition">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- My Booked Flights Section -->
    <div class="mt-8 bg-slate-900/70 border border-slate-800 rounded-2xl p-5 md:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg md:text-xl font-semibold mb-1">✈️ My Booked Flights</h2>
                <p class="text-xs text-slate-400">All flights you have booked - upcoming, completed, and cancelled</p>
            </div>
            <span class="text-xs text-slate-400 bg-slate-800 px-3 py-1 rounded-full">
                Total: <?php echo count($allBookedFlights); ?>
            </span>
        </div>

        <?php if (count($allBookedFlights) === 0): ?>
            <div class="text-center py-12">
                <div class="w-16 h-16 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">✈️</span>
                </div>
                <p class="text-sm text-slate-400 mb-2">You haven't booked any flights yet</p>
                <p class="text-xs text-slate-500">Search and book flights using the form above to get started</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/50">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="text-slate-300 bg-slate-900/50 border-b border-slate-700">
                    <tr>
                        <th class="pb-3 pr-4 font-semibold text-sky-300">PNR</th>
                        <th class="pb-3 pr-4 font-semibold">Flight</th>
                        <th class="pb-3 pr-4 font-semibold">Route</th>
                        <th class="pb-3 pr-4 font-semibold">Departure</th>
                        <th class="pb-3 pr-4 font-semibold">Arrival</th>
                        <th class="pb-3 pr-4 font-semibold">Seats</th>
                        <th class="pb-3 pr-4 font-semibold">Amount</th>
                        <th class="pb-3 pr-4 font-semibold text-emerald-300">💳 Payment</th>
                        <th class="pb-3 pr-4 font-semibold">Status</th>
                        <th class="pb-3 font-semibold">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allBookedFlights as $booking): ?>
                        <?php
                        $isUpcoming = strtotime($booking['departure_time']) > time();
                        $isCompleted = !$isUpcoming && $booking['status'] !== 'CANCELLED';
                        $isCancelled = $booking['status'] === 'CANCELLED';
                        $totalAmount = (float)$booking['base_fare'] * (int)$booking['seats'];
                        ?>
                        <tr class="border-b border-slate-800/50 hover:bg-slate-800/40 transition-all duration-200 <?php echo $isCancelled ? 'opacity-60' : ''; ?>">
                            <td class="py-3 pr-4">
                                <span class="font-mono font-semibold text-sky-300 bg-sky-500/10 px-2 py-1 rounded border border-sky-500/20"><?php echo htmlspecialchars($booking['pnr']); ?></span>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-slate-200"><?php echo htmlspecialchars($booking['flight_code']); ?></div>
                                <div class="text-[10px] text-slate-500">
                                    <?php
                                    $company = 'FlyWings';
                                    if (str_starts_with($booking['flight_code'], 'FW')) {
                                        $company = 'FlyWings';
                                    } elseif (str_starts_with($booking['flight_code'], 'SJ')) {
                                        $company = 'SkyJet Airways';
                                    } elseif (str_starts_with($booking['flight_code'], 'BA')) {
                                        $company = 'BlueAir Lines';
                                    }
                                    echo htmlspecialchars($company);
                                    ?>
                                </div>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="text-slate-200"><?php echo htmlspecialchars($booking['origin']); ?></div>
                                <div class="text-slate-400 text-[10px]">→ <?php echo htmlspecialchars($booking['destination']); ?></div>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="text-slate-200"><?php echo htmlspecialchars(date('d M Y', strtotime($booking['departure_time']))); ?></div>
                                <div class="text-slate-400 text-[10px]"><?php echo htmlspecialchars(date('H:i', strtotime($booking['departure_time']))); ?></div>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="text-slate-200"><?php echo htmlspecialchars(date('d M Y', strtotime($booking['arrival_time']))); ?></div>
                                <div class="text-slate-400 text-[10px]"><?php echo htmlspecialchars(date('H:i', strtotime($booking['arrival_time']))); ?></div>
                            </td>
                            <td class="py-3 pr-4 text-slate-300">
                                <?php echo (int)$booking['seats']; ?> seat<?php echo (int)$booking['seats'] > 1 ? 's' : ''; ?>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="text-emerald-300 font-bold text-sm">Rs.<?php echo number_format($totalAmount, 2); ?></div>
                                <div class="text-slate-400 text-[10px]">Rs.<?php echo number_format((float)$booking['base_fare'], 2); ?>/seat</div>
                            </td>
                            <td class="py-3 pr-4">
                                <?php
                                $paymentMethod = $booking['payment_method'] ?? null;
                                $paymentStatus = $booking['payment_status'] ?? null;
                                $paymentTxnId = $booking['payment_transaction_id'] ?? null;
                                
                                if ($paymentStatus === 'COMPLETED') {
                                    $methodNames = [
                                        'ESEWA' => '💰 eSewa',
                                        'MOBILE_BANKING' => '📱 Mobile Banking',
                                        'UPI' => '💳 UPI',
                                        'CARD' => '💳 Card',
                                        'NETBANKING' => '🏦 Net Banking'
                                    ];
                                    $methodName = $methodNames[strtoupper($paymentMethod)] ?? $paymentMethod;
                                    echo '<div class="flex flex-col gap-1">';
                                    echo '<div class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-semibold w-fit">';
                                    echo '<span>✓</span><span>Paid</span></div>';
                                    echo '<div class="text-slate-300 text-[10px] font-medium">' . htmlspecialchars($methodName) . '</div>';
                                    if ($paymentTxnId) {
                                        echo '<div class="text-slate-500 text-[9px] font-mono bg-slate-950 px-2 py-0.5 rounded w-fit">' . htmlspecialchars(substr($paymentTxnId, 0, 15)) . '...</div>';
                                    }
                                    echo '</div>';
                                } elseif ($booking['status'] === 'PENDING' && !$paymentStatus) {
                                    $payMethod = $paymentMethod ?? 'ESEWA';
                                    $bid = (int)$booking['id'];
                                    echo '<div class="flex flex-col gap-1.5">';
                                    echo '<a href="/flight/user/payment.php?booking_id=' . $bid . '&method=ESEWA"';
                                    echo ' class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#54a754]/20 text-[#6bc46b] border border-[#54a754]/50 hover:bg-[#54a754]/30 text-[10px] font-semibold transition-all w-fit">';
                                    echo '<span>💰</span><span>Pay with eSewa</span></a>';
                                    echo '<a href="/flight/user/payment.php?booking_id=' . $bid . '&method=' . urlencode($payMethod) . '"';
                                    echo ' class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-slate-700/50 text-slate-300 border border-slate-600 hover:bg-slate-600/50 text-[9px] transition-all w-fit">';
                                    echo '<span>💳</span><span>Other payment</span></a>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-medium">';
                                    echo '<span>⏳</span><span>Pending</span></div>';
                                }
                                ?>
                            </td>
                            <td class="py-3 pr-4">
                                <?php
                                $statusClass = 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30';
                                $statusText = $booking['status'];
                                
                                if ($isCompleted && $booking['status'] === 'CONFIRMED') {
                                    $statusClass = 'bg-blue-500/10 text-blue-300 border-blue-500/30';
                                    $statusText = 'COMPLETED';
                                } elseif ($booking['status'] === 'PENDING') {
                                    $statusClass = 'bg-amber-500/10 text-amber-300 border-amber-500/30';
                                } elseif ($booking['status'] === 'CANCELLED') {
                                    $statusClass = 'bg-red-500/10 text-red-300 border-red-500/30';
                                }
                                ?>
                                <span class="px-2 py-1 rounded-full border text-[10px] <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($statusText); ?>
                                </span>
                                <?php if ($booking['flight_status'] === 'DELAYED' && !$isCancelled): ?>
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 rounded-full border bg-amber-500/10 text-amber-300 border-amber-500/30 text-[10px]">
                                            Flight DELAYED
                                        </span>
                                    </div>
                                <?php elseif ($booking['flight_status'] === 'CANCELLED' && !$isCancelled): ?>
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 rounded-full border bg-red-500/10 text-red-300 border-red-500/30 text-[10px]">
                                            Flight CANCELLED
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3">
                                <?php if ($isUpcoming && $booking['status'] === 'CONFIRMED'): ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                        <button type="submit"
                                                onclick="return confirm('Are you sure you want to cancel this booking?');"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-red-500/20 text-red-300 border border-red-500/30 hover:bg-red-500/30 text-[10px] transition">
                                            <span>❌</span>
                                            <span>Cancel</span>
                                        </button>
                                    </form>
                                <?php elseif ($isCompleted): ?>
                                    <span class="text-[10px] text-slate-500">Completed</span>
                                <?php elseif ($isCancelled): ?>
                                    <span class="text-[10px] text-slate-500">Cancelled</span>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                <p>Booked on: <?php echo count($allBookedFlights) > 0 ? htmlspecialchars(date('d M Y', strtotime($allBookedFlights[0]['booked_at']))) : 'N/A'; ?></p>
                <p>Showing all <?php echo count($allBookedFlights); ?> booking<?php echo count($allBookedFlights) > 1 ? 's' : ''; ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Booking Modal -->
<div id="bookingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Confirm Your Booking</h3>
            <button onclick="closeBookingModal()" class="text-slate-400 hover:text-slate-200 transition">
                <span class="text-xl">×</span>
            </button>
        </div>

        <div id="bookingFlightInfo" class="mb-4 p-4 rounded-xl bg-slate-950 border border-slate-800">
            <!-- Flight info will be populated by JavaScript -->
        </div>

        <form method="post" id="bookingForm" class="space-y-4">
            <input type="hidden" name="action" value="book_flight">
            <input type="hidden" name="flight_id" id="modal_flight_id">

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-300">Travel Class</label>
                <select name="class" id="modal_class" required
                        class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent"
                        onchange="updateBookingPrice()">
                    <option value="ECONOMY">Economy Class</option>
                    <option value="BUSINESS">Business Class</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-300">Number of Seats</label>
                <input type="number" name="seats" id="modal_seats" min="1" required
                       value="<?php echo (int)$passengers; ?>"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent"
                       onchange="updateBookingPrice()">
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-300">Payment Details</label>
                <p class="text-xs text-slate-400 mb-1">
                    Select your preferred payment method. You'll be redirected to complete payment.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-200">
                    <label class="flex items-center gap-2 rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 cursor-pointer hover:border-accent transition">
                        <input type="radio" name="payment_method" value="ESEWA" class="accent-sky-500" checked>
                        <span>💰 eSewa</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 cursor-pointer hover:border-accent transition">
                        <input type="radio" name="payment_method" value="MOBILE_BANKING" class="accent-sky-500">
                        <span>📱 Mobile Banking</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 cursor-pointer hover:border-accent transition">
                        <input type="radio" name="payment_method" value="UPI" class="accent-sky-500">
                        <span>💳 UPI</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 cursor-pointer hover:border-accent transition">
                        <input type="radio" name="payment_method" value="CARD" class="accent-sky-500">
                        <span>💳 Credit / Debit Card</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 cursor-pointer hover:border-accent transition">
                        <input type="radio" name="payment_method" value="NETBANKING" class="accent-sky-500">
                        <span>🏦 Net Banking</span>
                    </label>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/30">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Total Amount:</span>
                    <span id="totalAmount" class="text-xl font-bold text-sky-300">₹0.00</span>
                </div>
                <p class="text-xs text-slate-400 mt-1" id="priceBreakdown"></p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-accent text-slate-950 px-6 py-3 text-sm font-semibold hover:bg-sky-400 transition shadow-lg shadow-sky-500/20">
                    <span>💾</span>
                    <span>Confirm Booking</span>
                </button>
                <button type="button" onclick="closeBookingModal()"
                        class="px-6 py-3 rounded-xl border border-slate-700 text-slate-300 text-sm font-medium hover:border-slate-600 hover:text-slate-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentFlightData = null;

function openBookingModal(flightData) {
    currentFlightData = flightData;
    const modal = document.getElementById('bookingModal');
    const flightInfo = document.getElementById('bookingFlightInfo');
    const form = document.getElementById('bookingForm');
    
    // Populate flight info
    flightInfo.innerHTML = `
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-lg font-bold text-sky-300">${flightData.code}</span>
                <span class="text-xs text-slate-400">${flightData.origin} → ${flightData.destination}</span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <p class="text-slate-400 mb-1">Departure</p>
                    <p class="text-slate-200">${flightData.departure}</p>
                </div>
                <div>
                    <p class="text-slate-400 mb-1">Arrival</p>
                    <p class="text-slate-200">${flightData.arrival}</p>
                </div>
            </div>
        </div>
    `;
    
    // Set form values
    document.getElementById('modal_flight_id').value = flightData.id;
    document.getElementById('modal_seats').value = flightData.passengers || 1;
    document.getElementById('modal_class').value = 'ECONOMY';
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Update price
    updateBookingPrice();
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
    currentFlightData = null;
}

function updateBookingPrice() {
    if (!currentFlightData) return;
    
    const classSelect = document.getElementById('modal_class');
    const seatsInput = document.getElementById('modal_seats');
    const totalAmount = document.getElementById('totalAmount');
    const priceBreakdown = document.getElementById('priceBreakdown');
    
    const selectedClass = classSelect.value;
    const seats = parseInt(seatsInput.value) || 1;
    const farePerSeat = selectedClass === 'BUSINESS' ? currentFlightData.business_fare : currentFlightData.economy_fare;
    const total = farePerSeat * seats;
    
    totalAmount.textContent = 'Rs.' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    priceBreakdown.textContent = `${seats} seat${seats > 1 ? 's' : ''} × Rs.${farePerSeat.toLocaleString('en-IN', {minimumFractionDigits: 2})} (${selectedClass})`;
}

// Close modal on outside click
document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookingModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBookingModal();
    }
});

// Form submission confirmation
var bookingFormEl = document.getElementById('bookingForm');
if (bookingFormEl) {
    bookingFormEl.addEventListener('submit', function(e) {
        const seats = parseInt(document.getElementById('modal_seats').value);
        const selectedClass = document.getElementById('modal_class').value;
        const total = document.getElementById('totalAmount').textContent;
        const paymentMethodInput = document.querySelector('input[name="payment_method"]:checked');
        const paymentMethod = paymentMethodInput ? paymentMethodInput.value : 'Not selected';
        
        if (!confirm(`Confirm booking for ${seats} seat${seats > 1 ? 's' : ''} in ${selectedClass} class?\nPayment method: ${paymentMethod}\nTotal: ${total}`)) {
            e.preventDefault();
            return false;
        }
    });
}

// Payment notification: dismiss and clean URL
function dismissPaymentNotification() {
    var el = document.getElementById('paymentNotification');
    if (!el) return;
    el.style.opacity = '0';
    el.style.transform = 'translate(-50%, -12px)';
    el.style.pointerEvents = 'none';
    setTimeout(function() {
        el.remove();
        var url = new URL(window.location.href);
        url.searchParams.delete('payment_success');
        url.searchParams.delete('payment');
        window.history.replaceState({}, '', url.pathname + url.search);
    }, 300);
}

// Auto-dismiss payment notification after 6 seconds
var paymentNotificationEl = document.getElementById('paymentNotification');
if (paymentNotificationEl) {
    setTimeout(dismissPaymentNotification, 6000);
}
</script>

<?php
fw_footer();

