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
            // Check if flight code already exists
            $check = $conn->prepare("SELECT id FROM flights WHERE flight_code = ?");
            $check->bind_param('s', $code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $flightError = 'Flight code already exists. Please use a different code.';
            } else {
                $stmt = $conn->prepare("INSERT INTO flights (flight_code, origin, destination, departure_time, arrival_time, status, base_fare) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssssd', $code, $origin, $destination, $departure, $arrival, $status, $fare);
                if ($stmt->execute()) {
                    $flightSuccess = 'Flight added successfully!';
                    // Clear form on success
                    $_POST = [];
                } else {
                    $flightError = 'Error adding flight. Please try again.';
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}

fw_admin_header("Add Flight - FlyWings Admin", "add_flight");
?>

<section class="max-w-4xl mx-auto px-4 py-10 md:py-14">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">✈️ Add New Flight</h1>
        <p class="text-sm text-slate-400">Create a new flight route with schedule and pricing information</p>
    </div>

    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 md:p-8">
        <?php if ($flightError): ?>
            <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <span class="font-semibold">Error:</span> <?php echo htmlspecialchars($flightError); ?>
            </div>
        <?php endif; ?>
        <?php if ($flightSuccess): ?>
            <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                <span class="font-semibold">Success:</span> <?php echo htmlspecialchars($flightSuccess); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <input type="hidden" name="action" value="create_flight">
            
            <div class="grid md:grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300">Flight Code <span class="text-red-400">*</span></label>
                    <input name="flight_code" type="text" required
                           value="<?php echo htmlspecialchars($_POST['flight_code'] ?? ''); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent transition"
                           placeholder="FW208">
                    <p class="text-xs text-slate-500">Unique flight identifier (e.g., FW208, BA542)</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300">Base Fare (Rs.) - Economy <span class="text-red-400">*</span></label>
                    <input name="base_fare" type="number" min="1" step="0.01" required
                           value="<?php echo htmlspecialchars($_POST['base_fare'] ?? ''); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent transition"
                           placeholder="18500.00">
                    <p class="text-xs text-slate-500">Business class will be calculated as 1.6× this amount</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300">Origin <span class="text-red-400">*</span></label>
                    <input name="origin" type="text" required
                           value="<?php echo htmlspecialchars($_POST['origin'] ?? ''); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent transition"
                           placeholder="Mumbai (BOM)">
                    <p class="text-xs text-slate-500">Departure city and airport code</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300">Destination <span class="text-red-400">*</span></label>
                    <input name="destination" type="text" required
                           value="<?php echo htmlspecialchars($_POST['destination'] ?? ''); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent transition"
                           placeholder="Dubai (DXB)">
                    <p class="text-xs text-slate-500">Arrival city and airport code</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300 flex items-center gap-2">
                        <span>📅</span>
                        <span>Departure Date & Time <span class="text-red-400">*</span></span>
                    </label>
                    <div class="relative">
                        <input id="departure_time" name="departure_time" type="datetime-local" required
                               value="<?php echo htmlspecialchars($_POST['departure_time'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d\TH:i'); ?>"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 pr-10 text-sm outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 transition cursor-pointer">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">📅</span>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <button type="button" onclick="setQuickTime('departure', 'today', '12:00')" 
                                class="text-xs px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:border-accent hover:text-accent transition">
                            Today 12:00
                        </button>
                        <button type="button" onclick="setQuickTime('departure', 'tomorrow', '08:00')" 
                                class="text-xs px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:border-accent hover:text-accent transition">
                            Tomorrow 08:00
                        </button>
                        <button type="button" onclick="setQuickTime('departure', 'nextweek', '10:00')" 
                                class="text-xs px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:border-accent hover:text-accent transition">
                            Next Week
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Select date and time using the calendar picker</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300 flex items-center gap-2">
                        <span>📅</span>
                        <span>Arrival Date & Time <span class="text-red-400">*</span></span>
                    </label>
                    <div class="relative">
                        <input id="arrival_time" name="arrival_time" type="datetime-local" required
                               value="<?php echo htmlspecialchars($_POST['arrival_time'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d\TH:i'); ?>"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 pr-10 text-sm outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 transition cursor-pointer">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">📅</span>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <button type="button" onclick="setQuickTime('arrival', 'today', '18:00')" 
                                class="text-xs px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:border-accent hover:text-accent transition">
                            Today 18:00
                        </button>
                        <button type="button" onclick="setQuickTime('arrival', 'tomorrow', '14:00')" 
                                class="text-xs px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:border-accent hover:text-accent transition">
                            Tomorrow 14:00
                        </button>
                        <button type="button" onclick="setArrivalAfterDeparture()" 
                                class="text-xs px-2 py-1 rounded-lg bg-sky-500/20 border border-sky-500/30 text-sky-300 hover:bg-sky-500/30 transition">
                            +2hrs from Departure
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Must be after departure time</p>
                </div>
            </div>
            
            <div id="timeValidation" class="hidden rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-2 text-xs text-amber-300">
                <span class="font-semibold">⚠️ Warning:</span> Arrival time must be after departure time.
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-300">Flight Status</label>
                <select name="status"
                        class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent transition">
                    <option value="ON-TIME" <?php echo ($_POST['status'] ?? 'ON-TIME') === 'ON-TIME' ? 'selected' : ''; ?>>ON-TIME</option>
                    <option value="DELAYED" <?php echo ($_POST['status'] ?? '') === 'DELAYED' ? 'selected' : ''; ?>>DELAYED</option>
                    <option value="CANCELLED" <?php echo ($_POST['status'] ?? '') === 'CANCELLED' ? 'selected' : ''; ?>>CANCELLED</option>
                </select>
                <p class="text-xs text-slate-500">Current status of the flight</p>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 px-6 py-3 text-sm font-semibold hover:bg-sky-400 transition shadow-lg shadow-sky-500/20">
                    <span>💾</span>
                    <span>Save Flight</span>
                </button>
                <a href="/flight/admin/flights.php"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-700 text-slate-300 px-6 py-3 text-sm font-medium hover:border-slate-600 hover:text-slate-200 transition">
                    <span>←</span>
                    <span>Back to Flights</span>
                </a>
            </div>
        </form>
    </div>
</section>

<script>
// Set minimum date for arrival based on departure
document.getElementById('departure_time').addEventListener('change', function() {
    const departureTime = this.value;
    const arrivalInput = document.getElementById('arrival_time');
    
    if (departureTime) {
        // Set minimum arrival time to be at least 1 hour after departure
        const departureDate = new Date(departureTime);
        departureDate.setHours(departureDate.getHours() + 1);
        
        const minArrival = departureDate.toISOString().slice(0, 16);
        arrivalInput.min = minArrival;
        
        // If current arrival is before new minimum, update it
        if (arrivalInput.value && arrivalInput.value < minArrival) {
            arrivalInput.value = minArrival;
        }
        
        validateTimes();
    }
});

// Validate that arrival is after departure
function validateTimes() {
    const departure = document.getElementById('departure_time').value;
    const arrival = document.getElementById('arrival_time').value;
    const validationMsg = document.getElementById('timeValidation');
    
    if (departure && arrival) {
        if (new Date(arrival) <= new Date(departure)) {
            validationMsg.classList.remove('hidden');
            return false;
        } else {
            validationMsg.classList.add('hidden');
            return true;
        }
    }
    return true;
}

// Validate on arrival change
document.getElementById('arrival_time').addEventListener('change', validateTimes);

// Quick time setters
function setQuickTime(field, type, time) {
    const input = document.getElementById(field + '_time');
    const now = new Date();
    let date = new Date();
    
    if (type === 'today') {
        date = new Date();
    } else if (type === 'tomorrow') {
        date.setDate(now.getDate() + 1);
    } else if (type === 'nextweek') {
        date.setDate(now.getDate() + 7);
    }
    
    const [hours, minutes] = time.split(':');
    date.setHours(parseInt(hours), parseInt(minutes), 0, 0);
    
    // Format as datetime-local value (YYYY-MM-DDTHH:mm)
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    
    input.value = `${year}-${month}-${day}T${hour}:${minute}`;
    
    // If setting departure, update arrival minimum
    if (field === 'departure') {
        const arrivalInput = document.getElementById('arrival_time');
        const minArrival = new Date(date);
        minArrival.setHours(minArrival.getHours() + 1);
        const minArrivalStr = minArrival.toISOString().slice(0, 16);
        arrivalInput.min = minArrivalStr;
    }
    
    validateTimes();
}

// Set arrival time to 2 hours after departure
function setArrivalAfterDeparture() {
    const departureInput = document.getElementById('departure_time');
    const arrivalInput = document.getElementById('arrival_time');
    
    if (!departureInput.value) {
        alert('Please select departure time first');
        return;
    }
    
    const departureDate = new Date(departureInput.value);
    departureDate.setHours(departureDate.getHours() + 2);
    
    const year = departureDate.getFullYear();
    const month = String(departureDate.getMonth() + 1).padStart(2, '0');
    const day = String(departureDate.getDate()).padStart(2, '0');
    const hour = String(departureDate.getHours()).padStart(2, '0');
    const minute = String(departureDate.getMinutes()).padStart(2, '0');
    
    arrivalInput.value = `${year}-${month}-${day}T${hour}:${minute}`;
    validateTimes();
}

// Form validation before submit
document.querySelector('form').addEventListener('submit', function(e) {
    if (!validateTimes()) {
        e.preventDefault();
        alert('Please ensure arrival time is after departure time.');
        return false;
    }
});

// Set minimum dates on page load
window.addEventListener('load', function() {
    const now = new Date().toISOString().slice(0, 16);
    document.getElementById('departure_time').min = now;
    document.getElementById('arrival_time').min = now;
});
</script>

<?php
fw_footer();

