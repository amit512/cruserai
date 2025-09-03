<?php
/**
 * Check Users Table Structure
 * This script shows the current structure of the users table
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';

echo "<h1>🔍 Users Table Structure Check</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>\n";

try {
    $pdo = db();
    echo "<p class='success'>✅ Database connection successful</p>\n";
    
    // Check current users table structure
    echo "<h2>📋 Current Users Table Structure</h2>\n";
    
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>\n";
    echo "<tr style='background:#f0f0f0;'>\n";
    echo "<th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>\n";
    echo "</tr>\n";
    
    foreach ($columns as $column) {
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>\n";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>\n";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>\n";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>\n";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>\n";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Check for specific columns we need
    echo "<h2>🔍 Required Columns Check</h2>\n";
    
    $requiredColumns = [
        'account_status' => 'ENUM("active", "frozen", "suspended")',
        'frozen_reason' => 'TEXT',
        'frozen_at' => 'TIMESTAMP',
        'subscription_expires' => 'DATE'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $column => $expectedType) {
        if (in_array($column, $existingColumns)) {
            echo "<p class='warning'>⚠️ Column '$column' already exists</p>\n";
        } else {
            echo "<p class='error'>❌ Column '$column' missing (Expected: $expectedType)</p>\n";
        }
    }
    
    // Check for required tables
    echo "<h2>📊 Required Tables Check</h2>\n";
    
    $requiredTables = ['seller_payments', 'seller_subscriptions'];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='warning'>⚠️ Table '$table' already exists</p>\n";
        } else {
            echo "<p class='error'>❌ Table '$table' missing</p>\n";
        }
    }
    
    // Check current user data
    echo "<h2>👥 Current Users Data</h2>\n";
    
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>\n";
        echo "<tr style='background:#f0f0f0;'>\n";
        echo "<th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th>\n";
        echo "</tr>\n";
        
        foreach ($users as $user) {
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>\n";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>\n";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>\n";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>\n";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p class='info'>No users found in the system</p>\n";
    }
    
    // Recommendations
    echo "<h2>💡 Recommendations</h2>\n";
    
    $missingColumns = array_diff(array_keys($requiredColumns), $existingColumns);
    $missingTables = array_filter($requiredTables, function($table) use ($pdo) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        return $stmt->rowCount() == 0;
    });
    
    if (empty($missingColumns) && empty($missingTables)) {
        echo "<p class='success'>✅ All required columns and tables are present!</p>\n";
        echo "<p class='info'>The account freezing system is ready to use.</p>\n";
    } else {
        if (!empty($missingColumns)) {
            echo "<p class='error'>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>\n";
        }
        if (!empty($missingTables)) {
            echo "<p class='error'>❌ Missing tables: " . implode(', ', $missingTables) . "</p>\n";
        }
        echo "<p class='warning'>⚠️ Run the fixed SQL script: <code>seller_account_freeze_system_fixed.sql</code></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<h3>📖 Next Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Review the table structure above</li>\n";
echo "<li>If columns are missing, run: <code>mysql -u root -p homecraft < seller_account_freeze_system_fixed.sql</code></li>\n";
echo "<li>Create upload directory: <code>mkdir -p public/uploads/payments</code></li>\n";
echo "<li>Test the system with: <code>php test_account_freeze_system.php</code></li>\n";
echo "</ol>\n";
?>