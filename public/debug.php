<?php
// Debug script to identify issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>HomeCraft Debug Information</h1>";

// Test 1: PHP Version
echo "<h2>1. PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Error Reporting: " . (error_reporting() ? 'Enabled' : 'Disabled') . "</p>";

// Test 2: File Paths
echo "<h2>2. File Paths</h2>";
echo "<p>Current Directory: " . __DIR__ . "</p>";
echo "<p>Config File: " . __DIR__ . '/../config/config.php' . "</p>";
echo "<p>Config File Exists: " . (file_exists(__DIR__ . '/../config/config.php') ? 'Yes' : 'No') . "</p>";

// Test 3: Config Loading
echo "<h2>3. Configuration Loading</h2>";
try {
    require_once __DIR__ . '/../config/config.php';
    echo "<p style='color: green;'>✅ Config file loaded successfully</p>";
    echo "<p>SITE_NAME: " . SITE_NAME . "</p>";
    echo "<p>SITE_DESCRIPTION: " . SITE_DESCRIPTION . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading config: " . $e->getMessage() . "</p>";
}

// Test 4: Database Connection
echo "<h2>4. Database Connection</h2>";
try {
    $pdo = db();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "<p>Products table accessible. Total products: " . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 5: CSS Files
echo "<h2>5. CSS Files</h2>";
$cssFiles = ['handcraf.css', 'startstyle.css'];
foreach ($cssFiles as $cssFile) {
    $filePath = __DIR__ . '/' . $cssFile;
    if (file_exists($filePath)) {
        $fileSize = filesize($filePath);
        echo "<p style='color: green;'>✅ {$cssFile} exists ({$fileSize} bytes)</p>";
    } else {
        echo "<p style='color: red;'>❌ {$cssFile} not found</p>";
    }
}

// Test 6: Helper Functions
echo "<h2>6. Helper Functions</h2>";
if (function_exists('get_categories')) {
    echo "<p style='color: green;'>✅ get_categories() function exists</p>";
    try {
        $categories = get_categories();
        echo "<p>Categories loaded: " . count($categories) . " found</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error calling get_categories(): " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ get_categories() function not found</p>";
}

if (function_exists('format_price')) {
    echo "<p style='color: green;'>✅ format_price() function exists</p>";
    try {
        $formatted = format_price(1500.50);
        echo "<p>Formatted price: {$formatted}</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error calling format_price(): " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ format_price() function not found</p>";
}

// Test 7: Session
echo "<h2>7. Session Information</h2>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session ID: " . (session_id() ?: 'None') . "</p>";
echo "<p>User Logged In: " . (isset($_SESSION['user']) ? 'Yes' : 'No') . "</p>";

// Test 8: File Permissions
echo "<h2>8. File Permissions</h2>";
$testFiles = [
    __DIR__ . '/../config/config.php',
    __DIR__ . '/../app/Database.php',
    __DIR__ . '/../app/Product.php'
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        $readable = is_readable($file) ? 'Yes' : 'No';
        $writable = is_writable($file) ? 'Yes' : 'No';
        echo "<p>{$file}: Readable: {$readable}, Writable: {$writable}</p>";
    } else {
        echo "<p style='color: red;'>{$file}: File not found</p>";
    }
}

// Test 9: Browser Information
echo "<h2>9. Browser Information</h2>";
echo "<p>User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "</p>";
echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>Debug Complete</h2>";
echo "<p><a href='index.php'>Try loading index.php again</a></p>";
echo "<p><a href='css_test.php'>Test CSS page</a></p>";
?>
