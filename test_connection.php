<?php
// Test file to check database connection and identify issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Test 1: Check if config file can be loaded
echo "<h2>Test 1: Loading config file</h2>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✅ Config file loaded successfully<br>";
    echo "Site name: " . SITE_NAME . "<br>";
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br>";
}

// Test 2: Test database connection
echo "<h2>Test 2: Database connection</h2>";
try {
    $pdo = db();
    echo "✅ Database connection successful<br>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "✅ Products table accessible. Total products: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check if Database class can be loaded
echo "<h2>Test 3: Loading Database class</h2>";
try {
    require_once __DIR__ . '/app/Database.php';
    echo "✅ Database class loaded successfully<br>";
    
    // Test Database class
    $db = Database::pdo();
    echo "✅ Database class working<br>";
    
} catch (Exception $e) {
    echo "❌ Error loading Database class: " . $e->getMessage() . "<br>";
}

// Test 4: Check if Product class can be loaded
echo "<h2>Test 4: Loading Product class</h2>";
try {
    require_once __DIR__ . '/app/Product.php';
    echo "✅ Product class loaded successfully<br>";
    
    // Test Product class methods
    $products = Product::allActive(3);
    echo "✅ Product class working. Found " . count($products) . " products<br>";
    
} catch (Exception $e) {
    echo "❌ Error loading Product class: " . $e->getMessage() . "<br>";
}

// Test 5: Check database table structure
echo "<h2>Test 5: Database table structure</h2>";
try {
    $pdo = db();
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "✅ Products table structure:<br>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If you see any ❌ errors above, those need to be fixed before the main site will work.</p>";
?>
