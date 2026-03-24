<?php
require_once __DIR__ . '/includes/layout.php';

fw_header("FlyWings - Smart Flight Management System");
?>

<!-- Hero Section -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0 opacity-25">
        <img src="https://images.pexels.com/photos/1309644/pexels-photo-1309644.jpeg?auto=compress&cs=tinysrgb&w=1600"
             alt="Airplane taking off" class="w-full h-full object-cover">
    </div>
    <div class="relative max-w-6xl mx-auto px-4 py-20 lg:py-28 grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <p class="text-xs font-medium tracking-[0.25em] uppercase text-accent/80 mb-4">Next-Gen Aviation</p>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-semibold tracking-tight mb-4">
                Manage your airline operations with
                <span class="text-accent">FlyWings</span>
            </h1>
            <p class="text-slate-300 text-sm md:text-base mb-6 max-w-xl">
                FlyWings is an advanced flight management system that helps airlines track routes, schedules,
                bookings and passengers in real time, with powerful dashboards for both admin and users.
            </p>
            <div class="flex flex-wrap gap-3 mb-6">
                <a href="login.php" 
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-accent text-slate-950 text-sm font-medium shadow-lg shadow-sky-500/20 hover:bg-sky-400 transition">
                    Get Started
                    <span>→</span>
                </a>
                <a href="#features"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-slate-700 text-sm hover:border-accent hover:text-accent transition">
                    Explore Features
                </a>
            </div>
            <div class="grid grid-cols-3 gap-4 text-xs text-slate-400">
                <div>
                    <p class="text-lg font-semibold text-slate-100">24/7</p>
                    <p>Real-time monitoring</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-slate-100">99.9%</p>
                    <p>Uptime for critical ops</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-slate-100">1M+</p>
                    <p>Passengers managed</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-xl backdrop-blur">
            <p class="text-xs font-medium text-slate-400 mb-3">Live Snapshot</p>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="rounded-xl bg-gradient-to-br from-sky-500/20 via-slate-900 to-slate-950 border border-sky-500/30 p-3">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wide mb-1">Flights Today</p>
                    <p class="text-2xl font-semibold text-slate-50">128</p>
                    <p class="text-[11px] text-emerald-400 mt-1">+12 new routes</p>
                </div>
                <div class="rounded-xl bg-gradient-to-br from-emerald-500/10 via-slate-900 to-slate-950 border border-emerald-500/20 p-3">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wide mb-1">On-Time</p>
                    <p class="text-2xl font-semibold text-slate-50">94%</p>
                    <p class="text-[11px] text-emerald-400 mt-1">Operational excellence</p>
                </div>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-3 mb-3">
                <div class="flex items-center justify-between text-[11px] text-slate-400 mb-2">
                    <p>Upcoming departures</p>
                    <p>UTC</p>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between rounded-lg bg-slate-900/60 px-2 py-1.5">
                        <div>
                            <p class="font-medium text-slate-100">FW 208 · Mumbai → Dubai</p>
                            <p class="text-[11px] text-slate-400">Gate B12 · Boarding in 18 min</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            ON-TIME
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-900/60 px-2 py-1.5">
                        <div>
                            <p class="font-medium text-slate-100">FW 542 · Delhi → Singapore</p>
                            <p class="text-[11px] text-slate-400">Gate C03 · Delayed by 10 min</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/20">
                            DELAY
                        </span>
                    </div>
                </div>
            </div>
            <p class="text-[11px] text-slate-500">
                * Demo data for UI preview. Real data will be loaded from the FlyWings database.
            </p>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="max-w-6xl mx-auto px-4 py-10 md:py-16">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
        <div>
            <h2 class="text-xl md:text-2xl font-semibold mb-2">Powerful modules for modern airlines</h2>
            <p class="text-sm text-slate-300 max-w-xl">
                FlyWings provides separate, secure experiences for administrators and end-users with role-based access,
                interactive dashboards, and real-time data visualization.
            </p>
        </div>
        <div class="flex gap-2 text-xs">
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-900 border border-slate-700">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                Live routes
            </span>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-900 border border-slate-700">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                Smart booking
            </span>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Admin Module Card -->
        <div class="relative rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-950 p-5 overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-sky-500/10 blur-3xl"></div>
            <div class="flex items-center justify-between mb-4 relative">
                <div>
                    <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.2em]">Admin Module</p>
                    <h3 class="text-lg font-semibold mt-1">Flight Operations Center</h3>
                </div>
                <div class="w-10 h-10 rounded-full bg-sky-500/20 flex items-center justify-center border border-sky-500/30">
                    <span class="text-sky-300 text-xl">⚙</span>
                </div>
            </div>
            <p class="text-sm text-slate-300 mb-4">
                Admins get a full control center to configure aircrafts, routes, schedules, pricing, and monitor
                booking trends with insights and alerts.
            </p>
            <ul class="text-xs text-slate-300 space-y-2 mb-4">
                <li class="flex items-start gap-2">
                    <span class="mt-1 text-sky-400">◆</span>
                    <span>Manage flights: create, update, cancel, and reschedule flights with conflict detection.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-1 text-sky-400">◆</span>
                    <span>Monitor bookings, passenger lists, and seat availability in real-time.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-1 text-sky-400">◆</span>
                    <span>Dashboard with KPIs: occupancy rate, revenue estimates, delay statistics.</span>
                </li>
            </ul>
            <a href="admin/login.php" class="inline-flex items-center gap-1 text-xs text-sky-400 hover:text-sky-300">
                Go to Admin Portal
                <span>↗</span>
            </a>
        </div>

        <!-- User Module Card -->
        <div class="relative rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-950 p-5 overflow-hidden">
            <div class="absolute -left-10 -bottom-10 w-40 h-40 rounded-full bg-emerald-500/10 blur-3xl"></div>
            <div class="flex items-center justify-between mb-4 relative">
                <div>
                    <p class="text-xs font-semibold text-emerald-400 uppercase tracking-[0.2em]">User Module</p>
                    <h3 class="text-lg font-semibold mt-1">Smart Passenger Portal</h3>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30">
                    <span class="text-emerald-300 text-xl">👤</span>
                </div>
            </div>
            <p class="text-sm text-slate-300 mb-4">
                Passengers can search flights, book tickets, manage their bookings, and download boarding passes
                from a mobile-first interface.
            </p>
            <ul class="text-xs text-slate-300 space-y-2 mb-4">
                <li class="flex items-start gap-2">
                    <span class="mt-1 text-emerald-400">◆</span>
                    <span>Search flights by route, date, time and filter by airline, price or duration.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-1 text-emerald-400">◆</span>
                    <span>Instant booking with unique PNR generation and booking history.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-1 text-emerald-400">◆</span>
                    <span>Responsive UI optimized for mobile, tablet and desktop.</span>
                </li>
            </ul>
            <a href="login.php" class="inline-flex items-center gap-1 text-xs text-emerald-400 hover:text-emerald-300">
                Login as User
                <span>↗</span>
            </a>
        </div>
    </div>
</section>

<!-- Gallery / Imagery -->
<section class="max-w-6xl mx-auto px-4 pb-10 md:pb-14">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg md:text-xl font-semibold">FlyWings in action</h2>
    </div>
    <div class="grid md:grid-cols-4 gap-4">
        <div class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
            <img src="https://images.pexels.com/photos/1309644/pexels-photo-1309644.jpeg?auto=compress&cs=tinysrgb&w=1600"
                 alt="Airplane taking off" class="w-full h-40 object-cover hover:scale-105 transition duration-500">
        </div>
        <div class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
            <img src="/flight/aeroplane1.jpg"
                 alt="Airplane climbing" class="w-full h-40 object-cover hover:scale-105 transition duration-500">
        </div>
        <div class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
            <img src="https://images.pexels.com/photos/723240/pexels-photo-723240.jpeg?auto=compress&cs=tinysrgb&w=1600"
                 alt="Airplane above clouds" class="w-full h-40 object-cover hover:scale-105 transition duration-500">
        </div>
        <div class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
            <img src="/flight/aeroplane.jpg"
                 alt="Airplane on runway at sunrise" class="w-full h-40 object-cover hover:scale-105 transition duration-500">
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="max-w-6xl mx-auto px-4 pb-10 md:pb-14">
    <div class="grid lg:grid-cols-[1.2fr,0.8fr] gap-8 items-center">
        <div class="space-y-3">
            <p class="text-xs font-semibold text-sky-400 uppercase tracking-[0.25em]">About FlyWings</p>
            <h2 class="text-2xl md:text-3xl font-semibold leading-tight">A complete mini airline system, ready for project and reports</h2>
            <p class="text-sm text-slate-300">
                FlyWings is an academic-grade flight management system that cleanly separates admin and user roles. Admins
                configure routes, schedules, prices and monitor bookings; users discover flights, check schedules, and book seats.
            </p>
            <div class="grid sm:grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3">
                    <p class="text-slate-200 font-semibold mb-1">Admin ops cockpit</p>
                    <p class="text-slate-400">Create/update flights, set fares, track status (on-time, delayed, cancelled).</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3">
                    <p class="text-slate-200 font-semibold mb-1">User booking flow</p>
                    <p class="text-slate-400">Search by origin/destination/date and view real data from MySQL flights table.</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3">
                    <p class="text-[11px] text-slate-400">Security</p>
                    <p class="text-slate-100 font-semibold">Hashed passwords</p>
                    <p class="text-slate-400">password_hash / verify for users</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3">
                    <p class="text-[11px] text-slate-400">Database</p>
                    <p class="text-slate-100 font-semibold">MySQL schema</p>
                    <p class="text-slate-400">users, admins, flights, bookings</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3">
                    <p class="text-[11px] text-slate-400">Stack</p>
                    <p class="text-slate-100 font-semibold">PHP + Tailwind</p>
                    <p class="text-slate-400">Responsive dark UI</p>
                </div>
            </div>
            <p class="text-sm text-slate-300">
                Ideal for mini-project submissions, portfolio projects, or as a starter kit to extend into a production-grade airline/booking product.
            </p>
        </div>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 space-y-3">
            <h3 class="text-sm font-semibold">System highlights</h3>
            <ul class="text-xs text-slate-300 space-y-2">
                <li>◆ Role-based access: isolated Admin vs User flows.</li>
                <li>◆ Admin can add flights with code, route, calendar times, price, and status.</li>
                <li>◆ User search is live: queries the `flights` table by origin/destination/date.</li>
                <li>◆ Database ready: `users`, `admins`, `flights`, `bookings` tables plus sample data.</li>
                <li>◆ Modern UI: Tailwind CSS, dark theme, responsive layouts.</li>
            </ul>
            <div class="rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-slate-300">
                <p class="text-[11px] text-slate-400 mb-1">logins</p>
                <p>Admin: <span class="text-sky-300">admin@flywings.com / admin123</span></p>
                <p class="text-[11px] text-slate-500 mt-1">Register a new user from the Register page to test the user flow.</p>
            </div>
        </div>
    </div>
</section>
<!-- <!-- 
Contact Section
<section id="contact" class="max-w-6xl mx-auto px-4 pb-14">
    <div class="grid md:grid-cols-2 gap-8">
        <div>
            <h2 class="text-xl md:text-2xl font-semibold mb-3">Contact & Support</h2>
            <p class="text-sm text-slate-300 mb-4">
                Use this section in your project report as the official contact area. You can later connect this form
                to send emails or store messages in a `contact_messages` table.
            </p>
            <div class="space-y-2 text-sm text-slate-300">
                <p><span class="text-slate-400">Project:</span> FlyWings Flight Management System</p>
                <p><span class="text-slate-400">Module:</span> Admin & User Based Airline Operations</p>
                <p><span class="text-slate-400">Tech:</span> PHP · MySQL · Tailwind CSS</p>
            </div>
        </div>
        <form class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs space-y-3">
            <div class="space-y-1">
                <label class="text-slate-300">Name</label>
                <input type="text" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                       placeholder="Your name">
            </div>
            <div class="space-y-1">
                <label class="text-slate-300">Email</label>
                <input type="email" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                       placeholder="you@example.com">
            </div>
            <div class="space-y-1">
                <label class="text-slate-300">Message</label>
                <textarea class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                          rows="4" placeholder="Share your feedback or queries about FlyWings..."></textarea>
            </div>
            <button type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 px-4 py-2 text-xs font-medium hover:bg-sky-400 transition">
                Send Message (Demo)
            </button>
            <p class="text-[11px] text-slate-500">
                This is a front-end only project. For production, connect it to a PHP handler or third-party email API.
            </p>
        </form>
    </div>
</section>  -->

<?php
fw_footer();

