<?php
session_start();

$serverName = "Nipuna2006";
$conn = sqlsrv_connect($serverName, array("Database" => "EcommerceDB", "CharacterSet" => "UTF-8"));

$successMessage = '';

//Handle ADD Product Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_submit'])) {
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
            $successMessage = "Database connection failed.";
        } else {
            // Updated SQL to include image_url
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

//Handle DELETE Product Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
    $productId = (int) ($_POST['delete_product_id'] ?? 0);
    if ($productId > 0 && $conn !== false) {
        $sql = "DELETE FROM dbo.products WHERE id = ?";
        $stmt = sqlsrv_query($conn, $sql, array($productId));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $successMessage = "Error deleting product: " . $errors[0]['message'];
        } else {
            $successMessage = "Product deleted successfully.";
        }
    }
}

//Fetch All Products to Display in the Table
$products = [];
if ($conn !== false) {
    $sql = "SELECT * FROM dbo.products ORDER BY created_at DESC";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $products[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Inventory</title>
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
                    <a href="admin-inventory.php" class="active"><span class="admin-nav-icon">📦</span> Inventory</a>
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
                        <h1>Inventory Management</h1>
                        <p>Manage products, pricing, and stock levels across all categories.</p>
                    </div>
                    <button type="button" class="admin-btn-primary open-modal" data-modal-target="productModal">+ Add New Product</button>
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
                                <option>All Categories</option>
                            </select>
                            <select class="admin-select">
                                <option>Stock Status</option>
                            </select>
                        </div>
                        <div class="admin-view-switch">
                            <button type="button">▦</button>
                            <button type="button" class="active">☷</button>
                        </div>
                    </div>

                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"><input type="checkbox"></th>
                                    <th>Image</th>
                                    <th>Product Details</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="7" class="admin-empty-state">No products found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <?php
                                        $stock = (int) ($product['stock'] ?? 0);
                                        $status = $stock <= 3 ? 'low-stock' : ($stock > 0 ? 'in-stock' : 'out-of-stock');
                                        $label = $stock <= 3 ? 'Low stock (' . $stock . ')' : ($stock > 0 ? $stock . ' in stock' : 'Out of stock');
                                        ?>
                                        <tr>
                                            <td><input type="checkbox"></td>
                                            <td><img class="admin-product-thumb" src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/50x50/cccccc/333333?text=Product'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"></td>
                                            <td>
                                                <div class="admin-product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <span class="admin-product-sku">SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td class="admin-price">$<?php echo number_format((float) $product['price'], 2); ?></td>
                                            <td><span class="admin-status <?php echo $status; ?>"><?php echo htmlspecialchars($label); ?></span></td>
                                            <td>
                                                <form method="POST" action="admin-inventory.php" class="admin-inline-form" onsubmit="return confirm('Delete this product?');">
                                                    <input type="hidden" name="delete_product_id" value="<?php echo (int) $product['id']; ?>">
                                                    <button type="submit" class="admin-delete-btn">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-pagination">
                        <span>Showing <?php echo count($products) ? '1-' . count($products) : '0'; ?> of <?php echo count($products); ?> products</span>
                        <div class="admin-page-numbers">
                            <span>‹</span>
                            <span class="active">1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>…</span>
                            <span>›</span>
                        </div>
                    </div>
                </div>
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
                <form method="POST" action="admin-inventory.php">
                    <input type="hidden" name="product_submit" value="1">
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

    <script src="assets/js/script.js"></script>
</body>
</html>