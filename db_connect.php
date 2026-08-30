<?php
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdminAccess() {
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: login.php');
        exit;
    }
}

function getDbConnection() {
    $serverName = "Nipuna2006";
    $connectionInfo = array(
        "Database" => "EcommerceDB",
        "CharacterSet" => "UTF-8",
        "ReturnDatesAsStrings" => true
    );

    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn === false) {
        $masterInfo = array(
            "Database" => "master",
            "CharacterSet" => "UTF-8",
            "ReturnDatesAsStrings" => true
        );

        $masterConn = sqlsrv_connect($serverName, $masterInfo);
        if ($masterConn !== false) {
            $dbCheck = sqlsrv_query($masterConn, "SELECT 1 FROM sys.databases WHERE name = ?", array("EcommerceDB"));
            if ($dbCheck !== false && sqlsrv_has_rows($dbCheck) === false) {
                sqlsrv_query($masterConn, "CREATE DATABASE EcommerceDB");
            }
            if ($dbCheck !== false) {
                sqlsrv_free_stmt($dbCheck);
            }
            sqlsrv_close($masterConn);
            $conn = sqlsrv_connect($serverName, $connectionInfo);
        }
    }

    return $conn;
}

function ensureUsersTable($conn) {
    if (!$conn) {
        return false;
    }

    $sql = "IF OBJECT_ID('dbo.users', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.users (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    name NVARCHAR(255) NOT NULL,
                    email NVARCHAR(255) NOT NULL UNIQUE,
                    password NVARCHAR(255) NOT NULL,
                    role NVARCHAR(20) NOT NULL DEFAULT 'customer',
                    created_at DATETIME2 DEFAULT GETDATE()
                );
            END";

    return sqlsrv_query($conn, $sql) !== false;
}

function ensureOrdersPaymentMethodColumn($conn) {
    if (!$conn) {
        return false;
    }

    $sql = "IF COL_LENGTH('dbo.orders', 'payment_method') IS NULL
            BEGIN
                ALTER TABLE dbo.orders ADD payment_method NVARCHAR(30) NOT NULL DEFAULT 'cod';
            END";

    return sqlsrv_query($conn, $sql) !== false;
}

function ensureDatabaseTables($conn) {
    if (!$conn) {
        return false;
    }

    if (!ensureUsersTable($conn)) {
        return false;
    }

    $tables = array(
        "products" => "IF OBJECT_ID('dbo.products', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.products (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    name NVARCHAR(255) NOT NULL,
                    sku NVARCHAR(100) NOT NULL UNIQUE,
                    category NVARCHAR(100) NOT NULL,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    stock INT NOT NULL DEFAULT 0,
                    description NVARCHAR(MAX),
                    image_url NVARCHAR(255) DEFAULT '',
                    status NVARCHAR(30) NOT NULL DEFAULT 'in-stock',
                    created_at DATETIME2 DEFAULT GETDATE()
                );
            END",
        "customers" => "IF OBJECT_ID('dbo.customers', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.customers (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    user_id INT NULL,
                    full_name NVARCHAR(255) NOT NULL,
                    email NVARCHAR(255) NOT NULL UNIQUE,
                    phone NVARCHAR(100) DEFAULT '',
                    location NVARCHAR(255) DEFAULT '',
                    created_at DATETIME2 DEFAULT GETDATE(),
                    CONSTRAINT FK_customers_users FOREIGN KEY (user_id) REFERENCES dbo.users(id)
                );
            END",
        "orders" => "IF OBJECT_ID('dbo.orders', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.orders (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    customer_id INT NOT NULL,
                    product_id INT NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    status NVARCHAR(30) NOT NULL DEFAULT 'pending',
                    payment_method NVARCHAR(30) NOT NULL DEFAULT 'cod',
                    created_at DATETIME2 DEFAULT GETDATE(),
                    CONSTRAINT FK_orders_customers FOREIGN KEY (customer_id) REFERENCES dbo.customers(id),
                    CONSTRAINT FK_orders_products FOREIGN KEY (product_id) REFERENCES dbo.products(id)
                );
            END",
        "contact_messages" => "IF OBJECT_ID('dbo.contact_messages', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.contact_messages (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    name NVARCHAR(255) NOT NULL,
                    email NVARCHAR(255) NOT NULL,
                    message NVARCHAR(MAX) NOT NULL,
                    created_at DATETIME2 DEFAULT GETDATE()
                );
            END"
    );

    foreach ($tables as $sql) {
        if (sqlsrv_query($conn, $sql) === false) {
            $errors = sqlsrv_errors();
            return false;
        }
    }

    return ensureOrdersPaymentMethodColumn($conn);
}

function ensureAdminUser($conn) {
    if (!$conn) {
        return false;
    }

    $email = 'admin@evergreen.co';
    $result = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.users WHERE email = ?", array($email));
    if ($result !== false && sqlsrv_has_rows($result)) {
        sqlsrv_query($conn, "UPDATE dbo.users SET role = 'admin' WHERE email = ?", array($email));
        if ($result !== false) {
            sqlsrv_free_stmt($result);
        }
        return true;
    }

    if ($result !== false) {
        sqlsrv_free_stmt($result);
    }

    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $query = "INSERT INTO dbo.users (name, email, password, role) VALUES (?, ?, ?, 'admin')";
    $params = array('Admin User', $email, $passwordHash);

    return sqlsrv_query($conn, $query, $params) !== false;
}

function seedInitialProducts($conn) {
    if (!$conn) {
        return false;
    }

    $count = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM dbo.products");
    if ($count !== false) {
        $row = sqlsrv_fetch_array($count, SQLSRV_FETCH_ASSOC);
        if ($row && (int) $row['total'] > 0) {
            sqlsrv_free_stmt($count);
            return true;
        }
        sqlsrv_free_stmt($count);
    }

    $products = array(
        array('Minimalist Leather Tote', 'EVG-ACC-001', 'Accessories', 185.00, 12, 'Soft leather tote for everyday carry.', 'https://via.placeholder.com/300x300/7c4a3a/ffffff?text=Tote'),
        array('Ceramic Vase', 'EVG-DEC-042', 'Home Decor', 65.00, 0, 'Handcrafted ceramic vase for modern interiors.', 'https://via.placeholder.com/300x300/d3cdc0/333333?text=Vase'),
        array('Textured Linen Throw', 'EVG-TEX-118', 'Textiles', 120.00, 3, 'Textured throw designed for warmth and style.', 'https://via.placeholder.com/300x300/a3b1a6/ffffff?text=Throw')
    );

    foreach ($products as $product) {
        $sql = "INSERT INTO dbo.products (name, sku, category, price, stock, description, image_url)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = array($product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6]);
        if (sqlsrv_query($conn, $sql, $params) === false) {
            return false;
        }
    }

    return true;
}

function getAllProducts($conn) {
    if (!$conn) {
        return array();
    }

    $result = sqlsrv_query($conn, "SELECT * FROM dbo.products ORDER BY created_at DESC");
    if ($result === false) {
        return array();
    }

    $items = array();
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
    sqlsrv_free_stmt($result);

    return $items;
}

function getProductsByCategory($conn, $category = null) {
    if (!$conn) {
        return array();
    }

    if ($category && $category !== 'All Categories') {
        $result = sqlsrv_query($conn, "SELECT * FROM dbo.products WHERE category = ? ORDER BY created_at DESC", array($category));
        if ($result === false) {
            return array();
        }

        $items = array();
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $items[] = $row;
        }
        sqlsrv_free_stmt($result);
        return $items;
    }

    return getAllProducts($conn);
}

function getProductById($conn, $id) {
    if (!$conn || empty($id)) {
        return null;
    }

    $result = sqlsrv_query($conn, "SELECT TOP 1 * FROM dbo.products WHERE id = ?", array((int) $id));
    if ($result === false) {
        return null;
    }

    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($result);

    return $row ?: null;
}

function getAllCustomers($conn) {
    if (!$conn) {
        return array();
    }

    $sql = "SELECT c.*, u.email AS account_email
            FROM dbo.customers c
            LEFT JOIN dbo.users u ON u.id = c.user_id
            ORDER BY c.created_at DESC";
    $result = sqlsrv_query($conn, $sql);
    if ($result === false) {
        return array();
    }

    $items = array();
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
    sqlsrv_free_stmt($result);

    return $items;
}

function getAllOrders($conn) {
    if (!$conn) {
        return array();
    }

    $sql = "SELECT o.*, p.name AS product_name, c.full_name AS customer_name
            FROM dbo.orders o
            LEFT JOIN dbo.products p ON p.id = o.product_id
            LEFT JOIN dbo.customers c ON c.id = o.customer_id
            ORDER BY o.created_at DESC";
    $result = sqlsrv_query($conn, $sql);
    if ($result === false) {
        return array();
    }

    $items = array();
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
    sqlsrv_free_stmt($result);

    return $items;
}

function getAllMessages($conn) {
    if (!$conn) {
        return array();
    }

    $result = sqlsrv_query($conn, "SELECT * FROM dbo.contact_messages ORDER BY created_at DESC");
    if ($result === false) {
        return array();
    }

    $items = array();
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
    sqlsrv_free_stmt($result);

    return $items;
}

function getDashboardStats($conn) {
    if (!$conn) {
        return array(
            'revenue' => 0,
            'orders_count' => 0,
            'customers_count' => 0,
            'low_stock_count' => 0
        );
    }

    $revenue = sqlsrv_query($conn, "SELECT ISNULL(SUM(total), 0) AS total FROM dbo.orders");
    $revenueRow = $revenue !== false ? sqlsrv_fetch_array($revenue, SQLSRV_FETCH_ASSOC) : array('total' => 0);
    if ($revenue !== false) {
        sqlsrv_free_stmt($revenue);
    }

    $orders = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM dbo.orders");
    $ordersRow = $orders !== false ? sqlsrv_fetch_array($orders, SQLSRV_FETCH_ASSOC) : array('total' => 0);
    if ($orders !== false) {
        sqlsrv_free_stmt($orders);
    }

    $customers = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM dbo.customers");
    $customersRow = $customers !== false ? sqlsrv_fetch_array($customers, SQLSRV_FETCH_ASSOC) : array('total' => 0);
    if ($customers !== false) {
        sqlsrv_free_stmt($customers);
    }

    $lowStock = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM dbo.products WHERE stock <= 3");
    $lowRow = $lowStock !== false ? sqlsrv_fetch_array($lowStock, SQLSRV_FETCH_ASSOC) : array('total' => 0);
    if ($lowStock !== false) {
        sqlsrv_free_stmt($lowStock);
    }

    return array(
        'revenue' => (float) ($revenueRow['total'] ?? 0),
        'orders_count' => (int) ($ordersRow['total'] ?? 0),
        'customers_count' => (int) ($customersRow['total'] ?? 0),
        'low_stock_count' => (int) ($lowRow['total'] ?? 0)
    );
}

function addContactMessage($conn, $name, $email, $message) {
    if (!$conn || trim($name) === '' || trim($email) === '' || trim($message) === '') {
        return false;
    }

    $query = "INSERT INTO dbo.contact_messages (name, email, message) VALUES (?, ?, ?)";
    $params = array($name, $email, $message);
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        return false;
    }

    sqlsrv_free_stmt($stmt);
    return true;
}

function addCustomerRecord($conn, $name, $email, $phone = '', $location = '') {
    if (!$conn || trim($name) === '' || trim($email) === '') {
        return false;
    }

    $query = "INSERT INTO dbo.customers (full_name, email, phone, location) VALUES (?, ?, ?, ?)";
    $params = array($name, $email, $phone, $location);
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        return false;
    }

    sqlsrv_free_stmt($stmt);
    return true;
}

function addOrderRecord($conn, $customerName, $productId, $quantity, $total, $status, $paymentMethod = 'cod') {
    if (!$conn || empty($productId) || empty($quantity)) {
        return false;
    }

    $customerRow = sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.customers WHERE full_name = ?", array($customerName)), SQLSRV_FETCH_ASSOC);
    if (!$customerRow) {
        $email = strtolower(str_replace(' ', '.', trim($customerName))) . '@example.com';
        addCustomerRecord($conn, $customerName, $email);
        $customerRow = sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.customers WHERE full_name = ?", array($customerName)), SQLSRV_FETCH_ASSOC);
    }

    if (!$customerRow) {
        return false;
    }

    $query = "INSERT INTO dbo.orders (customer_id, product_id, quantity, total, status, payment_method) VALUES (?, ?, ?, ?, ?, ?)";
    $params = array((int) $customerRow['id'], (int) $productId, (int) $quantity, (float) $total, $status, $paymentMethod);
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        return false;
    }

    sqlsrv_free_stmt($stmt);
    return true;
}

function addProductRecord($conn, $name, $category, $sku, $price, $stock, $description = '') {
    if (!$conn || trim($name) === '' || trim($sku) === '') {
        return false;
    }

    $image = 'https://via.placeholder.com/300x300?text=' . urlencode($name);
    $status = $stock > 0 ? 'in-stock' : 'out-of-stock';

    $query = "INSERT INTO dbo.products (name, sku, category, price, stock, description, image_url, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $params = array($name, $sku, $category, (float) $price, (int) $stock, $description, $image, $status);
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        return false;
    }

    sqlsrv_free_stmt($stmt);
    return true;
}

function deleteProductRecord($conn, $id) {
    if (!$conn || empty($id)) {
        return false;
    }

    $productId = (int) $id;

    $deleteOrders = sqlsrv_query($conn, "DELETE FROM dbo.orders WHERE product_id = ?", array($productId));
    if ($deleteOrders === false) {
        return false;
    }

    $deleteProduct = sqlsrv_query($conn, "DELETE FROM dbo.products WHERE id = ?", array($productId));
    if ($deleteProduct === false) {
        return false;
    }

    return true;
}

function hasSufficientStock($conn, $productId, $quantity) {
    if (!$conn || empty($productId) || (int) $quantity <= 0) {
        return false;
    }

    $product = getProductById($conn, (int) $productId);
    if (!$product) {
        return false;
    }

    return (int) $product['stock'] >= (int) $quantity;
}

function decrementProductStock($conn, $productId, $quantity) {
    if (!$conn || empty($productId) || (int) $quantity <= 0) {
        return false;
    }

    $product = getProductById($conn, (int) $productId);
    if (!$product) {
        return false;
    }

    $newStock = max(0, (int) $product['stock'] - (int) $quantity);
    $stmt = sqlsrv_query($conn, "UPDATE dbo.products SET stock = ? WHERE id = ?", array($newStock, (int) $productId));

    return $stmt !== false;
}

$conn = getDbConnection();
if ($conn) {
    ensureDatabaseTables($conn);
    ensureAdminUser($conn);
}
