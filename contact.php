<?php
session_start();
require 'db_connect.php';
requireLogin();

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $body = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $body === '') {
        $message = 'Please complete all form fields.';
    } else {
        $conn = getDbConnection();
        if (!$conn) {
            $message = 'Database connection failed.';
        } elseif (addContactMessage($conn, $name, $email, $body)) {
            $message = 'Thank you! Your message has been sent successfully.';
            $messageType = 'success';
            $_POST = [];
        } else {
            $message = 'Unable to send your message right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Contact Us</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="contact-page-bg">

    <header>
        <div class="logo">Evergreen</div>
        <nav>
            <a href="index.php" class="nav-link">Shop</a>
            <a href="catalog.php" class="nav-link">Catalog</a>
            <a href="cart.php" class="nav-link">Cart</a>
            <a href="contact.php" class="nav-link active">Contact</a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <a href="admin-dashboard.php" class="nav-link" style="color: #044b3c; font-weight: bold;">Admin</a>
            <?php endif; ?>
            <a href="login.php?logout=1" class="nav-link">Logout</a>
        </nav>
    </header>

    <!-- Main Contact Section -->
    <main class="contact-main">
        <div class="contact-header">
            <h1>Get in Touch</h1>
            <p>We'd love to hear from you. Please fill out the form below and our team will get back to you soon.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="auth-message <?php echo $messageType; ?>" style="max-width: 1200px; margin: 0 auto 20px auto;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="contact-container">
            <div class="contact-form-box">
                <form action="contact.php" method="POST">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Your full name" required>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required>

                    <label for="message">Message</label>
                    <textarea id="message" name="message" placeholder="How can we help you?" required></textarea>

                    <button type="submit" class="btn-submit">SUBMIT MESSAGE &rarr;</button>
                </form>
            </div>

            <div class="contact-info-wrapper">
                
                <div class="info-grid">
                    <div class="info-card">
                        <span class="icon">📍</span>
                        <h3>Visit Us</h3>
                        <p>Evergreen Studio<br>Customer Support Desk</p>
                    </div>
                    
                    <div class="info-card">
                        <span class="icon">📞</span>
                        <h3>Call Us</h3>
                        <p>Support available during business hours</p>
                    </div>
                </div>

                <div class="info-card email-card">
                    <span class="icon">✉️</span>
                    <h3>Email Us</h3>
                    <p>hello@evergreen.co</p>
                </div>

                <div class="map-container">
                    <img src="map.png" alt="Location Map" class="map-img">
                </div>
                
            </div>
        </div>
    </main>

    
    <footer>
        <div class="logo-small">Evergreen</div>
        <div class="links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Shipping & Returns</a>
            <a href="contact.php">Contact</a>
        </div>
        <div>&copy; 2026 Evergreen. All rights reserved.</div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>