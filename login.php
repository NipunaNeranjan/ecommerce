<?php
session_start();
require 'db_connect.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['user_role'] === 'admin' ? 'admin-dashboard.php' : 'catalog.php';
    header('Location: ' . $redirect);
    exit;
}

$message = '';
$messageType = 'error';

if (!empty($_SESSION['registration_success'])) {
    $message = $_SESSION['registration_success'];
    $messageType = 'success';
    unset($_SESSION['registration_success']);
}

$conn = getDbConnection();
if (!$conn) {
    $message = 'SQL Server connection failed. Please check the SQL Server server name and database.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Please enter both email and password.';
    } else {
        ensureDatabaseTables($conn);
        $stmt = sqlsrv_query($conn, "SELECT TOP 1 id, name, password, role FROM dbo.users WHERE email = ?", array($email));
        if ($stmt !== false) {
            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $user['role'] ?? 'customer';

                $redirect = $_SESSION['user_role'] === 'admin' ? 'admin-dashboard.php' : 'catalog.php';
                header('Location: ' . $redirect);
                exit;
            }

            $message = 'Invalid email or password.';
        } else {
            $message = 'Unable to process login request.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Login</h1>
                <p>Welcome back! Sign in to continue.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="auth-message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">
                <div class="auth-field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required>
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-primary auth-submit">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Create one</a></p>
            </div>
        </div>
    </main>

    <script src="assets/Js/script.js"></script>
</body>
</html>
