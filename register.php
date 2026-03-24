<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';
$fieldErrors = [
    'name' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    // Normalize email so duplicates like A@X.com and a@x.com are detected consistently.
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($name === '') {
        $fieldErrors['name'] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2) {
        $fieldErrors['name'] = 'Name must be at least 2 characters.';
    } elseif (mb_strlen($name) > 60) {
        $fieldErrors['name'] = 'Name must be at most 60 characters.';
    } elseif (!preg_match('/^[\p{L} .\'-]+$/u', $name)) {
        $fieldErrors['name'] = 'Name contains invalid characters.';
    }

    if ($email === '') {
        $fieldErrors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 255) {
        $fieldErrors['email'] = 'Email is too long.';
    }

    if ($password === '') {
        $fieldErrors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $fieldErrors['password'] = 'Password should be at least 6 characters.';
    } elseif (strlen($password) > 72) {
        // Avoid extremely large payloads; bcrypt hash input doesn't need more.
        $fieldErrors['password'] = 'Password is too long.';
    }

    if ($confirmPassword === '') {
        $fieldErrors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== '' && $confirmPassword !== $password) {
        $fieldErrors['confirm_password'] = 'Passwords do not match.';
    }

    $hasFieldErrors = (
        $fieldErrors['name'] !== '' ||
        $fieldErrors['email'] !== '' ||
        $fieldErrors['password'] !== '' ||
        $fieldErrors['confirm_password'] !== ''
    );

    if (!$hasFieldErrors) {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $fieldErrors['email'] = 'Email already registered. Please login.';
        } else {
            // Secure password hashing for users
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $name, $email, $hashedPassword);
            if ($stmt->execute()) {
                $success = 'Account created successfully. You can now login.';
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
    } else {
        $error = 'Please fix the highlighted fields.';
    }
}

fw_header("FlyWings - User Registration");
?>

<section class="max-w-6xl mx-auto px-4 py-10 md:py-16 grid md:grid-cols-2 gap-10 items-center">
    <div>
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">Create your FlyWings account</h1>
        <p class="text-sm text-slate-300 mb-6">
            Sign up as a passenger to search and book flights, manage bookings, and download your boarding passes.
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

        <form method="post" id="registerForm" class="space-y-4 bg-slate-900/60 border border-slate-800 rounded-2xl p-5" novalidate>
            <div class="space-y-1 text-xs">
                <label for="name" class="block text-slate-300">Full Name</label>
                <input id="name" name="name" type="text" required
                       minlength="2" maxlength="60"
                       value="<?= htmlspecialchars($name ?? '') ?>"
                       class="w-full rounded-xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-accent/60 <?= $fieldErrors['name'] ? 'border-red-500/70 focus:border-red-500 focus:ring-red-500/30' : 'border-slate-700 focus:border-accent' ?>"
                       placeholder="John Doe">
                <?php if ($fieldErrors['name']): ?>
                    <p class="mt-1 text-[11px] text-red-300"><?php echo htmlspecialchars($fieldErrors['name']); ?></p>
                <?php endif; ?>
            </div>

            <div class="space-y-1 text-xs">
                <label for="email" class="block text-slate-300">Email</label>
                <input id="email" name="email" type="email" required
                       maxlength="255"
                       value="<?= htmlspecialchars($email ?? '') ?>"
                       class="w-full rounded-xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-accent/60 <?= $fieldErrors['email'] ? 'border-red-500/70 focus:border-red-500 focus:ring-red-500/30' : 'border-slate-700 focus:border-accent' ?>"
                       placeholder="you@example.com">
                <?php if ($fieldErrors['email']): ?>
                    <p class="mt-1 text-[11px] text-red-300"><?php echo htmlspecialchars($fieldErrors['email']); ?></p>
                <?php endif; ?>
            </div>

            <div class="space-y-1 text-xs">
                <label for="password" class="block text-slate-300">Password</label>
                <input id="password" name="password" type="password" required
                       minlength="6"
                       class="w-full rounded-xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-accent/60 <?= $fieldErrors['password'] ? 'border-red-500/70 focus:border-red-500 focus:ring-red-500/30' : 'border-slate-700 focus:border-accent' ?>"
                       placeholder="••••••••">
                <?php if ($fieldErrors['password']): ?>
                    <p class="mt-1 text-[11px] text-red-300"><?php echo htmlspecialchars($fieldErrors['password']); ?></p>
                <?php endif; ?>
                <p class="mt-1 text-[11px] text-slate-500">Use at least 6 characters.</p>
            </div>

            <div class="space-y-1 text-xs">
                <label for="confirm_password" class="block text-slate-300">Confirm Password</label>
                <input id="confirm_password" name="confirm_password" type="password" required
                       minlength="6"
                       class="w-full rounded-xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-accent/60 <?= $fieldErrors['confirm_password'] ? 'border-red-500/70 focus:border-red-500 focus:ring-red-500/30' : 'border-slate-700 focus:border-accent' ?>"
                       placeholder="••••••••">
                <?php if ($fieldErrors['confirm_password']): ?>
                    <p class="mt-1 text-[11px] text-red-300"><?php echo htmlspecialchars($fieldErrors['confirm_password']); ?></p>
                <?php endif; ?>
            </div>

            <button type="submit"
                    class="w-full mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-accent text-slate-950 text-sm font-medium py-2.5 hover:bg-sky-400 transition">
                Create Account
                <span>→</span>
            </button>

            <p class="text-[11px] text-slate-400 mt-3 text-center">
                Already registered? <a href="login.php" class="text-accent hover:underline">Login here</a>
            </p>
        </form>
    </div>

    <div class="hidden md:block">
        <div class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
            <img src="/flight/sky.jpg"
                 alt="Sky at sunset" class="w-full h-72 object-cover rounded-2xl mb-4 shadow-lg shadow-sky-500/10">
            <p class="text-xs text-slate-400 mb-2">Why FlyWings?</p>
            <ul class="text-xs text-slate-200 space-y-1">
                <li>✓ Fast and secure booking process</li>
                <li>✓ Real-time flight status and notifications</li>
                <li>✓ Access your tickets anytime from any device</li>
                <li>✓ Powered by PHP + MySQL with Tailwind UI</li>
            </ul>
        </div>
    </div>
</section>

<script>
    (function () {
        const form = document.getElementById('registerForm');
        if (!form) return;

        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirm_password');

        function validatePasswordMatch() {
            if (!password || !confirmPassword) return;
            if (confirmPassword.value && password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match.');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        password && password.addEventListener('input', validatePasswordMatch);
        confirmPassword && confirmPassword.addEventListener('input', validatePasswordMatch);

        form.addEventListener('submit', function (e) {
            validatePasswordMatch();

            // Let HTML5 constraints do the quick checks, but keep UI consistent (no browser popup).
            if (!form.checkValidity()) {
                e.preventDefault();
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    })();
</script>

<?php
fw_footer();

