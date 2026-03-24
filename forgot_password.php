<?php
session_start();
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';
$step = 'request'; // 'request' or 'reset'

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_reset') {
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';

    if ($email === '') {
        $error = 'Please enter your email address.';
    } else {
        if ($role === 'admin') {
            $stmt = $conn->prepare("SELECT id, name, email FROM admins WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        }

        if ($user) {
            // Generate reset token (simple 6-digit code for demo)
            $resetToken = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store reset token in session (in production, store in database)
            $_SESSION['reset_token'] = $resetToken;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_role'] = $role;
            $_SESSION['reset_expires'] = $expiresAt;
            $_SESSION['reset_user_id'] = $user['id'];

            $success = 'Password reset code sent! Your reset code is: <strong>' . $resetToken . '</strong> (For demo purposes, this is displayed here. In production, this would be sent via email.)';
            $step = 'reset';
        } else {
            $error = 'Email address not found. Please check and try again.';
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $token = trim($_POST['token'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($token === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $token) {
        $error = 'Invalid or expired reset code. Please request a new one.';
    } elseif (isset($_SESSION['reset_expires']) && strtotime($_SESSION['reset_expires']) < time()) {
        $error = 'Reset code has expired. Please request a new one.';
    } else {
        $email = $_SESSION['reset_email'] ?? '';
        $role = $_SESSION['reset_role'] ?? 'user';
        $userId = $_SESSION['reset_user_id'] ?? 0;

        if ($role === 'admin') {
            // For admins, store plain password (as per current system)
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $newPassword, $userId);
        } else {
            // For users, hash the password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashedPassword, $userId);
        }

        if ($stmt->execute()) {
            // Clear reset session
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_role']);
            unset($_SESSION['reset_expires']);
            unset($_SESSION['reset_user_id']);

            $success = 'Password reset successfully! You can now <a href="login.php" class="text-accent hover:underline">login with your new password</a>.';
            $step = 'complete';
        } else {
            $error = 'Error resetting password. Please try again.';
        }
        $stmt->close();
    }
}

// Check if we're in reset mode
if (isset($_GET['token']) || (isset($_SESSION['reset_token']) && $step !== 'complete')) {
    $step = 'reset';
}

fw_header("Forgot Password - FlyWings");
?>

<section class="max-w-2xl mx-auto px-4 py-10 md:py-16">
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 md:p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-sky-500/20 border border-sky-500/30 flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">🔐</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-semibold mb-2">Reset Your Password</h1>
            <p class="text-sm text-slate-400">
                <?php if ($step === 'request'): ?>
                    Enter your email address and we'll send you a reset code
                <?php elseif ($step === 'reset'): ?>
                    Enter the reset code and your new password
                <?php else: ?>
                    Your password has been reset successfully
                <?php endif; ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'request'): ?>
            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="request_reset">
                
                <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-700 cursor-pointer hover:border-accent/60 transition">
                        <input type="radio" name="role" value="user" class="accent-sky-500" checked>
                        <span class="text-slate-200">User</span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-700 cursor-pointer hover:border-accent/60 transition">
                        <input type="radio" name="role" value="admin" class="accent-sky-500">
                        <span class="text-slate-200">Admin</span>
                    </label>
                </div>

                <div class="space-y-1">
                    <label for="email" class="block text-sm text-slate-300">Email Address</label>
                    <input id="email" name="email" type="email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent focus:ring-1 focus:ring-accent/60"
                           placeholder="you@example.com">
                </div>

                <button type="submit"
                        class="w-full mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-accent text-slate-950 text-sm font-semibold py-3 hover:bg-sky-400 transition">
                    <span>Send Reset Code</span>
                    <span>→</span>
                </button>

                <p class="text-xs text-slate-400 text-center mt-4">
                    Remember your password? <a href="login.php" class="text-accent hover:underline">Back to Login</a>
                </p>
            </form>

        <?php elseif ($step === 'reset'): ?>
            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="reset_password">

                <div class="space-y-1">
                    <label for="token" class="block text-sm text-slate-300">Reset Code</label>
                    <input id="token" name="token" type="text" required maxlength="6"
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm outline-none focus:border-accent focus:ring-1 focus:ring-accent/60 text-center tracking-widest font-mono"
                           placeholder="000000">
                    <p class="text-xs text-slate-500 mt-1">Enter the 6-digit code sent to your email</p>
                </div>

                <div class="space-y-1">
                    <label for="new_password" class="block text-sm text-slate-300">New Password</label>
                    <div class="relative">
                        <input id="new_password" name="new_password" type="password" required
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 pr-10 text-sm outline-none focus:border-accent focus:ring-1 focus:ring-accent/60"
                               placeholder="Enter new password">
                        <button type="button" id="toggleNewPassword" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition focus:outline-none">
                            <span id="eyeIconNew">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="confirm_password" class="block text-sm text-slate-300">Confirm New Password</label>
                    <div class="relative">
                        <input id="confirm_password" name="confirm_password" type="password" required
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 pr-10 text-sm outline-none focus:border-accent focus:ring-1 focus:ring-accent/60"
                               placeholder="Confirm new password">
                        <button type="button" id="toggleConfirmPassword" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition focus:outline-none">
                            <span id="eyeIconConfirm">👁️</span>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-accent text-slate-950 text-sm font-semibold py-3 hover:bg-sky-400 transition">
                    <span>Reset Password</span>
                    <span>→</span>
                </button>

                <p class="text-xs text-slate-400 text-center mt-4">
                    <a href="forgot_password.php" class="text-accent hover:underline">Request new code</a> | 
                    <a href="login.php" class="text-accent hover:underline">Back to Login</a>
                </p>
            </form>

        <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">✅</span>
                </div>
                <p class="text-lg font-semibold mb-4">Password Reset Successful!</p>
                <a href="login.php"
                   class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 px-6 py-3 text-sm font-semibold hover:bg-sky-400 transition">
                    <span>Go to Login</span>
                    <span>→</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Show/Hide Password Toggles
function setupPasswordToggle(toggleId, inputId, iconId) {
    const toggle = document.getElementById(toggleId);
    if (toggle) {
        toggle.addEventListener('click', function() {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        });
    }
}

setupPasswordToggle('toggleNewPassword', 'new_password', 'eyeIconNew');
setupPasswordToggle('toggleConfirmPassword', 'confirm_password', 'eyeIconConfirm');
</script>

<?php
fw_footer();

