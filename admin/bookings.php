<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: /flight/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Fetch all bookings with user and flight details
$bookings = [];
$bookingsQuery = "SELECT b.id, b.pnr, b.seats, b.status, b.booked_at, 
                  u.name as user_name, u.email as user_email,
                  f.flight_code, f.origin, f.destination, f.departure_time
                  FROM bookings b
                  JOIN users u ON b.user_id = u.id
                  JOIN flights f ON b.flight_id = f.id
                  ORDER BY b.booked_at DESC
                  LIMIT 50";
$bookingsResult = $conn->query($bookingsQuery);
if ($bookingsResult && $bookingsResult->num_rows > 0) {
    while ($row = $bookingsResult->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Get booking stats
$totalBookings = $conn->query("SELECT COUNT(*) as cnt FROM bookings")->fetch_assoc()['cnt'] ?? 0;
$confirmedBookings = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE status = 'CONFIRMED'")->fetch_assoc()['cnt'] ?? 0;

fw_admin_header("Bookings Management - FlyWings Admin", "bookings");
?>

<section class="max-w-7xl mx-auto px-4 py-10 md:py-14">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">🎫 Bookings Management</h1>
        <p class="text-sm text-slate-400">View and manage all flight reservations in the system</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-xs text-slate-400 mb-1">Total Bookings</p>
            <p class="text-2xl font-semibold text-sky-300"><?php echo $totalBookings; ?></p>
        </div>
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-xs text-slate-400 mb-1">Confirmed</p>
            <p class="text-2xl font-semibold text-emerald-300"><?php echo $confirmedBookings; ?></p>
        </div>
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-xs text-slate-400 mb-1">Pending/Cancelled</p>
            <p class="text-2xl font-semibold text-amber-300"><?php echo $totalBookings - $confirmedBookings; ?></p>
        </div>
    </div>

    <!-- Bookings List Only (booking form removed) -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5">
        <h2 class="text-sm font-semibold mb-4">Recent Bookings</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="pb-3 pr-3 font-semibold">PNR</th>
                    <th class="pb-3 pr-3 font-semibold">Passenger</th>
                    <th class="pb-3 pr-3 font-semibold">Flight</th>
                    <th class="pb-3 pr-3 font-semibold">Seats</th>
                    <th class="pb-3 pr-3 font-semibold">Status</th>
                    <th class="pb-3 font-semibold">Booked</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($bookings) === 0): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">No bookings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-800/30 transition">
                            <td class="py-3 pr-3 font-semibold text-sky-300"><?php echo htmlspecialchars($booking['pnr']); ?></td>
                            <td class="py-3 pr-3">
                                <div class="text-slate-200"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                <div class="text-slate-400 text-[10px]"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                            </td>
                            <td class="py-3 pr-3">
                                <div class="text-slate-200"><?php echo htmlspecialchars($booking['flight_code']); ?></div>
                                <div class="text-slate-400 text-[10px]"><?php echo htmlspecialchars($booking['origin']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></div>
                            </td>
                            <td class="py-3 pr-3 text-slate-300"><?php echo $booking['seats']; ?></td>
                            <td class="py-3 pr-3">
                                <?php
                                $statusClass = 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30';
                                if ($booking['status'] === 'PENDING') {
                                    $statusClass = 'bg-amber-500/10 text-amber-300 border-amber-500/30';
                                } elseif ($booking['status'] === 'CANCELLED') {
                                    $statusClass = 'bg-red-500/10 text-red-300 border-red-500/30';
                                }
                                ?>
                                <span class="px-2 py-1 rounded-full border text-[10px] <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </span>
                            </td>
                            <td class="py-3 text-slate-400 text-[10px]">
                                <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($booking['booked_at']))); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
fw_footer();

