<?php
/**
 * Cron Job Script: Auto-freeze expired seller accounts
 * Run this daily: php cron_auto_freeze.php
 * Or add to crontab: 0 0 * * * /usr/bin/php /path/to/cron_auto_freeze.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "Starting auto-freeze cron job...\n";

try {
    $pdo = db();
    
    // Find sellers with expired subscriptions (excluding already frozen)
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.subscription_expires, u.created_at
        FROM users u 
        WHERE u.role = 'seller' 
        AND u.account_status != 'frozen'
        AND u.subscription_expires IS NOT NULL
        AND u.subscription_expires < CURDATE()
    ");
    $stmt->execute();
    $expiredSellers = $stmt->fetchAll();
    
    echo "Found " . count($expiredSellers) . " sellers with expired subscriptions.\n";
    
    $frozenCount = 0;
    foreach ($expiredSellers as $seller) {
        try {
            // Check if trial period has passed
            $createdAt = new DateTime($seller['created_at']);
            $trialEnd = (clone $createdAt)->modify('+3 days');
            $today = new DateTime('today');
            
            if ($today > $trialEnd) {
                // Trial expired, freeze account
                $success = AccountManager::freezeAccount(
                    $seller['id'], 
                    'Auto-frozen: Trial expired and no active subscription', 
                    0 // System action
                );
                
                if ($success) {
                    // Mirror to legacy system
                    $pdo->prepare("INSERT INTO seller_accounts (seller_id, is_frozen) VALUES (?, 1) 
                        ON DUPLICATE KEY UPDATE is_frozen = 1")->execute([$seller['id']]);
                    
                    $frozenCount++;
                    echo "Frozen seller: {$seller['name']} ({$seller['email']}) - Expired: {$seller['subscription_expires']}\n";
                }
            }
        } catch (Exception $e) {
            echo "Error processing seller {$seller['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Successfully frozen $frozenCount accounts.\n";
    
    // Also check for sellers with no subscription_expires (shouldn't happen with new system)
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.created_at
        FROM users u 
        WHERE u.role = 'seller' 
        AND u.account_status != 'frozen'
        AND u.subscription_expires IS NULL
    ");
    $stmt->execute();
    $noSubscriptionSellers = $stmt->fetchAll();
    
    if (!empty($noSubscriptionSellers)) {
        echo "Found " . count($noSubscriptionSellers) . " sellers without subscription dates.\n";
        
        foreach ($noSubscriptionSellers as $seller) {
            try {
                $createdAt = new DateTime($seller['created_at']);
                $trialEnd = (clone $createdAt)->modify('+3 days');
                $today = new DateTime('today');
                
                if ($today > $trialEnd) {
                    $success = AccountManager::freezeAccount(
                        $seller['id'], 
                        'Auto-frozen: No subscription data and trial expired', 
                        0
                    );
                    
                    if ($success) {
                        $pdo->prepare("INSERT INTO seller_accounts (seller_id, is_frozen) VALUES (?, 1) 
                            ON DUPLICATE KEY UPDATE is_frozen = 1")->execute([$seller['id']]);
                        
                        $frozenCount++;
                        echo "Frozen seller (no subscription): {$seller['name']} ({$seller['email']})\n";
                    }
                }
            } catch (Exception $e) {
                echo "Error processing seller {$seller['id']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "Cron job completed. Total accounts frozen: $frozenCount\n";
    
} catch (Exception $e) {
    echo "Cron job failed: " . $e->getMessage() . "\n";
    exit(1);
}
