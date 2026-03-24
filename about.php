<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db.php';

// Get some stats for the about page
$totalFlights = 0;
$totalUsers = 0;
$totalBookings = 0;

try {
    $flightCount = $conn->query("SELECT COUNT(*) as cnt FROM flights");
    if ($flightCount) {
        $totalFlights = $flightCount->fetch_assoc()['cnt'] ?? 0;
    }
    
    $userCount = $conn->query("SELECT COUNT(*) as cnt FROM users");
    if ($userCount) {
        $totalUsers = $userCount->fetch_assoc()['cnt'] ?? 0;
    }
    
    $bookingCount = $conn->query("SELECT COUNT(*) as cnt FROM bookings");
    if ($bookingCount) {
        $totalBookings = $bookingCount->fetch_assoc()['cnt'] ?? 0;
    }
} catch (Exception $e) {
    // If tables don't exist, use defaults
}

fw_header("About FlyWings - Flight Management System");
?>

<!-- Hero Section -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0">
        <img src="/flight/pexels-ahmedmuntasir-912050.jpg"
             alt="Airplane on tarmac with dramatic sky"
             class="w-full h-full object-cover"
             style="filter: brightness(0.4) contrast(1.2);">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950/80 via-slate-950/70 to-slate-950/80"></div>
    </div>
    <div class="relative max-w-6xl mx-auto px-4 py-20 md:py-28">
        <div class="max-w-3xl">
            <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.3em] mb-4">About FlyWings</p>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
                Modern Flight Management
                <span class="text-sky-400">System</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-300 leading-relaxed mb-8 max-w-2xl">
                A comprehensive, academic-grade flight management platform that seamlessly connects airline administrators 
                with passengers. Built with modern web technologies for reliability, security, and exceptional user experience.
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="/flight/register.php"
                   class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 px-6 py-3 text-sm font-semibold hover:bg-sky-400 transition shadow-lg shadow-sky-500/30">
                    <span>Get Started</span>
                    <span>→</span>
                </a>
                <a href="/flight/login.php"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-700 text-slate-300 px-6 py-3 text-sm font-medium hover:border-slate-600 hover:text-slate-200 transition">
                    <span>Login</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="bg-slate-900/50 border-y border-slate-800">
    <div class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold text-sky-400 mb-2"><?php echo $totalFlights > 0 ? $totalFlights : '50+'; ?></div>
                <p class="text-sm text-slate-400">Active Flights</p>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold text-emerald-400 mb-2"><?php echo $totalUsers > 0 ? $totalUsers : '100+'; ?></div>
                <p class="text-sm text-slate-400">Registered Users</p>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold text-amber-400 mb-2"><?php echo $totalBookings > 0 ? $totalBookings : '200+'; ?></div>
                <p class="text-sm text-slate-400">Total Bookings</p>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold text-purple-400 mb-2">24/7</div>
                <p class="text-sm text-slate-400">System Availability</p>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="max-w-6xl mx-auto px-4 py-16 md:py-20">
    <div class="text-center mb-12">
        <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.3em] mb-3">Key Features</p>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Everything You Need for Flight Management</h2>
        <p class="text-slate-400 max-w-2xl mx-auto">
            FlyWings provides a complete solution for managing airline operations and passenger bookings
        </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Feature 1 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group">
            <div class="w-12 h-12 rounded-xl bg-sky-500/20 flex items-center justify-center mb-4 group-hover:bg-sky-500/30 transition">
                <span class="text-2xl">✈️</span>
            </div>
            <h3 class="text-lg font-semibold mb-2">Flight Management</h3>
            <p class="text-sm text-slate-400">
                Admins can easily add, update, and manage flight schedules with real-time status tracking. 
                Complete control over routes, timings, and pricing.
            </p>
        </div>

        <!-- Feature 2 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center mb-4 group-hover:bg-emerald-500/30 transition">
                <span class="text-2xl">🔍</span>
            </div>
            <h3 class="text-lg font-semibold mb-2">Advanced Search</h3>
            <p class="text-sm text-slate-400">
                Powerful search functionality allows passengers to find flights by origin, destination, 
                date, and filter by price or airline. Fast and intuitive.
            </p>
        </div>

        <!-- Feature 3 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group">
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center mb-4 group-hover:bg-amber-500/30 transition">
                <span class="text-2xl">🎫</span>
            </div>
            <h3 class="text-lg font-semibold mb-2">Easy Booking</h3>
            <p class="text-sm text-slate-400">
                Seamless booking experience with instant PNR generation. Choose between Economy and Business 
                class with transparent pricing.
            </p>
        </div>

        <!-- Feature 4 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group">
            <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center mb-4 group-hover:bg-purple-500/30 transition">
                <span class="text-2xl">👥</span>
            </div>
            <h3 class="text-lg font-semibold mb-2">User Management</h3>
            <p class="text-sm text-slate-400">
                Comprehensive user dashboard for passengers to view bookings, manage trips, and track 
                flight status. Complete booking history.
            </p>
        </div>

        <!-- Feature 5 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group">
            <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center mb-4 group-hover:bg-red-500/30 transition">
                <span class="text-2xl">🔒</span>
            </div>
            <h3 class="text-lg font-semibold mb-2">Secure & Safe</h3>
            <p class="text-sm text-slate-400">
                Enterprise-grade security with password hashing, role-based access control, and secure 
                session management. Your data is protected.
            </p>
        </div>

        <!-- Feature 6 -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group">
            <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center mb-4 group-hover:bg-blue-500/30 transition">
                <span class="text-2xl">📊</span>
            </div>
            <h3 class="text-lg font-semibold mb-2">Analytics & Reports</h3>
            <p class="text-sm text-slate-400">
                Real-time analytics dashboard for admins to monitor bookings, revenue, flight performance, 
                and passenger statistics.
            </p>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="bg-slate-900/30 border-y border-slate-800">
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-20">
        <div class="text-center mb-12">
            <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.3em] mb-3">How It Works</p>
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Simple, Fast, and Reliable</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-sky-500/20 border-2 border-sky-500/30 flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">1️⃣</span>
                </div>
                <h3 class="text-lg font-semibold mb-2">Register & Login</h3>
                <p class="text-sm text-slate-400">
                    Create your account as a passenger or login as admin. Secure authentication ensures 
                    your data is protected.
                </p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 border-2 border-emerald-500/30 flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">2️⃣</span>
                </div>
                <h3 class="text-lg font-semibold mb-2">Search Flights</h3>
                <p class="text-sm text-slate-400">
                    Use our powerful search to find flights by origin, destination, and date. View real-time 
                    availability and pricing.
                </p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-amber-500/20 border-2 border-amber-500/30 flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">3️⃣</span>
                </div>
                <h3 class="text-lg font-semibold mb-2">Book & Travel</h3>
                <p class="text-sm text-slate-400">
                    Select your preferred class, confirm seats, and receive instant PNR. Manage your bookings 
                    from your dashboard.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Technology Stack Section -->
<section class="max-w-6xl mx-auto px-4 py-16 md:py-20">
    <div class="text-center mb-12">
        <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.3em] mb-3">Technology</p>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Built with Modern Technologies</h2>
        <p class="text-slate-400 max-w-2xl mx-auto">
            FlyWings leverages industry-standard technologies for performance, security, and scalability
        </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 text-center">
            <div class="text-4xl mb-3">🐘</div>
            <h3 class="font-semibold mb-2">PHP 8+</h3>
            <p class="text-xs text-slate-400">Server-side scripting with modern PHP features</p>
        </div>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 text-center">
            <div class="text-4xl mb-3">🗄️</div>
            <h3 class="font-semibold mb-2">MySQL</h3>
            <p class="text-xs text-slate-400">Relational database for data management</p>
        </div>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 text-center">
            <div class="text-4xl mb-3">🎨</div>
            <h3 class="font-semibold mb-2">Tailwind CSS</h3>
            <p class="text-xs text-slate-400">Utility-first CSS framework for modern UI</p>
        </div>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 text-center">
            <div class="text-4xl mb-3">🔐</div>
            <h3 class="font-semibold mb-2">Security</h3>
            <p class="text-xs text-slate-400">Password hashing, session management, SQL injection protection</p>
        </div>
    </div>
</section>

<!-- System Architecture Section -->
<section class="bg-slate-900/30 border-y border-slate-800">
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.3em] mb-3">Architecture</p>
                <h2 class="text-3xl md:text-4xl font-bold mb-6">Robust System Design</h2>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-sky-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-lg">👤</span>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Role-Based Access</h3>
                            <p class="text-sm text-slate-400">
                                Separate admin and user portals with isolated functionalities. Admins manage flights, 
                                users book tickets.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-lg">🗄️</span>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Database Schema</h3>
                            <p class="text-sm text-slate-400">
                                Well-structured tables: users, admins, flights, and bookings with proper relationships 
                                and foreign keys.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-lg">🔒</span>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Security First</h3>
                            <p class="text-sm text-slate-400">
                                Prepared statements prevent SQL injection. Password hashing with PHP's password_hash() 
                                ensures secure authentication.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-lg font-semibold mb-4">Database Tables</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-950 border border-slate-800">
                        <span class="text-slate-300">users</span>
                        <span class="text-xs text-slate-500">Passenger accounts</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-950 border border-slate-800">
                        <span class="text-slate-300">admins</span>
                        <span class="text-xs text-slate-500">Admin accounts</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-950 border border-slate-800">
                        <span class="text-slate-300">flights</span>
                        <span class="text-xs text-slate-500">Flight schedules</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-950 border border-slate-800">
                        <span class="text-slate-300">bookings</span>
                        <span class="text-xs text-slate-500">Reservations</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Use Cases Section -->
<section class="max-w-6xl mx-auto px-4 py-16 md:py-20">
    <div class="text-center mb-12">
        <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.3em] mb-3">Use Cases</p>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Perfect For</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-sky-500/10 to-blue-600/10 border border-sky-500/30 rounded-2xl p-6">
            <div class="text-3xl mb-3">🎓</div>
            <h3 class="text-lg font-semibold mb-2">Academic Projects</h3>
            <p class="text-sm text-slate-400">
                Ideal for college projects, assignments, and demonstrations. Complete with documentation 
                and database schema.
            </p>
        </div>
        <div class="bg-gradient-to-br from-emerald-500/10 to-green-600/10 border border-emerald-500/30 rounded-2xl p-6">
            <div class="text-3xl mb-3">💼</div>
            <h3 class="text-lg font-semibold mb-2">Portfolio Showcase</h3>
            <p class="text-sm text-slate-400">
                Demonstrate your full-stack development skills with a complete, production-ready application 
                with modern UI.
            </p>
        </div>
        <div class="bg-gradient-to-br from-amber-500/10 to-orange-600/10 border border-amber-500/30 rounded-2xl p-6">
            <div class="text-3xl mb-3">🚀</div>
            <h3 class="text-lg font-semibold mb-2">Startup MVP</h3>
            <p class="text-sm text-slate-400">
                Use as a foundation for building a real airline booking system. Extensible architecture 
                ready for production scaling.
            </p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-gradient-to-r from-sky-500/10 via-blue-600/10 to-purple-600/10 border-y border-slate-800">
    <div class="max-w-4xl mx-auto px-4 py-16 md:py-20 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Get Started?</h2>
        <p class="text-lg text-slate-400 mb-8 max-w-2xl mx-auto">
            Experience the power of FlyWings. Register now to start booking flights or login as admin 
            to manage the system.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="/flight/register.php"
               class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 px-8 py-4 text-base font-semibold hover:bg-sky-400 transition shadow-lg shadow-sky-500/30">
                <span>Create Account</span>
                <span>→</span>
            </a>
            <a href="/flight/login.php"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-700 text-slate-300 px-8 py-4 text-base font-medium hover:border-slate-600 hover:text-slate-200 transition">
                <span>Login</span>
            </a>
        </div>
    </div>
</section>

<?php
fw_footer();
