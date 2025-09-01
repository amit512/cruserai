<?php
// Simple test script for catalog functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Catalog Test</h1>";

try {
    // Test 1: Load required files
    echo "<h2>1. Loading Required Files</h2>";
    require_once __DIR__ . '/../config/config.php';
    echo "✅ Config loaded<br>";
    
    require_once __DIR__ . '/../app/Database.php';
    echo "✅ Database class loaded<br>";
    
    require_once __DIR__ . '/../app/Product.php';
    echo "✅ Product class loaded<br>";
    
    // Test 2: Database connection
    echo "<h2>2. Database Connection</h2>";
    $pdo = db();
    echo "✅ Database connected<br>";
    
    // Test 3: Test Product methods
    echo "<h2>3. Testing Product Methods</h2>";
    
    // Test byCategory
    echo "<h3>Testing byCategory('jewelry'):</h3>";
    $jewelryProducts = Product::byCategory('jewelry');
    echo "Found " . count($jewelryProducts) . " jewelry products<br>";
    
    if (!empty($jewelryProducts)) {
        echo "<ul>";
        foreach ($jewelryProducts as $product) {
            echo "<li>{$product['name']} - {$product['category']} - Rs " . number_format($product['price'], 2) . "</li>";
        }
        echo "</ul>";
    }
    
    // Test getCategories
    echo "<h3>Testing getCategories():</h3>";
    $categories = Product::getCategories();
    echo "Found " . count($categories) . " categories<br>";
    
    if (!empty($categories)) {
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>{$cat['category']} - {$cat['count']} products</li>";
        }
        echo "</ul>";
    }
    
    // Test 4: Check database schema
    echo "<h2>4. Database Schema Check</h2>";
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "Products table columns:<br>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
    
    // Test 5: Sample data
    echo "<h2>5. Sample Data Check</h2>";
    $stmt = $pdo->query("SELECT id, name, category, price FROM products LIMIT 5");
    $sampleProducts = $stmt->fetchAll();
    
    echo "Sample products:<br>";
    echo "<ul>";
    foreach ($sampleProducts as $product) {
        echo "<li>ID: {$product['id']}, Name: {$product['name']}, Category: {$product['category']}, Price: {$product['price']}</li>";
    }
    echo "</ul>";
    
    echo "<h2>✅ All Tests Passed!</h2>";
    echo "<p><a href='catalog.php?category=jewelry'>Try the actual jewelry catalog page</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Occurred</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    
    // Show the full stack trace for debugging
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
