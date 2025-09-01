<?php
// Simple catalog test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Catalog Test</h1>";

try {
    // Load required files
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Product.php';
    
    echo "<p>✅ Files loaded successfully</p>";
    
    // Test database connection
    $pdo = db();
    echo "<p>✅ Database connected</p>";
    
    // Test jewelry category
    echo "<h2>Testing Jewelry Category</h2>";
    $jewelryProducts = Product::byCategory('jewelry');
    echo "<p>Found " . count($jewelryProducts) . " jewelry products</p>";
    
    if (!empty($jewelryProducts)) {
        echo "<h3>Jewelry Products:</h3>";
        echo "<ul>";
        foreach ($jewelryProducts as $product) {
            echo "<li><strong>{$product['name']}</strong> - Category: {$product['category']} - Price: " . format_price($product['price']) . "</li>";
        }
        echo "</ul>";
    }
    
    // Test categories
    echo "<h2>Testing Categories</h2>";
    $categories = Product::getCategories();
    echo "<p>Found " . count($categories) . " categories</p>";
    
    if (!empty($categories)) {
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>{$cat['category']} - {$cat['count']} products</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>✅ All Tests Passed!</h2>";
    echo "<p><a href='catalog.php?category=jewelry' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Jewelry Catalog</a></p>";
    echo "<p><a href='catalog.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View All Products</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Occurred</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
