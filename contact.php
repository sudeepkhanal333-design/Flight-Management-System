<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db.php';

$formError = '';
$formSuccess = '';

// Ensure contact_messages table exists (create if not)
$conn->query("
    CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL DEFAULT NULL
    )
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $formError = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } elseif (strlen($name) > 100) {
        $formError = 'Name must be 100 characters or less.';
    } elseif (strlen($subject) > 200) {
        $formError = 'Subject must be 200 characters or less.';
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $subject, $message);
        if ($stmt->execute()) {
            $formSuccess = 'Thank you! Your message has been sent. We will get back to you soon.';
            $_POST = []; // clear form
        } else {
            $formError = 'Sorry, we could not send your message. Please try again later.';
        }
        $stmt->close();
    }
}

fw_header("FlyWings - Contact & Support");
?>

<section class="max-w-6xl mx-auto px-4 py-10 md:py-16">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">Contact FlyWings</h1>
        <p class="text-sm text-slate-300 max-w-2xl">
            Have a question or feedback? Send us a message and we’ll get back to you as soon as we can.
        </p>
    </div>

    <?php if ($formSuccess): ?>
        <div class="mb-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300 flex items-center gap-3">
            <span class="text-2xl">✓</span>
            <span><?php echo htmlspecialchars($formSuccess); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($formError): ?>
        <div class="mb-6 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300 flex items-center gap-3">
            <span class="text-2xl">!</span>
            <span><?php echo htmlspecialchars($formError); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-8">
        <form method="post" class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs space-y-3">
            <div class="space-y-1">
                <label class="text-slate-300">Name</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                       placeholder="Your name">
            </div>
            <div class="space-y-1">
                <label class="text-slate-300">Email</label>
                <input type="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                       placeholder="you@example.com">
            </div>
            <div class="space-y-1">
                <label class="text-slate-300">Subject</label>
                <input type="text" name="subject" required maxlength="200"
                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                       placeholder="Query about FlyWings system">
            </div>
            <div class="space-y-1">
                <label class="text-slate-300">Message</label>
                <textarea name="message" rows="4" required
                          class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 outline-none focus:border-accent"
                          placeholder="Write your message or feedback here..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-accent text-slate-950 px-4 py-2 text-xs font-medium hover:bg-sky-400 transition">
                Send Message
            </button>
            <p class="text-[11px] text-slate-500">
                Messages are stored securely and reviewed by our team. We typically respond within 1–2 business days.
            </p>
        </form>

        <div class="space-y-3 text-sm text-slate-300">
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5">
                <h2 class="text-base font-semibold mb-2">FlyWings Support</h2>
                <p class="text-xs text-slate-300 mb-1"><span class="text-slate-400">Email:</span> support@flywings.com</p>
                <p class="text-xs text-slate-300 mb-1"><span class="text-slate-400">Hours:</span> Mon–Fri, 9:00 AM – 6:00 PM</p>
                <p class="text-xs text-slate-300 mb-1"><span class="text-slate-400">Topics:</span> Bookings, refunds, flight info, feedback</p>
            </div>
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 text-xs text-slate-300 space-y-1">
                <h2 class="text-sm font-semibold mb-1">What happens next</h2>
                <p>◆ Your message is saved and sent to our team.</p>
                <p>◆ Admins can view and manage messages from the admin panel.</p>
                <p>◆ We’ll reply to your email address when we have an update.</p>
            </div>
        </div>
    </div>
</section>

<?php
fw_footer();
