<?php
session_start();
require 'db_connect.php';

$serverName = "Nipuna2006";
$conn = sqlsrv_connect($serverName, array("Database" => "EcommerceDB", "CharacterSet" => "UTF-8"));

$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_product_submit'])) {
    $name = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['product_category'] ?? '');
    $sku = trim($_POST['product_sku'] ?? '');
    $price = (float) ($_POST['product_price'] ?? 0);
    $stock = (int) ($_POST['product_stock'] ?? 0);
    $description = trim($_POST['product_desc'] ?? '');
    $imageUrl = trim($_POST['product_image'] ?? '');

    if ($imageUrl === '') {
        $imageUrl = 'https://via.placeholder.com/300x300/e8eceb/333333?text=' . urlencode($name);
    }

    if ($name !== '' && $category !== '' && $sku !== '') {
        if ($conn === false) {
            $successMessage = "Database connection failed: " . print_r(sqlsrv_errors(), true);
        } else {
            $sql = "INSERT INTO dbo.products (name, category, sku, price, stock, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = array($name, $category, $sku, $price, $stock, $description, $imageUrl);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $successMessage = "Error saving product: " . $errors[0]['message'];
            } else {
                $successMessage = "Product added successfully!";
            }
        }
    } else {
        $successMessage = 'Please complete all required product fields.';
    }
}

$stats = getDashboardStats($conn);
$recentOrders = [];
$stockAlerts = [];

if ($conn !== false) {
    $recentSql = "
        SELECT TOP 4 o.id, c.full_name AS customer_name, p.name AS product_name, o.quantity, o.total, o.status
        FROM dbo.orders o
        LEFT JOIN dbo.customers c ON c.id = o.customer_id
        LEFT JOIN dbo.products p ON p.id = o.product_id
        ORDER BY o.created_at DESC
    ";
    $recentStmt = sqlsrv_query($conn, $recentSql);
    if ($recentStmt !== false) {
        while ($row = sqlsrv_fetch_array($recentStmt, SQLSRV_FETCH_ASSOC)) {
            $recentOrders[] = $row;
        }
    }

    $stockSql = "SELECT category, AVG(CAST(stock AS FLOAT)) AS avg_stock FROM dbo.products GROUP BY category ORDER BY category ASC";
    $stockRows = sqlsrv_query($conn, $stockSql);
    if ($stockRows !== false) {
        while ($row = sqlsrv_fetch_array($stockRows, SQLSRV_FETCH_ASSOC)) {
            $avg = (float) ($row['avg_stock'] ?? 0);
            $percent = max(0, min(100, (int) round(($avg / 20) * 100)));
            $stockAlerts[] = [
                'category' => $row['category'],
                'percent' => $percent
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Dashboard</title>
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
                    <a href="admin-dashboard.php" class="active"><span class="admin-nav-icon">⊞</span> Dashboard</a>
                    <a href="admin-inventory.php"><span class="admin-nav-icon">📦</span> Inventory</a>
                    <a href="admin-orders.php"><span class="admin-nav-icon">🛒</span> Orders</a>
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
                <div class="admin-topbar-actions">
                    <a href="login.php?logout=1" class="admin-logout-btn">Logout</a>
                </div>
            </header>

            <div class="admin-page-content">
                <div class="admin-page-header">
                    <div>
                        <h1>Dashboard Overview</h1>
                        <p>Monitor your storefront performance and stock health.</p>
                    </div>
                    <button type="button" class="admin-btn-primary open-modal" data-modal-target="productModal">+ Add New Product</button>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="auth-message success" style="margin-bottom: 20px; padding: 15px; background: #d4edda; color: #155724; border-radius: 4px; font-weight: bold;">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <section class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="admin-stat-label">Revenue</div>
                        <p class="admin-stat-value">$<?php echo number_format((float) $stats['revenue'], 1); ?>K</p>
                        <span class="admin-stat-trend">+12.4% vs last month</span>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-label">Orders</div>
                        <p class="admin-stat-value"><?php echo number_format((int) $stats['orders_count']); ?></p>
                        <span class="admin-stat-trend">+8.3% vs last month</span>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-label">Visitors</div>
                        <p class="admin-stat-value">18.4K</p>
                        <span class="admin-stat-trend">+15.6% vs last month</span>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-label">Inventory</div>
                        <p class="admin-stat-value"><?php echo $stats['low_stock_count'] > 0 ? (100 - min(100, ($stats['low_stock_count'] * 10))) . '%' : '100%'; ?></p>
                        <span class="admin-stat-trend"><?php echo $stats['low_stock_count']; ?> products low stock</span>
                    </div>
                </section>

                <section class="admin-dashboard-grid">
                    <div class="admin-card">
                        <div class="admin-panel-header">
                            <h3>Recent Orders</h3>
                            <a href="admin-orders.php">View all</a>
                        </div>
                        <ul class="admin-order-list">
                            <?php if (empty($recentOrders)): ?>
                                <li class="admin-order-item">
                                    <div class="admin-order-meta">
                                        <strong>No orders yet</strong>
                                        <span>Your recent orders will appear here.</span>
                                    </div>
                                </li>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <?php
                                    $statusClass = strtolower($order['status'] ?? 'pending');
                                    if ($statusClass === 'shipped') $statusClass = 'shipped';
                                    elseif ($statusClass === 'processing') $statusClass = 'processing';
                                    else $statusClass = 'pending';
                                    ?>
                                    <li class="admin-order-item">
                                        <div class="admin-order-meta">
                                            <strong>#<?php echo (int) $order['id']; ?> - <?php echo htmlspecialchars($order['customer_name'] ?: 'Customer'); ?></strong>
                                            <span><?php echo htmlspecialchars($order['product_name'] ?: 'Product'); ?></span>
                                        </div>
                                        <div class="admin-order-amount">$<?php echo number_format((float) $order['total'], 2); ?></div>
                                        <span class="admin-order-status <?php echo $statusClass; ?>"><?php echo ucfirst($order['status'] ?: 'Pending'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="admin-card">
                        <div class="admin-panel-header">
                            <h3>Stock Alerts</h3>
                            <a href="admin-inventory.php">Manage</a>
                        </div>
                        <ul class="admin-bar-list">
                            <?php if (empty($stockAlerts)): ?>
                                <li class="admin-bar-item">
                                    <div class="admin-bar-row"><span>No stock data</span><span>0%</span></div>
                                    <div class="admin-progress"><span style="width: 0%;"></span></div>
                                </li>
                            <?php else: ?>
                                <?php foreach ($stockAlerts as $alert): ?>
                                    <li class="admin-bar-item">
                                        <div class="admin-bar-row"><span><?php echo htmlspecialchars($alert['category']); ?></span><span><?php echo $alert['percent']; ?>%</span></div>
                                        <div class="admin-progress"><span style="width: <?php echo $alert['percent']; ?>%;"></span></div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div class="admin-modal" id="productModal">
        <div class="admin-modal-content" role="dialog" aria-modal="true" aria-labelledby="productModalTitle">
            <div class="admin-modal-header">
                <h3 id="productModalTitle">Add New Product</h3>
                <button type="button" class="admin-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <form method="POST" action="admin-dashboard.php">
                    <input type="hidden" name="dashboard_product_submit" value="1">
                    <div class="admin-form-row">
                        <div class="admin-modal-field">
                            <label for="product-name">Product Name</label>
                            <input id="product-name" name="product_name" type="text" placeholder="Minimalist Leather Tote" required>
                        </div>
                        <div class="admin-modal-field">
                            <label for="product-category">Category</label>
                            <select id="product-category" name="product_category" required>
                                <option value="">Select category</option>
                                <option>Accessories</option>
                                <option>Home Decor</option>
                                <option>Textiles</option>
                                <option>Furniture</option>
                            </select>
                        </div>
                    </div>
                    <div class="admin-form-row">
                        <div class="admin-modal-field">
                            <label for="product-sku">SKU</label>
                            <input id="product-sku" name="product_sku" type="text" placeholder="EVG-ACC-010" required>
                        </div>
                        <div class="admin-modal-field">
                            <label for="product-price">Price</label>
                            <input id="product-price" name="product_price" type="number" min="0" step="0.01" placeholder="185.00" required>
                        </div>
                    </div>
                    <div class="admin-form-row single">
                        <div class="admin-modal-field">
                            <label for="product-stock">Stock Quantity</label>
                            <input id="product-stock" name="product_stock" type="number" min="0" placeholder="12" required>
                        </div>
                    </div>
                    
                    <!-- New Image URL Field -->
                    <div class="admin-form-row single">
                        <div class="admin-modal-field">
                            <label for="product-image">Image URL</label>
                            <input id="product-image" name="product_image" type="url" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    
                    <div class="admin-form-row single">
                        <div class="admin-modal-field">
                            <label for="product-desc">Description</label>
                            <textarea id="product-desc" name="product_desc" placeholder="Describe the product..."></textarea>
                        </div>
                    </div>
                    <div class="admin-modal-actions">
                        <button type="button" class="admin-modal-cancel">Cancel</button>
                        <button type="submit" class="admin-modal-submit">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Fixed the capital 'J' in your js folder path -->
    <script src="assets/Js/script.js"></script>
</body>
</html>