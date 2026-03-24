<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: /flight/login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Handle new flight creation
$flightError = '';
$flightSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_flight') {
    $code = trim($_POST['flight_code'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $departure = trim($_POST['departure_time'] ?? '');
    $arrival = trim($_POST['arrival_time'] ?? '');
    $fare = trim($_POST['base_fare'] ?? '');
    $status = $_POST['status'] ?? 'ON-TIME';

    if ($code === '' || $origin === '' || $destination === '' || $departure === '' || $arrival === '' || $fare === '') {
        $flightError = 'All flight fields are required.';
    } else {
        if (!is_numeric($fare) || $fare <= 0) {
            $flightError = 'Please enter a valid positive price.';
        } else {
            $stmt = $conn->prepare("INSERT INTO flights (flight_code, origin, destination, departure_time, arrival_time, status, base_fare) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssd', $code, $origin, $destination, $departure, $arrival, $status, $fare);
            if ($stmt->execute()) {
                $flightSuccess = 'Flight added successfully.';
            } else {
                $flightError = 'Error adding flight. Maybe this flight code already exists.';
            }
        }
    }
}

// Fetch flights for listing + admin search
$flights = [];
$searchOrigin = trim($_GET['s_origin'] ?? '');
$searchDestination = trim($_GET['s_destination'] ?? '');
$searchDate = trim($_GET['s_date'] ?? '');

$hasSearch = $searchOrigin !== '' || $searchDestination !== '' || $searchDate !== '';

if ($hasSearch) {
    $sql = "SELECT flight_code, origin, destination, departure_time, status, base_fare 
            FROM flights WHERE 1=1";
    $params = [];
    $types = '';

    if ($searchOrigin !== '') {
        $sql .= " AND origin LIKE ?";
        $params[] = '%' . $searchOrigin . '%';
        $types .= 's';
    }
    if ($searchDestination !== '') {
        $sql .= " AND destination LIKE ?";
        $params[] = '%' . $searchDestination . '%';
        $types .= 's';
    }
    if ($searchDate !== '') {
        $sql .= " AND DATE(departure_time) = ?";
        $params[] = $searchDate;
        $types .= 's';
    }

    $sql .= " ORDER BY departure_time DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if ($stmt->execute()) {
            $resultFlights = $stmt->get_result();
            if ($resultFlights && $resultFlights->num_rows > 0) {
                while ($row = $resultFlights->fetch_assoc()) {
                    $flights[] = $row;
                }
            }
        }
        $stmt->close();
    }
} else {
    $resultFlights = $conn->query("SELECT flight_code, origin, destination, departure_time, status, base_fare FROM flights ORDER BY departure_time DESC LIMIT 10");
    if ($resultFlights && $resultFlights->num_rows > 0) {
        while ($row = $resultFlights->fetch_assoc()) {
            $flights[] = $row;
        }
    }
}

// Fetch latest passengers with booking & payment overview for admin
$users = [];
$usersSql = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.created_at,
        COUNT(b.id) AS total_bookings,
        COALESCE(SUM(CASE WHEN b.status = 'CONFIRMED' THEN b.seats ELSE 0 END), 0) AS total_seats_confirmed,
        COALESCE(SUM(CASE WHEN b.status = 'CONFIRMED' THEN b.seats * f.base_fare ELSE 0 END), 0) AS total_amount_paid
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    LEFT JOIN flights f ON b.flight_id = f.id
    GROUP BY u.id, u.name, u.email, u.created_at
    ORDER BY u.created_at DESC
    LIMIT 10
";

$resultUsers = $conn->query($usersSql);
if ($resultUsers && $resultUsers->num_rows > 0) {
    while ($row = $resultUsers->fetch_assoc()) {
        $users[] = $row;
    }
}

fw_admin_header("FlyWings - Admin Dashboard", "dashboard");
?>

<section class="max-w-6xl mx-auto px-4 py-10 md:py-14">
    <div
        class="relative overflow-hidden rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-900 via-slate-950 to-slate-950 mb-4">
        <div class="absolute inset-y-0 right-0 w-1/2 hidden md:block opacity-70">
            <img src="https://images.pexels.com/photos/1309644/pexels-photo-1309644.jpeg?auto=compress&cs=tinysrgb&w=1200"
                 alt="Airplane taking off"
                 class="w-full h-full object-cover">
        </div>
        <div class="relative grid md:grid-cols-[2fr,1fr] gap-4 items-center px-5 py-5 md:px-8 md:py-6">
            <div>
                <p class="text-[11px] text-sky-400 mb-1 uppercase tracking-[0.2em]">Admin · Operations Center</p>
                <h1 class="text-2xl md:text-3xl font-semibold mb-1">Hello, <?php echo htmlspecialchars($adminName); ?></h1>
                <p class="text-sm text-slate-300 max-w-xl">
                    Monitor flights, update schedules, and keep on-time performance high. Use the panel below to add
                    new routes, adjust fares, and review recent bookings.
                </p>
            </div>
            <div class="flex md:flex-col md:items-end gap-3 justify-between">
                <div class="rounded-2xl bg-slate-900/80 border border-slate-700 px-4 py-3 text-xs text-slate-300">
                    <p class="text-[11px] text-slate-400 mb-1">Today’s snapshot</p>
                    <p>
                        Flights scheduled:
                        <span class="font-semibold text-sky-300"><?php echo count($flights); ?></span>
                    </p>
                    <p class="text-[11px] text-slate-500 mt-1">Update timings using the calendar pickers below.</p>
                </div>
                <a href="/flight/logout.php"
                   class="inline-flex items-center gap-2 text-[11px] px-3 py-1.5 rounded-full border border-slate-600 hover:border-accent hover:text-accent transition bg-slate-950/60">
                    Logout
                </a>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-[11px] text-slate-400 mb-1">Total Flights (sample)</p>
            <p class="text-2xl font-semibold"><?php echo count($flights); ?></p>
            <p class="text-[11px] text-emerald-400 mt-1">+12 vs yesterday</p>
        </div>
        <div class="rounded-2xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-[11px] text-slate-400 mb-1">Registered Users</p>
            <p class="text-2xl font-semibold"><?php echo count($users); ?></p>
            <p class="text-[11px] text-emerald-400 mt-1">Showing latest 10 in list below</p>
        </div>
        <div class="rounded-2xl bg-slate-900/70 border border-slate-800 p-4">
            <p class="text-[11px] text-slate-400 mb-1">On-Time Performance</p>
            <p class="text-2xl font-semibold">94%</p>
            <p class="text-[11px] text-amber-300 mt-1">Monitor delays carefully</p>
        </div>
    </div>

    <div class="grid md:grid-cols-[2fr,1.5fr] gap-6">
        <!-- Flight management: add + list flights -->
        <div id="manage-flights" class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold">Manage Flights</h2>
                <span class="text-[11px] text-slate-400">Add new routes & schedules</span>
            </div>

            <?php if ($flightError): ?>
                <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-[11px] text-red-300">
                    <?php echo htmlspecialchars($flightError); ?>
                </div>
            <?php endif; ?>
            <?php if ($flightSuccess): ?>
                <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-[11px] text-emerald-300">
                    <?php echo htmlspecialchars($flightSuccess); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="grid md:grid-cols-2 gap-3 mb-4">
                <input type="hidden" name="action" value="create_flight">
                <div class="space-y-1">
                    <label class="text-slate-300">Flight Code</label>
                    <input name="flight_code" type="text" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent"
                           placeholder="FW208">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">Base Fare (Rs.) – Economy</label>
                    <input name="base_fare" type="number" min="1" step="0.01" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent"
                           placeholder="18500">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">From (Origin)</label>
                    <input name="origin" type="text" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent"
                           placeholder="Mumbai (BOM)">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">To (Destination)</label>
                    <input name="destination" type="text" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent"
                           placeholder="Dubai (DXB)">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">Departure Time
                        <span class="text-[10px] text-slate-500">(calendar picker)</span>
                    </label>
                    <input name="departure_time" type="datetime-local" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">Arrival Time
                        <span class="text-[10px] text-slate-500">(calendar picker)</span>
                    </label>
                    <input name="arrival_time" type="datetime-local" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent">
                </div>
                <div class="space-y-1">
                    <label class="text-slate-300">Status</label>
                    <select name="status"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent">
                        <option value="ON-TIME">ON-TIME</option>
                        <option value="DELAYED">DELAYED</option>
                        <option value="CANCELLED">CANCELLED</option>
                    </select>
                </div>
                <div class="flex items-end justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-1 rounded-xl bg-accent text-slate-950 px-4 py-2 text-[11px] font-medium hover:bg-sky-400 transition">
                        Save Flight
                    </button>
                </div>
            </form>

            <!-- Flight search form -->
            <div id="search-flights" class="mb-4 rounded-xl bg-slate-950 border border-slate-800 p-3">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[12px] font-semibold">Search flights</h3>
                    <?php if ($hasSearch): ?>
                        <a href="/flight/admin/dashboard.php"
                           class="text-[11px] text-sky-400 hover:text-sky-300">
                            Clear filters
                        </a>
                    <?php endif; ?>
                </div>
                <form method="get" class="grid md:grid-cols-3 gap-3 text-[11px]">
                    <div class="space-y-1">
                        <label class="text-slate-300">From</label>
                        <input type="text" name="s_origin"
                               value="<?php echo htmlspecialchars($searchOrigin); ?>"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                               placeholder="e.g. Mumbai">
                    </div>
                    <div class="space-y-1">
                        <label class="text-slate-300">To</label>
                        <input type="text" name="s_destination"
                               value="<?php echo htmlspecialchars($searchDestination); ?>"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                               placeholder="e.g. Dubai">
                    </div>
                    <div class="space-y-1">
                        <label class="text-slate-300">Departure date</label>
                        <input type="date" name="s_date"
                               value="<?php echo htmlspecialchars($searchDate); ?>"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent">
                    </div>
                    <div class="md:col-span-3 flex justify-end items-end">
                        <button type="submit"
                                class="inline-flex items-center gap-1 rounded-xl bg-sky-500 text-slate-950 px-4 py-2 font-medium hover:bg-sky-400 transition">
                            Apply filters
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl bg-slate-950 border border-slate-800 p-3 overflow-x-auto">
                <table class="w-full text-[11px] text-left border-collapse">
                    <thead class="text-slate-400">
                    <tr>
                        <th class="pb-2 pr-3">Code</th>
                        <th class="pb-2 pr-3">Route</th>
                        <th class="pb-2 pr-3">Departure</th>
                        <th class="pb-2 pr-3">Fares (Rs.)</th>
                        <th class="pb-2 pr-3">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($flights) === 0): ?>
                        <tr class="border-t border-slate-800">
                            <td colspan="5" class="py-2 text-slate-500">No flights added yet. Use the form above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($flights as $flight): ?>
                            <tr class="border-t border-slate-800">
                                <td class="py-2 pr-3"><?php echo htmlspecialchars($flight['flight_code']); ?></td>
                                <td class="py-2 pr-3">
                                    <?php echo htmlspecialchars($flight['origin']); ?>
                                    →
                                    <?php echo htmlspecialchars($flight['destination']); ?>
                                </td>
                                <td class="py-2 pr-3">
                                    <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($flight['departure_time']))); ?>
                                </td>
                                <td class="py-2 pr-3">
                                    <?php
                                    $economyFare = (float)$flight['base_fare'];
                                    $businessFare = $economyFare * 1.6; // simple multiplier for demo
                                    ?>
                                    <div>Economy: Rs.<?php echo number_format($economyFare, 2); ?></div>
                                    <div class="text-slate-400">Business: Rs.<?php echo number_format($businessFare, 2); ?></div>
                                </td>
                                <td class="py-2">
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
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Booking overview & calendar + passengers list -->
        <div class="space-y-4">
            <div id="bookings" class="relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/70 p-5 text-xs">
                <div class="absolute inset-0 opacity-20 pointer-events-none">
                    <img src="https://images.pexels.com/photos/3582202/pexels-photo-3582202.jpeg?auto=compress&cs=tinysrgb&w=1200"
                         alt="Airport terminal" class="w-full h-full object-cover">
                </div>
                <div class="relative">
                    <h2 class="text-sm font-semibold mb-2">Booking overview</h2>
                    <p class="text-[11px] text-slate-300 mb-3">
                        Use this calendar to quickly choose a day when you plan to schedule or review flights. You can
                        extend this to filter the flight list by selected date.
                    </p>
                    <div class="space-y-2 mb-3">
                        <label class="text-slate-300">Select date
                            <span class="text-[10px] text-slate-500">(calendar)</span>
                        </label>
                        <input type="date"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-[11px] outline-none focus:border-accent">
                    </div>
                    <p class="text-[11px] text-slate-500">
                        For your project, connect this date picker to a PHP query that filters flights in the table
                        by the chosen date, or pre-fills booking forms in the user module.
                    </p>
                </div>
            </div>
<!-- 
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs">
                <h2 class="text-sm font-semibold mb-2">Sample recent bookings</h2>
                <div class="space-y-2">
                    <div class="flex items-center justify-between rounded-xl bg-slate-950 border border-slate-800 px-3 py-2">
                        <div>
                            <p class="text-slate-100">PNR: FWX9KJ</p>
                            <p class="text-[11px] text-slate-400">John Doe · FW 208 · 2 seats</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/30">
                            CONFIRMED
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-950 border border-slate-800 px-3 py-2">
                        <div>
                            <p class="text-slate-100">PNR: FW3PLQ</p>
                            <p class="text-[11px] text-slate-400">Anita Rao · FW 542 · 1 seat</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/30">
                            PENDING
                        </span>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500 mt-3">
                    Later you can replace this static data with records from the `bookings` table filtered by today’s
                    date or the selected calendar date above.
                </p>
            </div> -->

            <div id="passengers" class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs">
                <h2 class="text-sm font-semibold mb-2">Passengers & Payment Overview</h2>
                <p class="text-[11px] text-slate-400 mb-3">
                    Latest registered passengers with their total bookings, seats, and confirmed payment amounts.
                </p>
                <div class="rounded-xl bg-slate-950 border border-slate-800 max-h-64 overflow-y-auto">
                    <table class="w-full text-[11px] text-left border-collapse">
                        <thead class="text-slate-400 sticky top-0 bg-slate-950">
                        <tr>
                            <th class="pb-2 px-3">Name</th>
                            <th class="pb-2 px-3">Email</th>
                            <th class="pb-2 px-3">Joined</th>
                            <th class="pb-2 px-3 text-right">Bookings</th>
                            <th class="pb-2 px-3 text-right">Seats (CONF.)</th>
                            <th class="pb-2 px-3 text-right">Paid (Rs.)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($users) === 0): ?>
                            <tr class="border-t border-slate-800">
                                <td colspan="6" class="px-3 py-2 text-slate-500">No users registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr class="border-t border-slate-800">
                                    <td class="px-3 py-2 text-slate-100">
                                        <?php echo htmlspecialchars($u['name']); ?>
                                    </td>
                                    <td class="px-3 py-2 text-slate-300">
                                        <?php echo htmlspecialchars($u['email']); ?>
                                    </td>
                                    <td class="px-3 py-2 text-slate-400">
                                        <?php echo htmlspecialchars(date('d M Y', strtotime($u['created_at']))); ?>
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-200">
                                        <?php echo (int)($u['total_bookings'] ?? 0); ?>
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-200">
                                        <?php echo (int)($u['total_seats_confirmed'] ?? 0); ?>
                                    </td>
                                    <td class="px-3 py-2 text-right text-emerald-300">
                                        Rs.<?php echo number_format((float)($u['total_amount_paid'] ?? 0), 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-[11px] text-slate-500 mt-3">
                    Payment totals are calculated from <code>CONFIRMED</code> bookings using the base fare and number of seats.
                </p>
            </div>
        </div>
    </div>

    <!-- Reports Section -->
    <div id="reports" class="mt-8 bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs">
        <h2 class="text-sm font-semibold mb-3">📈 Reports & Analytics</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="rounded-xl bg-slate-950 border border-slate-800 p-4">
                <p class="text-[11px] text-slate-400 mb-2">Revenue Overview</p>
                <p class="text-lg font-semibold text-emerald-400">Rs.<?php 
                    $totalRevenue = 0;
                    $revenueQuery = $conn->query("SELECT SUM(b.seats * f.base_fare) as total FROM bookings b JOIN flights f ON b.flight_id = f.id WHERE b.status = 'CONFIRMED'");
                    if ($revenueQuery && $row = $revenueQuery->fetch_assoc()) {
                        $totalRevenue = $row['total'] ?? 0;
                    }
                    echo number_format($totalRevenue, 2);
                ?></p>
                <p class="text-[10px] text-slate-500 mt-1">Total from confirmed bookings</p>
            </div>
            <div class="rounded-xl bg-slate-950 border border-slate-800 p-4">
                <p class="text-[11px] text-slate-400 mb-2">Booking Statistics</p>
                <p class="text-lg font-semibold text-sky-400"><?php 
                    $bookingCount = $conn->query("SELECT COUNT(*) as cnt FROM bookings")->fetch_assoc()['cnt'] ?? 0;
                    echo $bookingCount;
                ?></p>
                <p class="text-[10px] text-slate-500 mt-1">Total bookings in system</p>
            </div>
        </div>
    </div>

    <!-- Settings Section -->
    <div id="settings" class="mt-6 bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs">
        <h2 class="text-sm font-semibold mb-3">⚙️ System Settings</h2>
        <div class="space-y-3">
            <div class="rounded-xl bg-slate-950 border border-slate-800 p-4">
                <p class="text-[11px] text-slate-300 mb-2">Database Status</p>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                    <span class="text-[11px] text-slate-400">Connected to flywings database</span>
                </div>
            </div>
            <div class="rounded-xl bg-slate-950 border border-slate-800 p-4">
                <p class="text-[11px] text-slate-300 mb-2">System Information</p>
                <p class="text-[11px] text-slate-400">FlyWings Flight Management System v1.0</p>
                <p class="text-[10px] text-slate-500 mt-1">Admin panel for managing flights, passengers, and bookings</p>
            </div>
        </div>
    </div>
</section>

<?php
fw_footer();

