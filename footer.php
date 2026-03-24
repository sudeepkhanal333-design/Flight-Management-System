<?php
// FlyWings global footer (included from includes/layout.php)
?>
<style>
    /* Extra styling for the FlyWings footer (on top of Tailwind classes) */
    .fw-footer-gradient {
        background: radial-gradient(circle at 15% 20%, rgba(56, 189, 248, 0.12), transparent 45%),
                    radial-gradient(circle at 85% 0%, rgba(45, 212, 191, 0.10), transparent 40%),
                    linear-gradient(135deg, #020617 0%, #0b1226 100%);
    }

    .fw-footer-link-hover:hover {
        color: #38bdf8;
        transform: translateY(-1px);
    }
    .fw-footer-link-hover {
        transition: color 0.18s ease, transform 0.18s ease;
    }
</style>

<footer class="border-t border-slate-800 fw-footer-gradient mt-10 shadow-[0_-12px_48px_rgba(0,0,0,0.45)]">
    <div class="max-w-6xl mx-auto px-4 py-9 grid md:grid-cols-4 gap-6 text-xs text-slate-400">
        <!-- Brand + CTA -->
        <div class="md:col-span-2 space-y-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-accent/15 text-accent text-lg shadow-lg shadow-sky-500/20 border border-accent/30">
                    ✈
                </span>
                <div>
                    <p class="font-semibold text-slate-100 tracking-wide">FlyWings</p>
                    <p class="text-[11px] text-slate-500 -mt-0.5">Smart Flight Management System</p>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 max-w-md">
                FlyWings helps airlines and passengers manage flights, bookings, and operations in a clean, modern interface.
                Built with PHP, MySQL, and Tailwind CSS for academic and project use.
            </p>
            <div class="inline-flex items-center gap-2 rounded-full bg-slate-900/80 border border-slate-700 px-3 py-1.5 text-[11px] text-slate-200 shadow-lg shadow-sky-500/10">
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 text-[10px]">
                    Live Project
                </span>
                <span>Experience the FlyWings dashboards</span>
            </div>
        </div>

        <!-- Quick Links -->
        <div>
            <p class="text-[11px] font-semibold text-slate-200 mb-2 uppercase tracking-[0.15em]">Quick Links</p>
            <ul class="space-y-1.5">
                <li><a href="/flight/index.php" class="fw-footer-link-hover">Home</a></li>
                <li><a href="/flight/index.php#about" class="fw-footer-link-hover">About</a></li>
                <li><a href="/flight/contact.php" class="fw-footer-link-hover">Contact</a></li>
                <li><a href="/flight/login.php" class="fw-footer-link-hover">User Login</a></li>
                <li><a href="/flight/admin/login.php" class="fw-footer-link-hover">Admin Panel</a></li>
            </ul>
        </div>

        <!-- Project Info -->
        <div>
            <p class="text-[11px] font-semibold text-slate-200 mb-2 uppercase tracking-[0.15em]">Project Info</p>
            <ul class="space-y-1.5">
                <li>Module: Admin & User</li>
                <li>Backend: PHP · MySQL</li>
                <li>Frontend: Tailwind CSS</li>
                <li>Theme: Dark · Responsive</li>
            </ul>
        </div>

        <!-- Contact / Social -->
        <div class="md:col-span-1">
            <p class="text-[11px] font-semibold text-slate-200 mb-2 uppercase tracking-[0.15em]">Get in touch</p>
            <ul class="space-y-1.5">
                <li>Email: <a class="fw-footer-link-hover" href="mailto:contact@flywings.com">contact@flywings.com</a></li>
                <li>Support: <a class="fw-footer-link-hover" href="/flight/contact.php">Contact form</a></li>
            </ul>
            <div class="flex items-center gap-2 mt-3">
                <span class="text-[11px] text-slate-400">Socials:</span>
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-900 border border-slate-800 text-slate-300 text-xs fw-footer-link-hover">in</span>
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-900 border border-slate-800 text-slate-300 text-xs fw-footer-link-hover">X</span>
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-900 border border-slate-800 text-slate-300 text-xs fw-footer-link-hover">IG</span>
            </div>
        </div>
    </div>
    <div class="border-t border-slate-800 bg-slate-950/90">
        <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-2 text-[11px] text-slate-500">
            <p>© <?php echo date('Y'); ?> FlyWings. All rights reserved.</p>
            <p class="flex items-center gap-2">
                <span class="hidden sm:inline">Designed for academic project use.</span>
                <span class="inline-flex gap-2 text-slate-400 items-center">
                    <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                    <span>PHP</span>
                    <span>·</span>
                    <span>MySQL</span>
                    <span>·</span>
                    <span>Tailwind</span>
                </span>
            </p>
        </div>
    </div>
</footer>


