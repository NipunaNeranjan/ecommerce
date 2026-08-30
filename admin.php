<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Admin Console</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body">

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2 class="sidebar-logo">Evergreen</h2>
            <div class="sidebar-subtitle">ADMIN CONSOLE</div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="#"><span>⊞</span> Dashboard</a>
            <a href="admin.php" class="active"><span>📦</span> Inventory</a>
            <a href="#"><span>🛒</span> Orders</a>
            <a href="#"><span>👥</span> Customers</a>
            <a href="#"><span>💬</span> Messages</a>
        </nav>

        <div class="sidebar-user">
            <img src="https://via.placeholder.com/40" alt="Admin" class="user-avatar">
            <div class="user-info">
                <strong>Admin User</strong>
                <span>admin@evergreen.co</span>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-wrapper">
        
        <!-- Top Navigation Bar -->
        <header class="admin-topbar">
            <input type="text" class="admin-search" placeholder="🔍 Search inventory, orders...">
            <div class="notification-bell">🔔</div>
        </header>

        <!-- Page Content -->
        <main class="admin-main">
            <div class="admin-page-header">
                <div>
                    <h1>Inventory Management</h1>
                    <p>Manage products, pricing, and stock levels across all categories.</p>
                </div>
                <button class="btn-primary">+ Add New Product</button>
            </div>

            <!-- Inventory Data Table -->
            <div class="inventory-card">
                
                <div class="inventory-controls">
                    <div class="filters">
                        <select><option>All Categories</option></select>
                        <select><option>Stock Status</option></select>
                    </div>
                    <div class="view-toggles">
                        <button class="toggle-btn">⊞</button>
                        <button class="toggle-btn active">☷</button>
                    </div>
                </div>

                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox"></th>
                            <th>IMAGE</th>
                            <th>PRODUCT DETAILS</th>
                            <th>CATEGORY</th>
                            <th>PRICE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><img src="https://via.placeholder.com/50x50/7c4a3a/ffffff?text=Tote" alt="Tote"></td>
                            <td>
                                <strong>Minimalist Leather Tote</strong><br>
                                <span class="sku">SKU: EVG-ACC-001</span>
                            </td>
                            <td>Accessories</td>
                            <td class="bold-price">$185.00</td>
                            <td><span class="status-pill in-stock">12 in stock</span></td>
                            <td>...</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><img src="https://via.placeholder.com/50x50/d3cdc0/333333?text=Vase" alt="Vase"></td>
                            <td>
                                <strong>Ceramic Vase</strong><br>
                                <span class="sku">SKU: EVG-DEC-042</span>
                            </td>
                            <td>Home Decor</td>
                            <td class="bold-price">$65.00</td>
                            <td><span class="status-pill out-of-stock">Out of stock</span></td>
                            <td>...</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><img src="https://via.placeholder.com/50x50/a3b1a6/ffffff?text=Throw" alt="Throw"></td>
                            <td>
                                <strong>Textured Linen Throw</strong><br>
                                <span class="sku">SKU: EVG-TEX-118</span>
                            </td>
                            <td>Textiles</td>
                            <td class="bold-price">$120.00</td>
                            <td><span class="status-pill low-stock">Low stock (3)</span></td>
                            <td>...</td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="pagination">
                    <span>Showing 1-3 of 42 products</span>
                    <div class="page-numbers">
                        <span>&lt;</span>
                        <span class="active">1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>...</span>
                        <span>&gt;</span>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>