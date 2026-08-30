<?php
session_start();
require 'db_connect.php';
requireLogin();

$conn = getDbConnection();
$products = [];
$error = '';

if ($conn) {
    $products = getAllProducts($conn);
} else {
    $error = 'Database connection failed.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Modern Essentials</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header>
        <div class="logo">Evergreen</div>
        <nav>
            <a href="index.php" class="nav-link active">Shop</a>
            <a href="catalog.php" class="nav-link">Catalog</a>
            <a href="cart.php" class="nav-link">Cart</a>
            <a href="contact.php" class="nav-link">Contact Us</a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <a href="admin-dashboard.php" class="nav-link" style="color: #044b3c; font-weight: bold;">Admin</a>
            <?php endif; ?>
            <a href="login.php?logout=1" class="nav-link">Logout</a>
        </nav>
    </header>

    <section class="hero">
        <h1>Modern Essentials</h1>
        <p>Curated pieces for a considered lifestyle. Discover our latest collection of thoughtfully designed goods.</p>
        <a href="catalog.php" class="btn-primary">Shop Now &rarr;</a>
    </section>

    <section class="featured">
        <div class="section-header">
            <h2>Featured Arrivals</h2>
            <a href="catalog.php">View All &gt;</a>
        </div>

        <?php if ($error): ?>
            <div class="auth-message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div class="auth-card" style="grid-column: 1 / -1; max-width: 100%; padding: 30px;">No products are available yet. Add inventory from the admin panel.</div>
            <?php else: ?>
                <?php foreach (array_slice($products, 0, 4) as $product): ?>
                    <?php $stock = (int) ($product['stock'] ?? 0); ?>
                    <div class="product-card">
                        <span class="badge"><?php echo $stock <= 3 ? 'LOW STOCK' : 'NEW'; ?></span>
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/300x350/cccccc/333333?text=Product'); ?>" class="product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <span class="category"><?php echo htmlspecialchars($product['category']); ?></span>
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="price">$<?php echo number_format((float) ($product['price'] ?? 0), 2); ?></div>
                        <form method="post" action="catalog.php">
                            <input type="hidden" name="add_to_cart" value="<?php echo (int) $product['id']; ?>">
                            <button type="submit" class="btn-outline">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

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

    <script src="assets/Js/script.js"></script>
</body>
</html>