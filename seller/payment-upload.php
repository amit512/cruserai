<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/AccountManager.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); 
    echo "<p>Forbidden.</p>"; 
    exit;
}

$user = $_SESSION['user'];
$accountStatus = AccountManager::getAccountStatus($user['id']);
$subscriptionPlans = AccountManager::getSubscriptionPlans();

$message = '';
$error = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    try {
        $paymentType = $_POST['payment_type'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $paymentMethod = $_POST['payment_method'] ?? '';
        $transactionId = $_POST['transaction_id'] ?? '';
        
        // Validate inputs
        if (empty($paymentType) || empty($amount) || empty($paymentMethod)) {
            throw new Exception('All required fields must be filled');
        }
        
        if (!is_numeric($amount) || $amount <= 0) {
            throw new Exception('Invalid amount');
        }
        
        // Handle file upload
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Payment proof file is required');
        }
        
        $file = $_FILES['payment_proof'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and PDF are allowed');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            throw new Exception('File size too large. Maximum 5MB allowed');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $user['id'] . '_' . time() . '.' . $extension;
        $uploadPath = __DIR__ . '/../public/uploads/payments/' . $filename;
        
        // Create directory if it doesn't exist
        $uploadDir = dirname($uploadPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload file');
        }
        
        // Save payment record
        $paymentData = [
            'seller_id' => $user['id'],
            'payment_type' => $paymentType,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'payment_proof' => 'uploads/payments/' . $filename
        ];
        
        $paymentId = AccountManager::submitPayment($paymentData);
        
        $message = 'Payment submitted successfully! Your payment is under review. You will be notified once verified.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Upload - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../public/handcraf.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        .payment-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .status-banner {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .status-frozen {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .status-active {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .subscription-plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .plan-card {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .plan-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }
        
        .plan-card.featured {
            border-color: #3b82f6;
            background: #f0f9ff;
        }
        
        .plan-price {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
        }
        
        .payment-form {
            background: #f9fafb;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-submit {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #2563eb;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
    
    <div class="payment-container">
        <h1 class="text-3xl font-bold text-center mb-6">
            <i class="fas fa-credit-card"></i> Payment Management
        </h1>
        
        <!-- Account Status Banner -->
        <div class="status-banner <?= $accountStatus['account_status'] === 'frozen' ? 'status-frozen' : 'status-active' ?>">
            <i class="fas fa-<?= $accountStatus['account_status'] === 'frozen' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
            <strong>Account Status: <?= ucfirst($accountStatus['account_status'] ?? 'active') ?></strong>
            <?php if ($accountStatus['account_status'] === 'frozen'): ?>
                <br>
                <small>Reason: <?= htmlspecialchars($accountStatus['frozen_reason'] ?? 'Payment required') ?></small>
                <br>
                <small>Frozen since: <?= $accountStatus['frozen_at'] ? date('M d, Y H:i', strtotime($accountStatus['frozen_at'])) : 'N/A' ?></small>
            <?php endif; ?>
        </div>
        
        <!-- Subscription Plans -->
        <h2 class="text-2xl font-semibold mb-4">Subscription Plans</h2>
        <div class="subscription-plans">
            <?php foreach ($subscriptionPlans as $plan): ?>
                <div class="plan-card <?= $plan['plan_type'] === 'premium' ? 'featured' : '' ?>">
                    <h3 class="text-xl font-semibold mb-2"><?= ucfirst($plan['plan_type']) ?></h3>
                    <div class="plan-price">₹<?= number_format($plan['monthly_fee'], 2) ?>/month</div>
                    <ul class="text-sm text-gray-600 mt-3">
                        <?php 
                        $features = json_decode($plan['features'], true);
                        if ($features):
                            foreach ($features as $feature):
                        ?>
                            <li class="mb-1"><i class="fas fa-check text-green-500"></i> <?= ucwords(str_replace('_', ' ', $feature)) ?></li>
                        <?php 
                            endforeach;
                        endif;
                        ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Payment Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-2">
                <i class="fas fa-info-circle"></i> Payment Instructions
            </h3>
            <ul class="text-blue-700 text-sm space-y-1">
                <li>• Choose your preferred subscription plan above</li>
                <li>• Make payment using any of the methods below</li>
                <li>• Upload payment proof (screenshot, receipt, or transaction details)</li>
                <li>• Your account will be activated within 24 hours after verification</li>
            </ul>
        </div>
        
        <!-- Payment Methods -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                <i class="fas fa-university"></i> Payment Methods
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded-lg p-3 bg-white">
                    <h4 class="font-semibold text-gray-800">Bank Transfer</h4>
                    <p class="text-sm text-gray-600">Account: 1234567890<br>IFSC: ABCD0001234<br>Bank: Sample Bank</p>
                </div>
                <div class="border border-gray-200 rounded-lg p-3 bg-white">
                    <h4 class="font-semibold text-gray-800">UPI Payment</h4>
                    <p class="text-sm text-gray-600">UPI ID: homecraft@sample<br>Scan QR code for payment</p>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Payment Upload Form -->
        <div class="payment-form">
            <h3 class="text-xl font-semibold mb-4">
                <i class="fas fa-upload"></i> Submit Payment Proof
            </h3>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="form-group">
                    <label class="form-label">Payment Type *</label>
                    <select name="payment_type" class="form-select" required>
                        <option value="">Select payment type</option>
                        <option value="subscription">Subscription Payment</option>
                        <option value="renewal">Subscription Renewal</option>
                        <option value="penalty">Penalty Payment</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Amount (₹) *</label>
                    <input type="number" name="amount" class="form-input" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Method *</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">Select payment method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="upi">UPI Payment</option>
                        <option value="cash">Cash Deposit</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Transaction ID (Optional)</label>
                    <input type="text" name="transaction_id" class="form-input" placeholder="Bank reference number, UPI transaction ID, etc.">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Proof *</label>
                    <input type="file" name="payment_proof" class="form-input" accept="image/*,.pdf" required>
                    <small class="text-gray-600">Upload screenshot, receipt, or transaction details (JPG, PNG, GIF, PDF - Max 5MB)</small>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Payment Proof
                </button>
            </form>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>