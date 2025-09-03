<?php
declare(strict_types=1);

class AccountManager {
    /**
     * Ensure seller_payments table has admin verification fields
     */
    private static function ensurePaymentsSchema(): void {
        $pdo = Database::pdo();
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
            SELECT account_status FROM users 
            WHERE id = ? AND role = 'seller'
        ");
        $stmt->execute([$sellerId]);
        $result = $stmt->fetch();
        
        return $result && $result['account_status'] === 'frozen';
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
        } catch (PDOException $e) {
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
                    SELECT seller_id FROM seller_payments WHERE id = ?
                ");
                $stmt->execute([$paymentId]);
                $result = $stmt->fetch();
                
                if ($result) {
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
        $stmt = Database::pdo()->query("
            SELECT * FROM seller_subscriptions 
            WHERE is_active = 1 
            ORDER BY monthly_fee ASC
        ");
        return $stmt->fetchAll();
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
     * Log admin actions
     */
    private static function logAdminAction(int $adminId, string $action, string $targetTable, int $targetId): void {
        try {
            $stmt = Database::pdo()->prepare("
                INSERT INTO admin_logs (admin_id, action, target_table, target_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$adminId, $action, $targetTable, $targetId]);
        } catch (Exception $e) {
            error_log("Error logging admin action: " . $e->getMessage());
        }
    }
}