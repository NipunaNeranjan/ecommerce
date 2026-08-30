<?php
session_start();
require 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['user_role'] === 'admin' ? 'admin-dashboard.php' : 'catalog.php';
    header('Location: ' . $redirect);
    exit;
}

$message = '';
$messageType = 'error';

$conn = getDbConnection();
if (!$conn) {
    $message = 'SQL Server connection failed. Please check the SQL Server server name and database.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strtolower($email) === 'admin@evergreen.co') {
        $message = 'Admin accounts cannot be created from this page.';
    } elseif ($name === '' || $email === '' || $password === '') {
        $message = 'Please complete all fields.';
    } else {
        ensureDatabaseTables($conn);
        $checkStmt = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.users WHERE email = ?", array($email));
        if ($checkStmt !== false && sqlsrv_has_rows($checkStmt)) {
            $message = 'An account with this email already exists.';
            sqlsrv_free_stmt($checkStmt);
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer';
            $insertStmt = sqlsrv_query($conn, "INSERT INTO dbo.users (name, email, password, role) VALUES (?, ?, ?, ?)", array($name, $email, $hashedPassword, $role));
            if ($insertStmt !== false) {
                $userId = sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT CAST(SCOPE_IDENTITY() AS INT) AS id"), SQLSRV_FETCH_ASSOC)['id'];
                $customerStmt = sqlsrv_query($conn, "INSERT INTO dbo.customers (user_id, full_name, email) VALUES (?, ?, ?)", array($userId, $name, $email));
                if ($customerStmt !== false) {
                    $_SESSION['registration_success'] = 'Registration successful. Please login.';
                    header('Location: login.php');
                    exit;
                }
                $message = 'User account created, but customer record could not be created.';
            } else {
                $message = 'Unable to create your account right now.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Register to manage your orders and account.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="auth-message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="auth-form">
                <div class="auth-field">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Your full name" required>
                </div>

                <div class="auth-field">
                    <label for="register-email">Email Address</label>
                    <input type="email" id="register-email" name="email" placeholder="your@email.com" required>
                </div>

                <div class="auth-field">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" placeholder="Create a password" required>
                </div>

                <button type="submit" class="btn-primary auth-submit">Register</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </main>

    <script src="assets/Js/script.js"></script>
</body>
</html>
