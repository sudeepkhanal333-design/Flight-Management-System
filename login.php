<?php
session_start();
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'user'; // 'user' or 'admin'

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        if ($role === 'admin') {
            // Admins: simple check (for project/demo). You can later switch admins to hashed passwords too.
            $stmt = $conn->prepare("SELECT id, name, email FROM admins WHERE email = ? AND password = ?");
            $stmt->bind_param('ss', $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['role'] = 'admin';

                header('Location: admin/dashboard.php');
                exit;
            } else {
                $error = 'Invalid admin credentials.';
            }
        } else {
            // Users: secure login using password_verify
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['role'] = 'user';

                    header('Location: user/dashboard.php');
                    exit;
                }
            }

            $error = 'Invalid user credentials.';
        }
    }
}

fw_header("FlyWings - Login");
?>

<section class="max-w-6xl mx-auto px-4 py-10 md:py-16 grid md:grid-cols-2 gap-10 items-center">
    <div class="hidden md:block">
        <div class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-2xl shadow-sky-500/5">
            <div class="relative overflow-hidden rounded-2xl mb-4 group">
                <img src="/flight/aeroplane welcome.jpg"
                     alt="Aerial view from airplane wings" 
                     class="w-full h-80 object-cover transition-transform duration-700 group-hover:scale-105"
                     style="filter: brightness(0.9) contrast(1.15) saturate(1.2);">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent rounded-2xl"></div>
            </div>
            <p class="text-xs text-slate-400 mb-2 font-medium">Secure access for Admin & Users</p>
            <p class="text-sm text-slate-200 leading-relaxed">
                FlyWings uses role-based authentication so admins and passengers access tailored dashboards and
                operations without interfering with each other.
            </p>
        </div>
    </div>

    <div>
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">Welcome back to FlyWings</h1>
        <p class="text-sm text-slate-300 mb-6">
            Login to manage flights, bookings and passenger details. Choose your role to access the right module.
        </p>

        <?php if ($error): ?>
            <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-xs text-red-300">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-300">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4 bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
            <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-700 cursor-pointer hover:border-accent/60 transition">
                    <input type="radio" name="role" value="user" class="accent-sky-500" checked>
                    <span class="text-slate-200">User Portal</span>
                </label>
                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-700 cursor-pointer hover:border-accent/60 transition">
                    <input type="radio" name="role" value="admin" class="accent-sky-500">
                    <span class="text-slate-200">Admin Panel</span>
                </label>
            </div>

            <div class="space-y-1 text-xs">
                <label for="email" class="block text-slate-300">Email</label>
                <input id="email" name="email" type="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm outline-none focus:border-accent focus:ring-1 focus:ring-accent/60"
                       placeholder="you@example.com">
            </div>

            <div class="space-y-1 text-xs">
                <label for="password" class="block text-slate-300">Password</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 pr-10 text-sm outline-none focus:border-accent focus:ring-1 focus:ring-accent/60"
                           placeholder="••••••••">
                    <button type="button" id="togglePassword" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition focus:outline-none">
                        <span id="eyeIcon">👁️</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="remember" name="remember" class="accent-sky-500 rounded">
                    <span class="text-slate-400">Remember me</span>
                </label>
                <a href="forgot_password.php" class="text-accent hover:text-sky-300 hover:underline transition">
                    Forgot password?
                </a>
            </div>

            <button type="submit"
                    class="w-full mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-accent text-slate-950 text-sm font-medium py-2.5 hover:bg-sky-400 transition">
                Login
                <span>→</span>
            </button>

            <p class="text-[11px] text-slate-400 mt-3 text-center">
                New passenger? <a href="register.php" class="text-accent hover:underline">Create an account</a>
            </p>
        </form>
    </div>
</section>

<script>
// Show/Hide Password Toggle
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        eyeIcon.textContent = '👁️';
    }
});
</script>

<?php
fw_footer();
