<?php
declare(strict_types=1);

class AccountManager {
    /**
     * Ensure seller_payments table has admin verification fields
     */
    private static function ensurePaymentsSchema(): void {
        $pdo = self::getDatabase();
        // Create base table if missing
        $pdo->exec("CREATE TABLE IF NOT EXISTS seller_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seller_id INT NOT NULL,
            payment_type VARCHAR(50) DEFAULT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'INR',
            payment_method VARCHAR(50) DEFAULT NULL,
            transaction_id VARCHAR(100) DEFAULT NULL,
            payment_proof VARCHAR(255) DEFAULT NULL,
            status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            verified_by INT DEFAULT NULL,
            verified_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_seller_id (seller_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Add missing columns if older schema exists
        $cols = $pdo->query("SHOW COLUMNS FROM seller_payments")->fetchAll(PDO::FETCH_COLUMN, 0);
        $need = function(string $c) use ($cols) { return !in_array($c, $cols, true); };
        if ($need('status')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending'");
        }
        if ($need('admin_notes')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN admin_notes TEXT DEFAULT NULL");
        }
        if ($need('verified_by')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN verified_by INT DEFAULT NULL");
        }
        if ($need('verified_at')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL");
        }
        if ($need('payment_type')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN payment_type VARCHAR(50) DEFAULT NULL");
        }
        if ($need('currency')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN currency VARCHAR(10) DEFAULT 'INR'");
        }
        if ($need('payment_method')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL");
        }
        if ($need('transaction_id')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN transaction_id VARCHAR(100) DEFAULT NULL");
        }
        if ($need('payment_proof')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN payment_proof VARCHAR(255) DEFAULT NULL");
        }
        if ($need('created_at')) {
            $pdo->exec("ALTER TABLE seller_payments ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
    }
    
    /**
     * Check if seller account is frozen
     */
    public static function isAccountFrozen(int $sellerId): bool {
        $stmt = Database::pdo()->prepare("
            SELECT u.account_status, u.subscription_expires, u.created_at, sa.is_frozen as legacy_frozen
            FROM users u
            LEFT JOIN seller_accounts sa ON u.id = sa.seller_id
            WHERE u.id = ? AND u.role = 'seller'
        ");
        $stmt->execute([$sellerId]);
        $result = $stmt->fetch();

        if (!$result) {
            return true; // No such seller treated as blocked
        }

        // If already frozen in users table, return true immediately
        if (($result['account_status'] ?? 'active') === 'frozen') {
            return true;
        }

        // If frozen in legacy seller_accounts table, return true
        if (($result['legacy_frozen'] ?? 0) == 1) {
            return true;
        }

        $today = new DateTime('today');
        $subscriptionExpires = !empty($result['subscription_expires']) ? new DateTime($result['subscription_expires']) : null;

        // Active subscription
        if ($subscriptionExpires && $subscriptionExpires >= $today) {
            return false;
        }

        // No active subscription: check 3-day trial from created_at
        try {
            $createdAt = new DateTime($result['created_at']);
            $trialEnd = (clone $createdAt)->modify('+3 days');
            if ($today <= $trialEnd) {
                return false; // Trial still active
            }
        } catch (Exception $e) {
            // If created_at invalid, proceed to freeze
        }

        // Trial expired and no active subscription → should be frozen
        // Don't auto-freeze here to avoid infinite loops
        // The cron job or admin should handle freezing
        return true;
    }
    
    /**
     * Get account status details
     */
    public static function getAccountStatus(int $sellerId): ?array {
        $stmt = Database::pdo()->prepare("
            SELECT account_status, frozen_reason, frozen_at, subscription_expires
            FROM users 
            WHERE id = ? AND role = 'seller'
        ");
        $stmt->execute([$sellerId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Freeze seller account
     */
    public static function freezeAccount(int $sellerId, string $reason, int $adminId): bool {
        try {
            $stmt = Database::pdo()->prepare("
                UPDATE users 
                SET account_status = 'frozen', frozen_reason = ?, frozen_at = CURRENT_TIMESTAMP
                WHERE id = ? AND role = 'seller'
            ");
            $stmt->execute([$reason, $sellerId]);
            
            // Log the action
            self::logAdminAction($adminId, 'freeze_account', 'users', $sellerId);
            
            return true;
        } catch (Exception $e) {
            error_log("Error freezing account: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unfreeze seller account
     */
    public static function unfreezeAccount(int $sellerId, int $adminId): bool {
        try {
            $stmt = Database::pdo()->prepare("
                UPDATE users 
                SET account_status = 'active', frozen_reason = NULL, frozen_at = NULL
                WHERE id = ? AND role = 'seller'
            ");
            $stmt->execute([$sellerId]);
            
            // Log the action
            self::logAdminAction($adminId, 'unfreeze_account', 'users', $sellerId);
            
            return true;
        } catch (Exception $e) {
            error_log("Error unfreezing account: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Submit payment for verification
     */
    public static function submitPayment(array $paymentData): int {
        self::ensurePaymentsSchema();
        $stmt = Database::pdo()->prepare("
            INSERT INTO seller_payments 
            (seller_id, payment_type, amount, currency, payment_method, transaction_id, payment_proof)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $paymentData['seller_id'],
            $paymentData['payment_type'],
            $paymentData['amount'],
            $paymentData['currency'] ?? 'INR',
            $paymentData['payment_method'],
            $paymentData['transaction_id'] ?? null,
            $paymentData['payment_proof']
        ]);
        
        return (int) Database::pdo()->lastInsertId();
    }
    
    /**
     * Get pending payments for admin review
     */
    public static function getPendingPayments(): array {
        self::ensurePaymentsSchema();
        try {
            $stmt = Database::pdo()->query("
                SELECT sp.*, u.name as seller_name, u.email as seller_email
                FROM seller_payments sp
                JOIN users u ON sp.seller_id = u.id
                WHERE sp.status = 'pending'
                ORDER BY sp.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            if ($e->getCode() === '42S22') { // Missing column on older schema
                self::ensurePaymentsSchema();
                $stmt = Database::pdo()->query("
                    SELECT sp.*, u.name as seller_name, u.email as seller_email
                    FROM seller_payments sp
                    JOIN users u ON sp.seller_id = u.id
                    WHERE sp.status = 'pending'
                    ORDER BY sp.created_at DESC
                ");
                return $stmt->fetchAll();
            }
            throw $e;
        }
    }
    
    /**
     * Verify payment
     */
    public static function verifyPayment(int $paymentId, string $status, string $adminNotes, int $adminId): bool {
        self::ensurePaymentsSchema();
        try {
            $stmt = Database::pdo()->prepare("
                UPDATE seller_payments 
                SET status = ?, admin_notes = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminNotes, $adminId, $paymentId]);
            
            // If payment is verified, unfreeze the account
            if ($status === 'verified') {
                $stmt = Database::pdo()->prepare("
                
                    SELECT seller_id, payment_type FROM seller_payments WHERE id = ?
                ");
                $stmt->execute([$paymentId]);
                $result = $stmt->fetch();
                
                if ($result) {
                    // If it's a subscription payment, extend subscription by 1 month
                    if (($result['payment_type'] ?? '') === 'subscription') {
                        // Extend from existing expiry if in future, else from today
                        $u = Database::pdo()->prepare("SELECT subscription_expires FROM users WHERE id = ?");
                        $u->execute([$result['seller_id']]);
                        $row = $u->fetch();
                        $base = null;
                        $today = new DateTime('today');
                        if ($row && !empty($row['subscription_expires'])) {
                            try { $base = new DateTime($row['subscription_expires']); } catch (Exception $e) { $base = null; }
                        }
                        if (!$base || $base < $today) { $base = $today; }
                        $newExpiry = (clone $base)->modify('+1 month');
                        $upd = Database::pdo()->prepare("UPDATE users SET subscription_expires = ? WHERE id = ?");
                        $upd->execute([$newExpiry->format('Y-m-d'), $result['seller_id']]);
                    }
                    self::unfreezeAccount($result['seller_id'], $adminId);
                }
            }
            
            // Log the action
            self::logAdminAction($adminId, 'verify_payment', 'seller_payments', $paymentId);
            
            return true;
        } catch (Exception $e) {
            error_log("Error verifying payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get subscription plans
     */
    public static function getSubscriptionPlans(): array {
        try {
            // Check if seller_subscriptions table exists
            $stmt = Database::pdo()->query("SHOW TABLES LIKE 'seller_subscriptions'");
            if ($stmt->rowCount() > 0) {
                $stmt = Database::pdo()->query("
                    SELECT * FROM seller_subscriptions 
                    WHERE is_active = 1 
                    ORDER BY monthly_fee ASC
                ");
                return $stmt->fetchAll();
            }
        } catch (Exception $e) {
            error_log("Error getting subscription plans: " . $e->getMessage());
        }
        return [];
    }
    
    /**
     * Check subscription expiry
     */
    public static function checkSubscriptionExpiry(int $sellerId): bool {
        $stmt = Database::pdo()->prepare("
            SELECT subscription_expires FROM users 
            WHERE id = ? AND role = 'seller'
        ");
        $stmt->execute([$sellerId]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['subscription_expires']) {
            return false; // No subscription
        }
        
        $expiryDate = new DateTime($result['subscription_expires']);
        $today = new DateTime();
        
        return $expiryDate < $today;
    }
    
    /**
     * Force freeze accounts that should be frozen but aren't
     */
    public static function forceFreezeExpiredAccounts(): int {
        $frozenCount = 0;
        try {
            $pdo = self::getDatabase();
            
            // Find sellers with expired subscriptions that aren't frozen
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.email, u.created_at, u.subscription_expires
                FROM users u
                WHERE u.role = 'seller'
                AND u.account_status != 'frozen'
                AND u.subscription_expires IS NOT NULL
                AND u.subscription_expires < CURDATE()
            ");
            $stmt->execute();
            $expiredSellers = $stmt->fetchAll();
            
            foreach ($expiredSellers as $seller) {
                try {
                    $createdAt = new DateTime($seller['created_at']);
                    $trialEnd = (clone $createdAt)->modify('+3 days');
                    $today = new DateTime('today');
                    
                    if ($today > $trialEnd) {
                        // Trial expired, freeze account
                        $success = self::freezeAccount(
                            $seller['id'],
                            'Auto-frozen: Trial expired and no active subscription',
                            0 // System action
                        );
                        
                        if ($success) {
                            // Also update legacy seller_accounts table
                            $pdo->prepare("
                                INSERT INTO seller_accounts (seller_id, is_frozen, freeze_threshold) 
                                VALUES (?, 1, 1000.00)
                                ON DUPLICATE KEY UPDATE is_frozen = 1
                            ")->execute([$seller['id']]);
                            
                            $frozenCount++;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error processing seller {$seller['id']}: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            error_log("Error in forceFreezeExpiredAccounts: " . $e->getMessage());
        }
        
        return $frozenCount;
    }
    
    /**
     * Get database connection
     */
    private static function getDatabase() {
        try {
            // Try to use the global db() function first
            if (function_exists('db')) {
                return db();
            }
            
            // Fallback to Database class if available
            if (class_exists('Database')) {
                return Database::pdo();
            }
            
            // Last resort: direct connection
            require_once __DIR__ . '/../config/config.php';
            $host = 'localhost';
            $dbname = 'homecraft';
            $username = 'root';
            $password = '';
            
            return new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Log admin actions
     */
    private static function logAdminAction(int $adminId, string $action, string $targetTable, int $targetId): void {
        try {
            $pdo = self::getDatabase();
            // Check if admin_logs table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'admin_logs'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_logs (admin_id, action, target_table, target_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$adminId, $action, $targetTable, $targetId]);
            }
        } catch (Exception $e) {
            error_log("Error logging admin action: " . $e->getMessage());
        }
    }
}