<?php
session_start();
require 'db_connect.php';
requireLogin();

$conn = getDbConnection();
if (!$conn) {
    $error = 'Database connection failed.';
    $products = [];
} else {
    $error = '';
    $products = getAllProducts($conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productId = (int) $_POST['add_to_cart'];
    if ($productId > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $product = getProductById($conn, $productId);
        if (!$product) {
            $error = 'Product not found.';
        } else {
            $currentQty = (int) ($_SESSION['cart'][$productId] ?? 0);
            $availableStock = (int) ($product['stock'] ?? 0);
            $newQty = $currentQty + 1;

            if ($newQty > $availableStock) {
                $error = 'Only ' . $availableStock . ' item(s) left in stock for ' . htmlspecialchars($product['name']) . '.';
            } else {
                $_SESSION['cart'][$productId] = $newQty;
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
    <title>Evergreen - Catalog</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="logo">Evergreen</div>
        <nav>
            <a href="index.php" class="nav-link">Shop</a>
            <a href="catalog.php" class="nav-link active">Catalog</a>
            <a href="cart.php" class="nav-link">Cart</a>
            <a href="contact.php" class="nav-link">Contact Us</a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <a href="admin-dashboard.php" class="nav-link" style="color: #044b3c; font-weight: bold;">Admin</a>
            <?php endif; ?>
            <a href="login.php?logout=1" class="nav-link">Logout</a>
        </nav>
    </header>

    <main class="catalog-main">
        <div class="catalog-header">
            <h1 class="dark-green-title">Curated Essentials</h1>
            <p>Discover our collection of minimalist home goods, designed for intentional living and enduring quality.</p>
        </div>

        <?php if ($error): ?>
            <div class="auth-message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="catalog-grid">
            <?php if (empty($products)): ?>
                <div class="auth-card" style="grid-column: 1 / -1; max-width: 100%; padding: 30px;">No products available yet. Add products from the admin panel.</div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $stock = (int) ($product['stock'] ?? 0);
                    $badge = $stock <= 3 ? '<span class="badge badge-low-stock">LOW STOCK</span>' : '<span class="badge">NEW</span>';
                    ?>
                    <div class="product-card">
                        <?php echo $badge; ?>
                        <span class="wishlist-icon">🤍</span>
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/300x300/cccccc/333333?text=Product'); ?>" class="product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <span class="category"><?php echo htmlspecialchars($product['category']); ?></span>
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="price">$<?php echo number_format((float) $product['price'], 2); ?></div>
                        <form method="post" action="catalog.php">
                            <input type="hidden" name="add_to_cart" value="<?php echo (int) $product['id']; ?>">
                            <button type="submit" class="btn-outline">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="logo-small">Evergreen</div>
        <div class="links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Shipping</a>
            <a href="#">Returns</a>
        </div>
        <div>&copy; 2026 Evergreen Minimalist Essentials</div>
    </footer>

    <script src="assets/Js/script.js"></script>
</body>
</html>