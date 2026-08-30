<?php
session_start();

$serverName = "Nipuna2006";
$conn = sqlsrv_connect($serverName, array("Database" => "EcommerceDB", "CharacterSet" => "UTF-8"));

$successMessage = '';

//Handle New Order Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_submit'])) {
    $customerName = trim($_POST['customer_name'] ?? '');
    $productName = trim($_POST['product_name'] ?? '');
    $quantity = max(1, (int) ($_POST['order_quantity'] ?? 1));
    $total = (float) ($_POST['order_total'] ?? 0);
    $status = trim($_POST['order_status'] ?? 'pending');

    if ($customerName !== '' && $productName !== '' && $total >= 0) {
        if ($conn === false) {
            $successMessage = "Database connection failed.";
        } else {
            $productSql = "SELECT TOP 1 id, stock, price FROM dbo.products WHERE name = ?";
            $productStmt = sqlsrv_query($conn, $productSql, array($productName));
            $product = $productStmt !== false ? sqlsrv_fetch_array($productStmt, SQLSRV_FETCH_ASSOC) : null;

            if ($product) {
                if ((int) $product['stock'] < $quantity) {
                    $successMessage = 'Not enough stock available for "' . htmlspecialchars($productName) . '". Only ' . (int) $product['stock'] . ' item(s) left.';
                } else {
                    $productId = $product['id'];

                    $customerSql = "SELECT TOP 1 id FROM dbo.customers WHERE full_name = ?";
                    $customerStmt = sqlsrv_query($conn, $customerSql, array($customerName));
                    $customer = $customerStmt !== false ? sqlsrv_fetch_array($customerStmt, SQLSRV_FETCH_ASSOC) : null;
                    $customerId = null;

                    if ($customer) {
                        $customerId = $customer['id'];
                    } else {
                        $email = strtolower(str_replace(' ', '.', $customerName)) . '@example.com';
                        $insertCustomerSql = "INSERT INTO dbo.customers (full_name, email) VALUES (?, ?); SELECT SCOPE_IDENTITY() AS id;";
                        $insertCustomerStmt = sqlsrv_query($conn, $insertCustomerSql, array($customerName, $email));

                        if ($insertCustomerStmt !== false) {
                            sqlsrv_next_result($insertCustomerStmt);
                            $newCustomerRow = sqlsrv_fetch_array($insertCustomerStmt, SQLSRV_FETCH_ASSOC);
                            $customerId = $newCustomerRow['id'] ?? null;
                        }
                    }

                    if ($customerId) {
                        $orderSql = "INSERT INTO dbo.orders (customer_id, product_id, quantity, total, status) VALUES (?, ?, ?, ?, ?)";
                        $orderParams = array($customerId, $productId, $quantity, $total, $status);
                        $orderStmt = sqlsrv_query($conn, $orderSql, $orderParams);

                        if ($orderStmt === false) {
                            $errors = sqlsrv_errors();
                            $successMessage = "Error saving order: " . $errors[0]['message'];
                        } else {
                            decrementProductStock($conn, $productId, $quantity);
                            $successMessage = "Order created successfully!";
                        }
                    } else {
                        $successMessage = 'Unable to create or find customer record.';
                    }
                }
            } else {
                $successMessage = 'Product not found. Please type the exact product name.';
            }
        }
    } else {
        $successMessage = 'Please complete all required order fields.';
    }
}

$orders = [];
if ($conn !== false) {
    $fetchOrdersSql = "
        SELECT o.id, c.full_name AS customer_name, p.name AS product_name, o.quantity, o.created_at, o.total, o.status
        FROM dbo.orders o
        LEFT JOIN dbo.customers c ON o.customer_id = c.id
        LEFT JOIN dbo.products p ON o.product_id = p.id
        ORDER BY o.created_at DESC
    ";
    $fetchOrdersStmt = sqlsrv_query($conn, $fetchOrdersSql);

    if ($fetchOrdersStmt !== false) {
        while ($row = sqlsrv_fetch_array($fetchOrdersStmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['created_at'] instanceof DateTime) {
                $row['created_at'] = $row['created_at']->format('Y-m-d H:i:s');
            }
            $orders[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Orders</title>
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
                    <a href="admin-orders.php" class="active"><span class="admin-nav-icon">🛒</span> Orders</a>
                    <a href="admin-customers.php"><span class="admin-nav-icon">👥</span> Customers</a>
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
                    <input type="text" placeholder="Search inventory, orders...">
                </div>
                <div class="admin-topbar-actions">
                    <a href="login.php?logout=1" class="admin-logout-btn">Logout</a>
                </div>
            </header>

            <div class="admin-page-content">
                <div class="admin-page-header">
                    <div>
                        <h1>Orders</h1>
                        <p>Track purchases, shipping status, and fulfillment progress.</p>
                    </div>
                    <button type="button" class="admin-btn-primary open-modal" data-modal-target="orderModal">+ New Order</button>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="auth-message success" style="margin-bottom: 20px; padding: 15px; background: #d4edda; color: #155724; border-radius: 4px; font-weight: bold;">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="admin-controls">
                        <div class="admin-filters">
                            <select class="admin-select">
                                <option>All Orders</option>
                            </select>
                            <select class="admin-select">
                                <option>Shipping Status</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="7" class="admin-empty-state">No orders found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo (int) $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?: 'Customer'); ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name'] ?: 'Product'); ?></td>
                                            <td><?php echo (int) ($order['quantity'] ?? 1); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td class="admin-price">$<?php echo number_format((float) $order['total'], 2); ?></td>
                                            <td><span class="admin-order-status <?php echo strtolower($order['status'] ?: 'pending'); ?>"><?php echo ucfirst($order['status'] ?: 'Pending'); ?></span></td>
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

    <div class="admin-modal" id="orderModal">
        <div class="admin-modal-content" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
            <div class="admin-modal-header">
                <h3 id="orderModalTitle">New Order</h3>
                <button type="button" class="admin-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <form method="POST" action="admin-orders.php" data-admin-form="order">
                    <input type="hidden" name="order_submit" value="1">
                    <div class="admin-form-row">
                        <div class="admin-modal-field">
                            <label for="order-customer">Customer Name</label>
                            <input id="order-customer" name="customer_name" type="text" placeholder="Sarah L." required>
                        </div>
                        <div class="admin-modal-field">
                            <label for="order-product">Exact Product Name</label>
                            <input id="order-product" name="product_name" type="text" placeholder="Minimalist Leather Tote" required>
                        </div>
                    </div>
                    <div class="admin-form-row">
                        <div class="admin-modal-field">
                            <label for="order-quantity">Quantity</label>
                            <input id="order-quantity" name="order_quantity" type="number" min="1" placeholder="1" required>
                        </div>
                        <div class="admin-modal-field">
                            <label for="order-total">Total</label>
                            <input id="order-total" name="order_total" type="number" min="0" step="0.01" placeholder="185.00" required>
                        </div>
                    </div>
                    <div class="admin-form-row single">
                        <div class="admin-modal-field">
                            <label for="order-status">Status</label>
                            <select id="order-status" name="order_status" required>
                                <option value="">Select status</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-modal-cancel">Cancel</button>
                        <button type="submit" class="admin-modal-submit">Create Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>