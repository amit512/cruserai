<?php

// add-product.php

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); 
    echo "<p>Forbidden.</p>"; 
    exit;
}

$user = $_SESSION['user'];
$categories = get_categories();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $is_active = !empty($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name)) $errors[] = 'Product name is required';
    if (empty($description)) $errors[] = 'Product description is required';
    if (empty($category)) $errors[] = 'Please select a category';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';
    
    // Image validation
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'Only JPG, PNG, WEBP, and GIF images are allowed';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'Image size must be less than 5MB';
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed';
        } else {
            // Process image
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $upload_path = __DIR__ . '/../public/uploads/' . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $filename;
            } else {
                $errors[] = 'Failed to save image';
            }
        }
    }
    
    // If no errors, create product
    if (empty($errors)) {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("
                INSERT INTO products (seller_id, name, description, category, price, stock, image, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $name, $description, $category, $price, $stock, $image_path, $is_active]);
            
            $success = 'Product created successfully!';
            
            // Clear form data
            $name = $description = $category = '';
            $price = $stock = 0;
            $is_active = 1;
            $image_path = null;
            
        } catch (Exception $e) {
            $errors[] = 'Failed to create product. Please try again.';
            error_log("Product creation error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-input:focus { box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1); }
        .image-preview { transition: all 0.3s ease; }
        .drag-over { border-color: #f97316; background-color: #fff7ed; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <span class="text-orange-600">Hand</span>Craft
                    </h1>
                    <span class="ml-4 px-3 py-1 bg-orange-100 text-orange-800 text-sm font-medium rounded-full">
                        Seller Dashboard
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?= htmlspecialchars($user['name']) ?></span>
                    <a href="../public/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="products.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-box mr-2"></i>Products
                </a>
                <a href="orders.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-shopping-cart mr-2"></i>Orders
                </a>
                <a href="analytics.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-chart-bar mr-2"></i>Analytics
                </a>
                <a href="../public/index.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-home mr-2"></i>View Store
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Add New Product</h1>
                    <p class="text-gray-600 mt-2">Create a new product listing for your customers</p>
                </div>
                <a href="products.php" class="text-orange-600 hover:text-orange-700 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Products
                </a>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <p class="text-green-800 font-medium"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                    <h3 class="text-red-800 font-medium">Please fix the following errors:</h3>
                </div>
                <ul class="list-disc list-inside text-red-700 space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Product Form -->
        <div class="bg-white rounded-lg shadow">
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <!-- Basic Information -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Product Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required
                                   class="form-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                   placeholder="Enter product name">
                        </div>
                        
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select id="category" name="category" required
                                    class="form-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $key => $value): ?>
                                    <option value="<?= $key ?>" <?= ($category ?? '') === $key ? 'selected' : '' ?>>
                                        <?= $value ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea id="description" name="description" rows="4" required
                                  class="form-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                  placeholder="Describe your product in detail..."><?= htmlspecialchars($description ?? '') ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">Be descriptive to help customers understand your product better.</p>
                    </div>
                </div>

                <!-- Pricing & Inventory -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing & Inventory</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                Price (Rs.) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">Rs.</span>
                                <input type="number" id="price" name="price" value="<?= $price ?? '' ?>" 
                                       step="0.01" min="0" required
                                       class="form-input w-full border border-gray-300 rounded-lg pl-12 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                       placeholder="0.00">
                            </div>
                        </div>
                        
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">
                                Stock Quantity <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="stock" name="stock" value="<?= $stock ?? '' ?>" 
                                   min="0" required
                                   class="form-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                   placeholder="0">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" <?= ($is_active ?? 1) ? 'checked' : '' ?>
                                   class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Make this product active and visible to customers</span>
                        </label>
                    </div>
                </div>

                <!-- Product Image -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Product Image</h3>
                    
                    <div class="space-y-4">
                        <div id="imageUpload" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-orange-400 transition-colors">
                            <div id="uploadIcon" class="mx-auto h-12 w-12 text-gray-400">
                                <i class="fas fa-cloud-upload-alt text-4xl"></i>
                            </div>
                            <div class="mt-4">
                                <label for="image" class="cursor-pointer">
                                    <span class="text-orange-600 hover:text-orange-500 font-medium">Upload an image</span>
                                    <span class="text-gray-500"> or drag and drop</span>
                                </label>
                                <input id="image" name="image" type="file" accept="image/*" class="hidden" onchange="previewImage(this)">
                            </label>
                            <p class="text-xs text-gray-500 mt-2">PNG, JPG, WEBP, GIF up to 5MB</p>
                        </div>
                        
                        <div id="imagePreview" class="hidden">
                            <img id="previewImg" src="" alt="Preview" class="max-w-xs mx-auto rounded-lg shadow">
                            <button type="button" onclick="removeImage()" class="mt-2 text-red-600 hover:text-red-700 text-sm">
                                <i class="fas fa-trash mr-1"></i>Remove Image
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4">
                    <a href="products.php" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </a>
                    <button type="submit" class="bg-orange-600 text-white px-8 py-3 rounded-lg hover:bg-orange-700 transition flex items-center">
                        <i class="fas fa-plus mr-2"></i>Create Product
                    </button>
                </div>
            </form>
        </div>

        <!-- Tips Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
            <h3 class="text-lg font-medium text-blue-900 mb-3">
                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>Tips for Better Product Listings
            </h3>
            <ul class="text-blue-800 space-y-2 text-sm">
                <li>• Use clear, high-quality images that showcase your product</li>
                <li>• Write detailed descriptions highlighting unique features</li>
                <li>• Set competitive prices based on market research</li>
                <li>• Keep stock levels updated to avoid customer disappointment</li>
                <li>• Use relevant categories to help customers find your products</li>
            </ul>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                    document.getElementById('uploadIcon').classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeImage() {
            document.getElementById('image').value = '';
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('uploadIcon').classList.remove('hidden');
        }
        
        // Drag and drop functionality
        const dropZone = document.getElementById('imageUpload');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            dropZone.classList.add('drag-over');
        }
        
        function unhighlight(e) {
            dropZone.classList.remove('drag-over');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                document.getElementById('image').files = files;
                previewImage(document.getElementById('image'));
            }
        }
    </script>
</body>
</html>
>>>>>>> Incoming (Background Agent changes)
