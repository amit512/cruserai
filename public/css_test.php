<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Test - HandCraft</title>
    <link rel="stylesheet" href="handcraf.css">
    <link rel="stylesheet" href="startstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Test CSS to verify styling works */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .test-title {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .test-section h3 {
            color: #333;
            margin-top: 0;
        }
        
        .test-button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        
        .test-button:hover {
            background: #45a049;
        }
        
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
        
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="test-title">CSS Test Page</h1>
        
        <div class="test-section">
            <h3>Basic Styling Test</h3>
            <p>If you can see this styled text, basic CSS is working!</p>
            <button class="test-button">Test Button</button>
            <button class="test-button" style="background: #2196F3;">Blue Button</button>
        </div>
        
        <div class="test-section">
            <h3>Font Awesome Icons Test</h3>
            <p>Testing Font Awesome icons:</p>
            <i class="fas fa-home" style="font-size: 2rem; color: #4CAF50;"></i>
            <i class="fas fa-heart" style="font-size: 2rem; color: #f44336;"></i>
            <i class="fas fa-star" style="font-size: 2rem; color: #ffc107;"></i>
        </div>
        
        <div class="test-section">
            <h3>CSS File Loading Test</h3>
            <?php
            $cssFiles = ['handcraf.css', 'startstyle.css'];
            foreach ($cssFiles as $cssFile) {
                $filePath = __DIR__ . '/' . $cssFile;
                if (file_exists($filePath)) {
                    $fileSize = filesize($filePath);
                    echo "<div class='status success'>✅ {$cssFile} loaded successfully ({$fileSize} bytes)</div>";
                } else {
                    echo "<div class='status error'>❌ {$cssFile} not found</div>";
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <h3>Configuration Test</h3>
            <div class="test-card">
                <p><strong>Site Name:</strong> <?= SITE_NAME ?></p>
                <p><strong>Site Description:</strong> <?= SITE_DESCRIPTION ?></p>
                <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Helper Functions Test</h3>
            <div class="test-card">
                <p><strong>Formatted Price:</strong> <?= format_price(1500.50) ?></p>
                <p><strong>Truncated Text:</strong> <?= truncate_text('This is a very long text that should be truncated to show that the helper functions are working correctly.', 50) ?></p>
                <p><strong>Categories:</strong></p>
                <ul>
                    <?php foreach (get_categories() as $key => $name): ?>
                        <li><strong><?= $key ?>:</strong> <?= $name ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Navigation</h3>
            <a href="index.php" class="test-button">Back to Homepage</a>
            <a href="catalog.php" class="test-button">View Catalog</a>
        </div>
    </div>
    
    <script>
        // Simple JavaScript test
        console.log('CSS Test Page loaded successfully!');
        
        // Test button functionality
        document.querySelectorAll('.test-button').forEach(button => {
            button.addEventListener('click', function() {
                alert('Button clicked: ' + this.textContent);
            });
        });
    </script>
</body>
</html>
