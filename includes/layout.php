<?php
// Basic layout helper for FlyWings pages

function fw_header(string $title = "FlyWings - Flight Management System"): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#0f172a',
                            accent: '#38bdf8',
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col">
    <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/flight/index.php" class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-accent/10">
                    ✈
                </span>
                <div>
                    <p class="font-semibold tracking-wide">FlyWings</p>
                    <p class="text-xs text-slate-400 -mt-1">Flight Management System</p>
                </div>
            </a>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                <a href="/flight/index.php" class="hover:text-accent transition">Home</a>
                <a href="/flight/about.php" class="hover:text-accent transition">About</a>
                <a href="/flight/contact.php" class="hover:text-accent transition">Contact</a>
                <a href="/flight/login.php" class="hover:text-accent transition">Login</a>
                <a href="/flight/register.php" class="hover:text-accent transition">Register</a>
            </nav>
            <a href="/flight/login.php" class="md:hidden text-sm px-3 py-1.5 rounded-full border border-slate-700 hover:border-accent hover:text-accent transition">
                Login
            </a>
        </div>
    </header>
    <main class="flex-1">
    <?php
}

function fw_admin_header(string $title = "FlyWings - Admin Dashboard", string $currentPage = "dashboard"): void
{
    $adminName = $_SESSION['user_name'] ?? 'Admin';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#0f172a',
                            accent: '#38bdf8',
                        }
                    }
                }
            }
        </script>
        <style>
            html {
                scroll-behavior: smooth;
            }
            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }
        </style>
    </head>
    <body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col">
    <!-- Admin Navbar -->
    <header class="border-b border-slate-800 bg-gradient-to-r from-slate-900 via-slate-950 to-slate-900 backdrop-blur sticky top-0 z-50 shadow-lg shadow-slate-900/50">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Top Bar -->
            <div class="flex items-center justify-between py-3 border-b border-slate-800/50">
                <div class="flex items-center gap-3">
                    <a href="/flight/admin/dashboard.php" class="flex items-center gap-2 group">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500/20 to-blue-600/20 border border-sky-500/30 group-hover:from-sky-500/30 group-hover:to-blue-600/30 transition">
                            <span class="text-xl">✈</span>
                        </span>
                        <div>
                            <p class="font-bold tracking-wide text-sm">FlyWings Admin</p>
                            <p class="text-[10px] text-slate-400 -mt-0.5">Control Panel</p>
                        </div>
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/50 border border-slate-700/50">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                        <span class="text-xs text-slate-300"><?php echo htmlspecialchars($adminName); ?></span>
                    </div>
                    <a href="/flight/logout.php"
                       class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-slate-700 hover:border-red-500/50 hover:text-red-400 hover:bg-red-500/10 transition">
                        <span>🚪</span>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
            <!-- Navigation Menu -->
            <nav class="flex items-center gap-1 overflow-x-auto py-2 scrollbar-hide">
                <a href="/flight/admin/dashboard.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap <?php echo $currentPage === 'dashboard' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'; ?>">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="/flight/admin/add_flight.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap <?php echo $currentPage === 'add_flight' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'; ?>">
                    <span>➕</span>
                    <span>Add Flight</span>
                </a>
                <a href="/flight/admin/flights.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap <?php echo $currentPage === 'flights' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'; ?>">
                    <span>✈️</span>
                    <span>Search Flights</span>
                </a>
                <a href="/flight/admin/bookings.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap <?php echo $currentPage === 'bookings' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'; ?>">
                    <span>🎫</span>
                    <span>Bookings</span>
                </a>
                <a href="/flight/admin/dashboard.php#passengers"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap <?php echo $currentPage === 'passengers' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'; ?>">
                    <span>👥</span>
                    <span>Passengers</span>
                </a>
                <a href="/flight/admin/dashboard.php#reports"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap <?php echo $currentPage === 'reports' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'; ?>">
                    <span>📈</span>
                    <span>Reports</span>
                </a>
                <a href="/flight/admin/contact.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap <?php echo $currentPage === 'contact' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'; ?>">
                    <span>✉️</span>
                    <span>Contact</span>
                </a>
                <a href="/flight/index.php"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-medium transition whitespace-nowrap text-slate-400 hover:text-slate-200 hover:bg-slate-800/50">
                    <span>🏠</span>
                    <span>View Site</span>
                </a>
            </nav>
        </div>
    </header>
    <main class="flex-1">
    <?php
}

function fw_footer(): void
{
    ?>
    </main>
    <?php include __DIR__ . '/../footer.php'; ?>
    </body>
    </html>
    <?php
}

