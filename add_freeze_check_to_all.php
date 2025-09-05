<?php
/**
 * Add Simple Freeze Check to All Seller Pages
 * This will add the direct database check to all seller files
 */

$sellerFiles = [
    'seller/analytics.php',
    'seller/edit-product.php',
    'seller/delete-product.php'
];

$freezeCheckCode = '
// SIMPLE FREEZE CHECK - REDIRECT IMMEDIATELY IF FROZEN
if (!empty($_SESSION[\'user\']) && ($_SESSION[\'user\'][\'role\'] ?? \'\') === \'seller\') {
    $userId = $_SESSION[\'user\'][\'id\'];
    
    // Direct database check - no fancy logic, just check if frozen
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT account_status FROM users WHERE id = ? AND role = \'seller\'");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if ($result && $result[\'account_status\'] === \'frozen\') {
            header(\'Location: /homecraft-php/seller/payment-upload.php\');
            exit;
        }
    } catch (Exception $e) {
        // If error, redirect to payment page anyway (better safe than sorry)
        header(\'Location: /homecraft-php/seller/payment-upload.php\');
        exit;
    }
}
';

foreach ($sellerFiles as $file) {
    if (file_exists($file)) {
        echo "Processing: $file\n";
        
        $content = file_get_contents($file);
        
        // Find the line after require_once statements
        $lines = explode("\n", $content);
        $insertPosition = -1;
        
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], 'require_once') !== false) {
                $insertPosition = $i;
            }
        }
        
        if ($insertPosition !== -1) {
            // Insert the freeze check after the last require_once
            $insertPosition++;
            
            // Check if there's already a freeze check
            if (strpos($content, 'SIMPLE FREEZE CHECK') === false) {
                array_splice($lines, $insertPosition, 0, $freezeCheckCode);
                $newContent = implode("\n", $lines);
                
                if (file_put_contents($file, $newContent)) {
                    echo "✅ Added freeze check to $file\n";
                } else {
                    echo "❌ Failed to write to $file\n";
                }
            } else {
                echo "⚠️ Freeze check already exists in $file\n";
            }
        } else {
            echo "❌ Could not find require_once statements in $file\n";
        }
    } else {
        echo "⚠️ File not found: $file\n";
    }
}

echo "\n🎯 Freeze check added to all seller pages!\n";
echo "Now frozen sellers will be redirected immediately when they try to access any seller page.\n";
?>
