<?php
session_start();
require 'db_connect.php';
requireAdminAccess();

$conn = getDbConnection();
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_submit'])) {
    $to = trim($_POST['message_recipient'] ?? '');
    $subject = trim($_POST['message_subject'] ?? '');
    $body = trim($_POST['message_body'] ?? '');

    if ($to !== '' && $subject !== '' && $body !== '') {
        $messageText = $subject . '\n\n' . $body;
        $ok = $conn ? addContactMessage($conn, 'Admin User', $to, $messageText) : false;
        $successMessage = $ok ? 'Message sent successfully.' : 'Unable to send message.';
    } else {
        $successMessage = 'Please complete all message fields.';
    }
}

$messages = $conn ? getAllMessages($conn) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Messages</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div>
                <div class="admin-sidebar-header">
                    <h2 class="admin-logo">Evergreen</h2>
                    <div class="admin-sidebar-subtitle">Admin Console</div>
                </div>

                <nav class="admin-nav">
                    <a href="admin-dashboard.php"><span class="admin-nav-icon">⊞</span> Dashboard</a>
                    <a href="admin-inventory.php"><span class="admin-nav-icon">📦</span> Inventory</a>
                    <a href="admin-orders.php"><span class="admin-nav-icon">🛒</span> Orders</a>
                    <a href="admin-customers.php"><span class="admin-nav-icon">👥</span> Customers</a>
                    <a href="admin-messages.php" class="active"><span class="admin-nav-icon">💬</span> Messages</a>
                </nav>
            </div>

            <div class="admin-user">
                <div class="admin-user-avatar">AU</div>
                <div class="admin-user-meta">
                    <span class="admin-user-name">Admin User</span>
                    <span class="admin-user-email">admin@evergreen.co</span>
                </div>
            </div>
        </aside>

        <main class="admin-main-panel">
            <header class="admin-topbar">
                <div class="admin-search-wrap">
                    <input type="text" placeholder="Search messages...">
                </div>
                <div class="admin-topbar-actions">
                    <a href="login.php?logout=1" class="admin-logout-btn">Logout</a>
                </div>
            </header>

            <div class="admin-page-content">
                <div class="admin-page-header">
                    <div>
                        <h1>Messages</h1>
                        <p>Manage incoming customer questions and support conversations.</p>
                    </div>
                    <button type="button" class="admin-btn-primary open-modal" data-modal-target="messageModal">+ Compose</button>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="auth-message success" style="margin-bottom: 20px;">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="admin-controls">
                        <div class="admin-filters">
                            <select class="admin-select">
                                <option>Inbox</option>
                            </select>
                            <select class="admin-select">
                                <option>Unread</option>
                            </select>
                        </div>
                    </div>

                    <ul class="admin-message-list">
                        <?php if (empty($messages)): ?>
                            <li class="admin-message-item">
                                <div class="admin-order-meta">
                                    <strong>No messages yet</strong>
                                    <span>Your customer messages will appear here.</span>
                                </div>
                            </li>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <li class="admin-message-item">
                                    <div class="admin-order-meta">
                                        <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                        <span><?php echo htmlspecialchars(substr($message['message'], 0, 120)); ?>...</span>
                                    </div>
                                    <span class="admin-order-status processing">Unread</span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <div class="admin-modal" id="messageModal" aria-hidden="true">
        <div class="admin-modal-content" role="dialog" aria-modal="true" aria-labelledby="messageModalTitle">
            <div class="admin-modal-header">
                <h3 id="messageModalTitle">Compose Message</h3>
                <button type="button" class="admin-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <form method="POST" action="admin-messages.php" data-admin-form="message">
                    <input type="hidden" name="message_submit" value="1">
                    <div class="admin-form-row">
                        <div class="admin-modal-field">
                            <label for="message-recipient">To</label>
                            <input id="message-recipient" name="message_recipient" type="email" placeholder="customer@example.com" required>
                        </div>
                        <div class="admin-modal-field">
                            <label for="message-subject">Subject</label>
                            <input id="message-subject" name="message_subject" type="text" placeholder="Order update" required>
                        </div>
                    </div>
                    <div class="admin-form-row single">
                        <div class="admin-modal-field">
                            <label for="message-body">Message</label>
                            <textarea id="message-body" name="message_body" placeholder="Write your message here..." required></textarea>
                        </div>
                    </div>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-modal-cancel">Cancel</button>
                        <button type="submit" class="admin-modal-submit">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/Js/script.js"></script>
</body>
</html>
