<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: /flight/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Display all flights without search
$flights = [];
$sql = "SELECT id, flight_code, origin, destination, departure_time, arrival_time, status, base_fare, created_at 
        FROM flights ORDER BY departure_time DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $flights[] = $row;
        }
    }
    $stmt->close();
}

// Get total count for stats
$totalFlights = $conn->query("SELECT COUNT(*) as cnt FROM flights")->fetch_assoc()['cnt'] ?? 0;

fw_admin_header("Flights Management - FlyWings Admin", "flights");
?>

<section class="max-w-7xl mx-auto px-4 py-10 md:py-14">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-semibold mb-2">✈️ Flight Management</h1>
            <p class="text-sm text-slate-400">View and manage all flights in the system</p>
        </div>
        <a href="/flight/admin/add_flight.php"
           class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 px-5 py-3 text-sm font-semibold hover:bg-sky-400 transition shadow-lg shadow-sky-500/20">
            <span>➕</span>
            <span>Add New Flight</span>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-xs text-slate-400 mb-1">Total Flights</p>
            <p class="text-2xl font-semibold text-sky-300"><?php echo $totalFlights; ?></p>
        </div>
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-xs text-slate-400 mb-1">Displaying</p>
            <p class="text-2xl font-semibold text-emerald-300"><?php echo count($flights); ?></p>
        </div>
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-xs text-slate-400 mb-1">On-Time Flights</p>
            <p class="text-2xl font-semibold text-emerald-400">
                <?php 
                $onTime = $conn->query("SELECT COUNT(*) as cnt FROM flights WHERE status = 'ON-TIME'")->fetch_assoc()['cnt'] ?? 0;
                echo $onTime;
                ?>
            </p>
        </div>
        <div class="rounded-xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-xs text-slate-400 mb-1">Delayed/Cancelled</p>
            <p class="text-2xl font-semibold text-amber-300">
                <?php 
                $delayed = $conn->query("SELECT COUNT(*) as cnt FROM flights WHERE status IN ('DELAYED', 'CANCELLED')")->fetch_assoc()['cnt'] ?? 0;
                echo $delayed;
                ?>
            </p>
        </div>
    </div>



    <!-- Flights Table -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold">All Flights</h2>
            <span class="text-xs text-slate-400">Showing <?php echo count($flights); ?> flight(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="pb-3 pr-4 font-semibold">Code</th>
                    <th class="pb-3 pr-4 font-semibold">Route</th>
                    <th class="pb-3 pr-4 font-semibold">Departure</th>
                    <th class="pb-3 pr-4 font-semibold">Arrival</th>
                    <th class="pb-3 pr-4 font-semibold">Fares (Rs.)</th>
                    <th class="pb-3 pr-4 font-semibold">Status</th>
                    <th class="pb-3 font-semibold">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($flights) === 0): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            No flights in the system. <a href="/flight/admin/add_flight.php" class="text-accent hover:underline">Add your first flight</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($flights as $flight): ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-800/30 transition">
                            <td class="py-3 pr-4 font-semibold text-sky-300"><?php echo htmlspecialchars($flight['flight_code']); ?></td>
                            <td class="py-3 pr-4">
                                <div class="text-slate-200"><?php echo htmlspecialchars($flight['origin']); ?></div>
                                <div class="text-slate-400 text-[10px]">→ <?php echo htmlspecialchars($flight['destination']); ?></div>
                            </td>
                            <td class="py-3 pr-4 text-slate-300">
                                <?php echo htmlspecialchars(date('d M Y', strtotime($flight['departure_time']))); ?><br>
                                <span class="text-slate-400 text-[10px]"><?php echo htmlspecialchars(date('H:i', strtotime($flight['departure_time']))); ?></span>
                            </td>
                            <td class="py-3 pr-4 text-slate-300">
                                <?php echo htmlspecialchars(date('d M Y', strtotime($flight['arrival_time']))); ?><br>
                                <span class="text-slate-400 text-[10px]"><?php echo htmlspecialchars(date('H:i', strtotime($flight['arrival_time']))); ?></span>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="text-slate-200">Economy: Rs.<?php echo number_format((float)$flight['base_fare'], 2); ?></div>
                                <div class="text-slate-400 text-[10px]">Business: Rs.<?php echo number_format((float)$flight['base_fare'] * 1.6, 2); ?></div>
                            </td>
                            <td class="py-3 pr-4">
                                <?php
                                $statusClass = 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30';
                                if ($flight['status'] === 'DELAYED') {
                                    $statusClass = 'bg-amber-500/10 text-amber-300 border-amber-500/30';
                                } elseif ($flight['status'] === 'CANCELLED') {
                                    $statusClass = 'bg-red-500/10 text-red-300 border-red-500/30';
                                }
                                ?>
                                <span class="px-2 py-1 rounded-full border text-[10px] <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($flight['status']); ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <a href="/flight/admin/bookings.php?flight_id=<?php echo $flight['id']; ?>"
                                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-sky-500/20 text-sky-300 border border-sky-500/30 hover:bg-sky-500/30 text-[10px] transition">
                                    <span>🎫</span>
                                    <span>Book</span>
                                </a>
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

