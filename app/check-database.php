<?php
require_once __DIR__ . '/../config/config.php';

echo "<h1>Database Structure Check</h1>";

try {
    $pdo = db();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if products table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Products table exists</p>";
        
        // Check products table structure
        $stmt = $pdo->query("DESCRIBE products");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Products Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if is_active column exists
        $hasIsActive = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'is_active') {
                $hasIsActive = true;
                break;
            }
        }
        
        if ($hasIsActive) {
            echo "<p>✅ is_active column exists</p>";
        } else {
            echo "<p>❌ is_active column is missing!</p>";
            
            // Try to add the column
            echo "<h3>Attempting to add is_active column...</h3>";
            try {
                $stmt = $pdo->prepare("ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                $stmt->execute();
                echo "<p>✅ is_active column added successfully</p>";
            } catch (Exception $e) {
                echo "<p>❌ Failed to add is_active column: " . $e->getMessage() . "</p>";
            }
        }
        
        // Check sample data
        $stmt = $pdo->query("SELECT id, name, seller_id, is_active FROM products LIMIT 5");
        $products = $stmt->fetchAll();
        
        echo "<h3>Sample Products Data:</h3>";
        echo "<pre>";
        print_r($products);
        echo "</pre>";
        
    } else {
        echo "<p>❌ Products table does not exist!</p>";
    }
    
    // Check users table
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
        
        // Check users table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Users Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check sample users
        $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'seller' LIMIT 5");
        $sellers = $stmt->fetchAll();
        
        echo "<h3>Sample Sellers:</h3>";
        echo "<pre>";
        print_r($sellers);
        echo "</pre>";
        
    } else {
        echo "<p>❌ Users table does not exist!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='manage-products.php'>Back to Manage Products</a></p>";
echo "<p><a href='test-toggle.php'>Test Toggle Functionality</a></p>";
?>