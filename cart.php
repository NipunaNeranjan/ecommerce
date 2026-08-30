<?php
session_start();
require 'db_connect.php';
requireLogin();

$checkoutMessage = '';
$checkoutType = 'error';
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item'])) {
        $id = (int) $_POST['remove_item'];
        unset($_SESSION['cart'][$id]);
        header('Location: cart.php');
        exit;
    }

    if (isset($_POST['update_qty'])) {
        $id = (int) $_POST['update_qty'];
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        if (isset($_SESSION['cart'][$id])) {
            $product = getProductById($conn, $id);
            $availableStock = $product ? (int) ($product['stock'] ?? 0) : 0;
            if ($availableStock <= 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id] = min($qty, $availableStock);
            }
        }
        header('Location: cart.php');
        exit;
    }

    if (isset($_POST['place_order'])) {
        $paymentMethod = strtolower(trim($_POST['payment_method'] ?? 'cod'));
        $paymentMethod = in_array($paymentMethod, ['cod', 'card', 'bank_transfer'], true) ? $paymentMethod : 'cod';

        if (!$conn || empty($_SESSION['cart'])) {
            $checkoutMessage = 'Your cart is empty. Add products before placing an order.';
        } else {
            $stockIssue = false;
            $stockIssueText = '';

            foreach ($_SESSION['cart'] as $productId => $qty) {
                $product = getProductById($conn, $productId);
                if (!$product) {
                    $stockIssue = true;
                    $stockIssueText = 'One or more products are no longer available.';
                    break;
                }

                if ((int) $qty > (int) ($product['stock'] ?? 0)) {
                    $stockIssue = true;
                    $stockIssueText = 'Only ' . (int) ($product['stock'] ?? 0) . ' item(s) left in stock for ' . htmlspecialchars($product['name']) . '.';
                    break;
                }
            }

            if ($stockIssue) {
                $checkoutMessage = $stockIssueText;
            } else {
                $userName = $_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? 'Customer');
                $userEmail = $_SESSION['user_email'] ?? strtolower(str_replace(' ', '.', $userName)) . '@example.com';

                $customerResult = sqlsrv_query($conn, "SELECT TOP 1 id, full_name FROM dbo.customers WHERE user_id = ? OR email = ?", array((int) ($_SESSION['user_id'] ?? 0), $userEmail));
                $customerRow = $customerResult !== false ? sqlsrv_fetch_array($customerResult, SQLSRV_FETCH_ASSOC) : null;
                if ($customerResult !== false) {
                    sqlsrv_free_stmt($customerResult);
                }

                if (!$customerRow) {
                    $createCustomer = sqlsrv_query($conn, "INSERT INTO dbo.customers (user_id, full_name, email) VALUES (?, ?, ?)", array((int) ($_SESSION['user_id'] ?? 0), $userName, $userEmail));
                    if ($createCustomer !== false) {
                        $customerResult = sqlsrv_query($conn, "SELECT TOP 1 id, full_name FROM dbo.customers WHERE user_id = ? OR email = ?", array((int) ($_SESSION['user_id'] ?? 0), $userEmail));
                        $customerRow = $customerResult !== false ? sqlsrv_fetch_array($customerResult, SQLSRV_FETCH_ASSOC) : null;
                        if ($customerResult !== false) {
                            sqlsrv_free_stmt($customerResult);
                        }
                    }
                }

                if ($customerRow) {
                    $placed = true;
                    foreach ($_SESSION['cart'] as $productId => $qty) {
                        $product = getProductById($conn, $productId);
                        if (!$product) {
                            $placed = false;
                            continue;
                        }

                        $lineTotal = (float) $product['price'] * (int) $qty;
                        $orderResult = sqlsrv_query(
                            $conn,
                            "INSERT INTO dbo.orders (customer_id, product_id, quantity, total, status, payment_method) VALUES (?, ?, ?, ?, ?, ?)",
                            array((int) $customerRow['id'], (int) $productId, (int) $qty, $lineTotal, 'pending', $paymentMethod)
                        );

                        if ($orderResult === false) {
                            $placed = false;
                        } else {
                            decrementProductStock($conn, $productId, $qty);
                        }
                    }

                    if ($placed) {
                        $_SESSION['cart'] = [];
                        $checkoutMessage = 'Order placed successfully. We will contact you soon.';
                        $checkoutType = 'success';
                    } else {
                        $checkoutMessage = 'Unable to place the order. Please try again.';
                    }
                } else {
                    $checkoutMessage = 'Unable to create a customer record for this order.';
                }
            }
        }
    }
}

$conn = getDbConnection();
$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$subtotal = 0.0;

if ($conn) {
    foreach ($cart as $productId => $qty) {
        $product = getProductById($conn, $productId);
        if ($product) {
            $lineTotal = (float) $product['price'] * (int) $qty;
            $subtotal += $lineTotal;
            $cartItems[] = [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'price' => (float) $product['price'],
                'image_url' => $product['image_url'],
                'quantity' => (int) $qty,
                'line_total' => $lineTotal,
                'category' => $product['category']
            ];
        }
    }
}

$tax = $subtotal * 0.08;
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen - Shopping Cart</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="logo">Evergreen</div>
        <nav>
            <a href="index.php" class="nav-link">Shop</a>
            <a href="catalog.php" class="nav-link">Catalog</a>
            <a href="cart.php" class="nav-link active">Cart</a>
            <a href="contact.php" class="nav-link">Contact Us</a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <a href="admin-dashboard.php" class="nav-link" style="color: #044b3c; font-weight: bold;">Admin</a>
            <?php endif; ?>
            <a href="login.php?logout=1" class="nav-link">Logout</a>
        </nav>
        <div>
        </div>
    </header>

    <main class="cart-main">
        <h1 class="cart-title">Shopping Cart</h1>

        <?php if ($checkoutMessage !== ''): ?>
            <div class="auth-message <?php echo $checkoutType; ?>" style="max-width: 900px; margin: 0 auto 20px auto;">
                <?php echo htmlspecialchars($checkoutMessage); ?>
            </div>
        <?php endif; ?>

        <div class="cart-container">
            <div class="cart-items-wrapper">
                <?php if (empty($cartItems)): ?>
                    <div class="auth-card" style="margin: 20px; padding: 30px; width: auto; max-width: none;">
                        Your cart is empty. Add products from the catalog.
                    </div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-img">
                            <div class="item-details">
                                <div class="item-header">
                                    <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                <p class="item-variant"><?php echo htmlspecialchars($item['category']); ?></p>
                                <div class="item-actions">
                                    <form method="post" action="cart.php" class="qty-form">
                                        <input type="hidden" name="update_qty" value="<?php echo $item['id']; ?>">
                                        <div class="qty-selector">
                                            <button type="submit" class="qty-btn" name="qty" value="<?php echo max(1, $item['quantity'] - 1); ?>">&minus;</button>
                                            <span class="qty-num"><?php echo $item['quantity']; ?></span>
                                            <button type="submit" class="qty-btn" name="qty" value="<?php echo $item['quantity'] + 1; ?>">&plus;</button>
                                        </div>
                                    </form>
                                    <form method="post" action="cart.php" style="display:inline;">
                                        <input type="hidden" name="remove_item" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="delete-btn" aria-label="Remove item">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Calculated at checkout</span>
                </div>
                <div class="summary-row">
                    <span>Estimated Tax</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>

                <?php if (!empty($cartItems)): ?>
                    <div id="checkout-panel" class="checkout-panel" style="display: none; margin-top: 20px;">
                        <form method="POST" action="cart.php">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method" style="width: 100%; padding: 10px; margin: 10px 0 15px; border-radius: 6px; border: 1px solid #dfe3ea;">
                                <option value="cod">Cash on Delivery (COD)</option>
                            </select>
                            <button type="submit" name="place_order" value="1" class="btn-checkout">Place Order</button>
                        </form>
                    </div>
                    <button type="button" class="btn-checkout" id="proceed-checkout-btn">Proceed to Checkout</button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="assets/Js/script.js"></script>
</body>
</html>