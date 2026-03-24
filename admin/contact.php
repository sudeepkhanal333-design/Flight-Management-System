<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: /flight/login.php');
    exit;
}

// Ensure table exists
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

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE contact_messages SET read_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: /flight/admin/contact.php');
    exit;
}

$messages = [];
$res = $conn->query("SELECT id, name, email, subject, message, created_at, read_at FROM contact_messages ORDER BY created_at DESC LIMIT 100");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
}

$unreadCount = 0;
foreach ($messages as $m) {
    if ($m['read_at'] === null) $unreadCount++;
}

fw_admin_header("Contact Messages - FlyWings Admin", "contact");
?>

<section class="max-w-6xl mx-auto px-4 py-10 md:py-14">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-semibold mb-2">✉️ Contact Messages</h1>
            <p class="text-sm text-slate-400">Messages sent by users from the contact form</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400 bg-slate-800 px-3 py-1.5 rounded-full">
                Total: <?php echo count($messages); ?>
                <?php if ($unreadCount > 0): ?>
                    <span class="text-amber-300 font-semibold"> · <?php echo $unreadCount; ?> unread</span>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <?php if (count($messages) === 0): ?>
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-12 text-center">
            <p class="text-slate-400 mb-2">No contact messages yet.</p>
            <p class="text-xs text-slate-500">Messages from <a href="/flight/contact.php" class="text-accent hover:underline">contact page</a> will appear here.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($messages as $m): ?>
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 <?php echo $m['read_at'] ? '' : 'border-l-4 border-l-sky-500'; ?>">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-slate-100"><?php echo htmlspecialchars($m['name']); ?></span>
                                <span class="text-slate-500">·</span>
                                <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" class="text-sky-300 text-xs hover:underline"><?php echo htmlspecialchars($m['email']); ?></a>
                                <?php if (!$m['read_at']): ?>
                                    <span class="px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-300 text-[10px] font-medium">New</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm font-medium text-slate-200 mt-1"><?php echo htmlspecialchars($m['subject']); ?></p>
                            <p class="text-xs text-slate-400 mt-2 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                            <p class="text-[11px] text-slate-500 mt-2"><?php echo date('d M Y, H:i', strtotime($m['created_at'])); ?></p>
                        </div>
                        <?php if (!$m['read_at']): ?>
                            <form method="post" class="flex-shrink-0">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition">Mark as read</button>
                            </form>
                        <?php else: ?>
                            <span class="text-[11px] text-slate-500 flex-shrink-0">Read <?php echo date('d M', strtotime($m['read_at'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
fw_footer();
