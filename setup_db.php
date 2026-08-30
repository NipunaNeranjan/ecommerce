<?php
$serverName = "Nipuna2006";
$database = "EcommerceDB";

$masterConnection = sqlsrv_connect($serverName, array("Database" => "master", "CharacterSet" => "UTF-8", "ReturnDatesAsStrings" => true));
if ($masterConnection === false) {
    die('SQL Server connection failed. Please enable the SQL Server PHP drivers and verify the server name.');
}

$dbExists = sqlsrv_query($masterConnection, "SELECT 1 FROM sys.databases WHERE name = ?", array($database));
if ($dbExists !== false && sqlsrv_has_rows($dbExists) === false) {
    $createDb = sqlsrv_query($masterConnection, "CREATE DATABASE [EcommerceDB]");
    if ($createDb === false) {
        die('Unable to create the EcommerceDB database.<br>' . print_r(sqlsrv_errors(), true));
    }
}
if ($dbExists !== false) {
    sqlsrv_free_stmt($dbExists);
}
sqlsrv_close($masterConnection);

$conn = sqlsrv_connect($serverName, array("Database" => $database, "CharacterSet" => "UTF-8", "ReturnDatesAsStrings" => true));
if ($conn === false) {
    die('Database connection failed.<br>' . print_r(sqlsrv_errors(), true));
}

$tables = array(
    "IF OBJECT_ID('dbo.users', 'U') IS NULL BEGIN CREATE TABLE dbo.users (id INT IDENTITY(1,1) PRIMARY KEY, name NVARCHAR(255) NOT NULL, email NVARCHAR(255) NOT NULL UNIQUE, password NVARCHAR(255) NOT NULL, role NVARCHAR(20) NOT NULL DEFAULT 'customer', created_at DATETIME2 DEFAULT GETDATE()); END",
    "IF OBJECT_ID('dbo.products', 'U') IS NULL BEGIN CREATE TABLE dbo.products (id INT IDENTITY(1,1) PRIMARY KEY, name NVARCHAR(255) NOT NULL, sku NVARCHAR(100) NOT NULL UNIQUE, category NVARCHAR(100) NOT NULL, price DECIMAL(10,2) NOT NULL DEFAULT 0.00, stock INT NOT NULL DEFAULT 0, description NVARCHAR(MAX), image_url NVARCHAR(255) DEFAULT '', status NVARCHAR(30) NOT NULL DEFAULT 'in-stock', created_at DATETIME2 DEFAULT GETDATE()); END",
    "IF OBJECT_ID('dbo.customers', 'U') IS NULL BEGIN CREATE TABLE dbo.customers (id INT IDENTITY(1,1) PRIMARY KEY, user_id INT NULL, full_name NVARCHAR(255) NOT NULL, email NVARCHAR(255) NOT NULL UNIQUE, phone NVARCHAR(100) DEFAULT '', location NVARCHAR(255) DEFAULT '', created_at DATETIME2 DEFAULT GETDATE(), CONSTRAINT FK_customers_users FOREIGN KEY (user_id) REFERENCES dbo.users(id)); END",
    "IF OBJECT_ID('dbo.orders', 'U') IS NULL BEGIN CREATE TABLE dbo.orders (id INT IDENTITY(1,1) PRIMARY KEY, customer_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL DEFAULT 1, total DECIMAL(10,2) NOT NULL DEFAULT 0.00, status NVARCHAR(30) NOT NULL DEFAULT 'pending', created_at DATETIME2 DEFAULT GETDATE(), CONSTRAINT FK_orders_customers FOREIGN KEY (customer_id) REFERENCES dbo.customers(id), CONSTRAINT FK_orders_products FOREIGN KEY (product_id) REFERENCES dbo.products(id)); END",
    "IF OBJECT_ID('dbo.contact_messages', 'U') IS NULL BEGIN CREATE TABLE dbo.contact_messages (id INT IDENTITY(1,1) PRIMARY KEY, name NVARCHAR(255) NOT NULL, email NVARCHAR(255) NOT NULL, message NVARCHAR(MAX) NOT NULL, created_at DATETIME2 DEFAULT GETDATE()); END"
);

foreach ($tables as $sql) {
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die('Table creation failed.<br>' . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
}

$adminCheck = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.users WHERE email = ?", array('admin@evergreen.co'));
if ($adminCheck === false || sqlsrv_has_rows($adminCheck) === false) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $adminInsert = sqlsrv_query($conn, "INSERT INTO dbo.users (name, email, password, role) VALUES (?, ?, ?, 'admin')", array('Admin User', 'admin@evergreen.co', $hash));
    if ($adminInsert === false) {
        die('Admin creation failed.<br>' . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($adminInsert);
}
if ($adminCheck !== false) {
    sqlsrv_free_stmt($adminCheck);
}

$productCheck = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM dbo.products");
$productRow = $productCheck !== false ? sqlsrv_fetch_array($productCheck, SQLSRV_FETCH_ASSOC) : array('total' => 0);
if ($productCheck !== false) {
    sqlsrv_free_stmt($productCheck);
}

if (!isset($productRow['total']) || (int) $productRow['total'] === 0) {
    $sampleProducts = array(
        array('Minimalist Leather Tote', 'EVG-ACC-001', 'Accessories', 185.00, 12, 'Soft leather tote for everyday carry.', 'https://via.placeholder.com/300x300/7c4a3a/ffffff?text=Tote'),
        array('Ceramic Vase', 'EVG-DEC-042', 'Home Decor', 65.00, 0, 'Handcrafted ceramic vase for modern interiors.', 'https://via.placeholder.com/300x300/d3cdc0/333333?text=Vase'),
        array('Textured Linen Throw', 'EVG-TEX-118', 'Textiles', 120.00, 3, 'Textured throw designed for warmth and style.', 'https://via.placeholder.com/300x300/a3b1a6/ffffff?text=Throw')
    );

    foreach ($sampleProducts as $product) {
        $stmt = sqlsrv_query($conn, "INSERT INTO dbo.products (name, sku, category, price, stock, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)", array($product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6]));
        if ($stmt === false) {
            die('Sample product creation failed.<br>' . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmt);
    }
}

sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f7f5; }
        .box { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 8px 22px rgba(0,0,0,0.08); }
        h1 { color: #044b3c; }
        p { color: #333; }
    </style>
</head>
<body>
    <div class="box">
        <h1>SQL Server Database Ready</h1>
        <p>The EcommerceDB database and tables were created successfully.</p>
        <p><strong>Admin email:</strong> admin@evergreen.co</p>
        <p><strong>Admin password:</strong> admin123</p>
        <p><a href="login.php">Go to Login</a> | <a href="register.php">Go to Register</a></p>
    </div>
</body>
</html>
