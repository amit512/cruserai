<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/AccountManager.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "<p>Forbidden.</p>";
    exit;
}

$user = $_SESSION['user'];
$pdo = db();
try {
    $pdo->exec("
        UPDATE users 
        SET account_status = 'frozen'
        WHERE role = 'seller' 
          AND subscription_expires IS NOT NULL
          AND subscription_expires < CURDATE()
          AND account_status != 'frozen'
    ");
} catch (Exception $e) {
    $error = "Failed to update expired accounts: " . $e->getMessage();
}
$message = '';
$error = '';

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['freeze_account'])) {
    verify_csrf();
    try {
        $paymentId = $_POST['payment_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $adminNotes = $_POST['admin_notes'] ?? '';

        if (empty($paymentId) || empty($status)) {
            throw new Exception('Missing required fields');
        }
        if (!in_array($status, ['verified', 'rejected'])) {
            throw new Exception('Invalid status');
        }

        $success = AccountManager::verifyPayment((int)$paymentId, $status, $adminNotes, (int)$user['id']);
        if ($success) {
            $message = 'Payment ' . $status . ' successfully!';
        } else {
            throw new Exception('Failed to update payment status');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle account freezing
if (isset($_POST['freeze_account'])) {
    verify_csrf();
    try {
        $sellerId = $_POST['seller_id'] ?? '';
        $reason = $_POST['freeze_reason'] ?? '';
        if (empty($sellerId) || empty($reason)) {
            throw new Exception('Missing required fields');
        }

        $success = AccountManager::freezeAccount((int)$sellerId, $reason, (int)$user['id']);
        if ($success) {
            $message = 'Account frozen successfully!';
        } else {
            throw new Exception('Failed to freeze account');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch pending payments
$pendingPayments = [];
try {
    $pendingPayments = AccountManager::getPendingPayments();
} catch (Exception $e) {
    $error = $error ?: $e->getMessage();
}

// Fetch all sellers with their account status
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.account_status, u.frozen_reason, u.frozen_at,
           u.subscription_expires,
           COUNT(p.id) as total_products,
           COUNT(CASE WHEN p.is_active = 1 THEN 1 END) as active_products
    FROM users u
    LEFT JOIN products p ON u.id = p.seller_id
    WHERE u.role = 'seller'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$sellers = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Seller Payments - <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-verified { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-frozen { background: #fee2e2; color: #991b1b; }
        .status-active { background: #dcfce7; color: #166534; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem; transition: all 0.2s ease; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background:white; margin:10% auto; padding:2rem; border-radius:10px; width:90%; max-width:500px; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display:block; margin-bottom:0.5rem; font-weight:600; }
        .form-input, .form-textarea { width:100%; padding:0.5rem; border:1px solid #d1d5db; border-radius:4px; }
        .alert { padding:1rem; border-radius:6px; margin-bottom:1rem; }
        .alert-success { background:#dcfce7; border:1px solid #bbf7d0; color:#16a34a; }
        .alert-error { background:#fee2e2; border:1px solid #fecaca; color:#dc2626; }
    </style>
</head>
<body class="bg-gray-100 flex">
    <!-- Sidebar (same as admin dashboard) -->
    <aside class="w-64 bg-white shadow-lg h-screen p-6">
        <h1 class="text-2xl font-bold text-indigo-600 mb-6">HomeCraft Admin</h1>
        <nav class="space-y-4">
            <a href="admin-dashboard.php" class="block text-gray-700 font-medium hover:text-indigo-600">📊 Dashboard</a>
            <a href="manage-orders.php" class="block text-gray-700 font-medium hover:text-indigo-600">📦 Orders</a>
            <a href="manage-products.php" class="block text-gray-700 font-medium hover:text-indigo-600">🛒 Products</a>
            <a href="manage-users.php" class="block text-gray-700 font-medium hover:text-indigo-600">👤 Customers</a>
            
            <a href="manage-seller-payments.php" class="block text-indigo-600 font-semibold">💳 Seller Payments</a>
            <a href="../public/logout.php" class="block text-red-600 font-medium hover:text-red-800 mt-6">🚪 Logout</a>
        </nav>
        <div class="mt-10 flex items-center space-x-3">
            <img src="assets/img/admin-avatar.png" class="w-10 h-10 rounded-full border" alt="Admin">
            <span class="font-semibold"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin', ENT_QUOTES) ?></span>
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-8 overflow-y-auto">
        <div class="admin-container max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">
                <i class="fas fa-credit-card"></i> Manage Seller Payments & Accounts
            </h1>

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

            <!-- Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?= count($pendingPayments) ?></div>
                    <div class="text-gray-600">Pending Payments</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?= count(array_filter($sellers, fn($s) => $s['account_status'] === 'frozen')) ?></div>
                    <div class="text-gray-600">Frozen Accounts</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?= count(array_filter($sellers, fn($s) => $s['account_status'] === 'active')) ?></div>
                    <div class="text-gray-600">Active Sellers</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?= count($sellers) ?></div>
                    <div class="text-gray-600">Total Sellers</div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="bg-white rounded-xl shadow mb-8 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold">
                        <i class="fas fa-clock"></i> Pending Payment Verifications
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (empty($pendingPayments)): ?>
                        <p class="text-gray-600 text-center py-4">No pending payments to verify.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-3 text-left">Seller</th>
                                        <th class="p-3 text-left">Payment Type</th>
                                        <th class="p-3 text-left">Amount</th>
                                        <th class="p-3 text-left">Method</th>
                                        <th class="p-3 text-left">Transaction ID</th>
                                        <th class="p-3 text-left">Proof</th>
                                        <th class="p-3 text-left">Submitted</th>
                                        <th class="p-3 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pendingPayments as $payment): ?>
                                    <tr class="border-b">
                                        <td class="p-3">
                                            <div>
                                                <div class="font-medium"><?= htmlspecialchars($payment['seller_name']) ?></div>
                                                <div class="text-sm text-gray-600"><?= htmlspecialchars($payment['seller_email']) ?></div>
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <span class="status-badge status-pending"><?= ucfirst($payment['payment_type']) ?></span>
                                        </td>
                                        <td class="p-3">₹<?= number_format((float)$payment['amount'], 2) ?></td>
                                        <td class="p-3"><?= ucwords(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                        <td class="p-3"><?= htmlspecialchars($payment['transaction_id'] ?: 'N/A') ?></td>
                                        <td class="p-3">
                                            <a href="../public/<?= htmlspecialchars($payment['payment_proof']) ?>" target="_blank" class="btn btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                        <td class="p-3"><?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></td>
                                        <td class="p-3">
                                            <button onclick="showVerificationModal(<?= (int)$payment['id'] ?>)" class="btn btn-success">
                                                <i class="fas fa-check"></i> Verify
                                            </button>
                                            <button onclick="showRejectionModal(<?= (int)$payment['id'] ?>)" class="btn btn-danger">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Seller Accounts -->
            <div class="bg-white rounded-xl shadow mb-8 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold">
                        <i class="fas fa-users"></i> Seller Account Management
                    </h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-3 text-left">Seller</th>
                                    <th class="p-3 text-left">Account Status</th>
                                    <th class="p-3 text-left">Products</th>
                                    <th class="p-3 text-left">Expiry Date</th>
                                    <th class="p-3 text-left">Frozen Reason</th>
                                    <th class="p-3 text-left">Frozen Since</th>
                                    <th class="p-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($sellers as $seller): ?>
                                <tr class="border-b">
                                    <td class="p-3">
                                        <div>
                                            <div class="font-medium"><?= htmlspecialchars($seller['name']) ?></div>
                                            <div class="text-sm text-gray-600"><?= htmlspecialchars($seller['email']) ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="status-badge status-<?= htmlspecialchars($seller['account_status']) ?>">
                                            <?= ucfirst($seller['account_status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-sm">
                                            <div>Total: <?= (int)$seller['total_products'] ?></div>
                                            <div>Active: <?= (int)$seller['active_products'] ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3">
    <?= $seller['subscription_expires'] 
        ? date('M d, Y', strtotime($seller['subscription_expires'])) 
        : 'N/A' ?>
</td>

                                    <td class="p-3"><?= htmlspecialchars($seller['frozen_reason'] ?: 'N/A') ?></td>
                                    <td class="p-3"><?= $seller['frozen_at'] ? date('M d, Y H:i', strtotime($seller['frozen_at'])) : 'N/A' ?></td>
                                    <td class="p-3">
                                        <?php if ($seller['account_status'] === 'active'): ?>
                                            <button onclick="showFreezeModal(<?= (int)$seller['id'] ?>)" class="btn btn-danger">
                                                <i class="fas fa-snowflake"></i> Freeze
                                            </button>
                                        <?php else: ?>
                                            <button onclick="unfreezeAccount(<?= (int)$seller['id'] ?>)" class="btn btn-success">
                                                <i class="fas fa-sun"></i> Unfreeze
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Verification Modal -->
        <div id="verificationModal" class="modal">
            <div class="modal-content">
                <h3 class="text-xl font-semibold mb-4">Verify Payment</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="payment_id" id="verifyPaymentId">
                    <input type="hidden" name="status" value="verified">

                    <div class="form-group">
                        <label class="form-label">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-textarea" rows="3" placeholder="Add any notes about this verification..."></textarea>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="closeModal('verificationModal')" class="btn btn-danger">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Verify Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment Rejection Modal -->
        <div id="rejectionModal" class="modal">
            <div class="modal-content">
                <h3 class="text-xl font-semibold mb-4">Reject Payment</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="payment_id" id="rejectPaymentId">
                    <input type="hidden" name="status" value="rejected">

                    <div class="form-group">
                        <label class="form-label">Rejection Reason *</label>
                        <textarea name="admin_notes" class="form-textarea" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="closeModal('rejectionModal')" class="btn btn-danger">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Freeze Account Modal -->
        <div id="freezeModal" class="modal">
            <div class="modal-content">
                <h3 class="text-xl font-semibold mb-4">Freeze Seller Account</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="freeze_account" value="1">
                    <input type="hidden" name="seller_id" id="freezeSellerId">

                    <div class="form-group">
                        <label class="form-label">Freeze Reason *</label>
                        <textarea name="freeze_reason" class="form-textarea" rows="3" required placeholder="Please provide a reason for freezing this account..."></textarea>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="closeModal('freezeModal')" class="btn btn-danger">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-snowflake"></i> Freeze Account</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showVerificationModal(paymentId) {
            document.getElementById('verifyPaymentId').value = paymentId;
            document.getElementById('verificationModal').style.display = 'block';
        }
        function showRejectionModal(paymentId) {
            document.getElementById('rejectPaymentId').value = paymentId;
            document.getElementById('rejectionModal').style.display = 'block';
        }
        function showFreezeModal(sellerId) {
            document.getElementById('freezeSellerId').value = sellerId;
            document.getElementById('freezeModal').style.display = 'block';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        function unfreezeAccount(sellerId) {
            if (confirm('Are you sure you want to unfreeze this seller account?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="payment_id" value="0">
                    <input type="hidden" name="status" value="verified">
                    <input type="hidden" name="admin_notes" value="Account unfrozen by admin">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    
</body>
</html>