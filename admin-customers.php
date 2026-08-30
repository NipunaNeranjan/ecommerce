<?php
session_start();
require 'db_connect.php';
requireAdminAccess();

$conn = getDbConnection();
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_submit'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $location = trim($_POST['customer_location'] ?? '');

    if ($name !== '' && $email !== '') {
        $ok = addCustomerRecord($conn, $name, $email, $phone, $location);
        $successMessage = $ok ? 'Customer added successfully.' : 'Unable to add customer.';
    } else {
        $successMessage = 'Please complete all required customer fields.';
    }
}

$customers = $conn ? getAllCustomers($conn) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Customers</title>
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
                    <a href="admin-customers.php" class="active"><span class="admin-nav-icon">👥</span> Customers</a>
                    <a href="admin-messages.php"><span class="admin-nav-icon">💬</span> Messages</a>
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
                    <input type="text" placeholder="Search customers...">
                </div>
                <div class="admin-topbar-actions">
                    <a href="login.php?logout=1" class="admin-logout-btn">Logout</a>
                </div>
            </header>

            <div class="admin-page-content">
                <div class="admin-page-header">
                    <div>
                        <h1>Customers</h1>
                        <p>Manage customer accounts and purchase history.</p>
                    </div>
                    <button type="button" class="admin-btn-primary open-modal" data-modal-target="customerModal">+ Add Customer</button>
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
                                <option>All Customers</option>
                            </select>
                            <select class="admin-select">
                                <option>Member Status</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Orders</th>
                                    <th>Spent</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr><td colspan="6" class="admin-empty-state">No customers found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <?php
                                        $orderCountStmt = $conn ? sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM dbo.orders WHERE customer_id = ?", array((int) $customer['id'])) : false;
                                        $orderCountRow = $orderCountStmt !== false ? sqlsrv_fetch_array($orderCountStmt, SQLSRV_FETCH_ASSOC) : array('total' => 0);
                                        if ($orderCountStmt !== false) {
                                            sqlsrv_free_stmt($orderCountStmt);
                                        }
                                        $orderCount = (int) ($orderCountRow['total'] ?? 0);

                                        $spentStmt = $conn ? sqlsrv_query($conn, "SELECT ISNULL(SUM(total), 0) AS total FROM dbo.orders WHERE customer_id = ?", array((int) $customer['id'])) : false;
                                        $spentRow = $spentStmt !== false ? sqlsrv_fetch_array($spentStmt, SQLSRV_FETCH_ASSOC) : array('total' => 0);
                                        if ($spentStmt !== false) {
                                            sqlsrv_free_stmt($spentStmt);
                                        }
                                        $spent = (float) ($spentRow['total'] ?? 0);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['email'] ?: $customer['account_email']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['location'] ?: 'N/A'); ?></td>
                                            <td><?php echo (int) $orderCount; ?></td>
                                            <td class="admin-price">$<?php echo number_format((float) $spent, 2); ?></td>
                                            <td><span class="admin-status in-stock">Active</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="admin-modal" id="customerModal" aria-hidden="true">
        <div class="admin-modal-content" role="dialog" aria-modal="true" aria-labelledby="customerModalTitle">
            <div class="admin-modal-header">
                <h3 id="customerModalTitle">Add Customer</h3>
                <button type="button" class="admin-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <form method="POST" action="admin-customers.php" data-admin-form="customer">
                    <input type="hidden" name="customer_submit" value="1">
                    <div class="admin-form-row">
                        <div class="admin-modal-field">
                            <label for="customer-name">Full Name</label>
                            <input id="customer-name" name="customer_name" type="text" placeholder="Jane Smith" required>
                        </div>
                        <div class="admin-modal-field">
                            <label for="customer-email">Email</label>
                            <input id="customer-email" name="customer_email" type="email" placeholder="jane@example.com" required>
                        </div>
                    </div>
                    <div class="admin-form-row">
                        <div class="admin-modal-field">
                            <label for="customer-phone">Phone</label>
                            <input id="customer-phone" name="customer_phone" type="tel" placeholder="(555) 123-4567">
                        </div>
                        <div class="admin-modal-field">
                            <label for="customer-location">Location</label>
                            <input id="customer-location" name="customer_location" type="text" placeholder="Brooklyn, NY">
                        </div>
                    </div>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-modal-cancel">Cancel</button>
                        <button type="submit" class="admin-modal-submit">Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/Js/script.js"></script>
</body>
</html>
