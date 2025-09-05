# HomeCraft Monthly Subscription System

## Overview
The Monthly Subscription System enforces a 3-day trial period for new sellers, followed by mandatory monthly subscription payments. Sellers with expired subscriptions are automatically frozen and cannot access seller features until payment is verified.

## 🚀 Features

### Trial Period
- **3-Day Free Trial**: New sellers get 3 days from registration to explore the platform
- **Automatic Expiry**: Trial automatically expires after 3 days
- **Graceful Degradation**: Sellers see clear warnings about trial status

### Monthly Subscription
- **Mandatory Payment**: Subscription required after trial period
- **Payment Verification**: Admin verification of payment proofs
- **Auto-Extension**: Subscription extends by 1 month when payment verified
- **Account Freezing**: Automatic freeze when subscription expires

### Security & Enforcement
- **Access Control**: All seller pages redirect to payment upload when frozen
- **Real-time Checks**: Account status verified on every seller action
- **Legacy Compatibility**: Works alongside existing freeze system

## 🗄️ Database Schema

### Required Fields in `users` Table
```sql
ALTER TABLE users ADD COLUMN subscription_expires DATE DEFAULT NULL;
ALTER TABLE users ADD COLUMN account_status ENUM('active','frozen','suspended') DEFAULT 'active';
ALTER TABLE users ADD COLUMN frozen_reason TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN frozen_at TIMESTAMP NULL DEFAULT NULL;
```

### Required Fields in `seller_payments` Table
```sql
ALTER TABLE seller_payments ADD COLUMN payment_type VARCHAR(50) DEFAULT NULL;
ALTER TABLE seller_payments ADD COLUMN status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending';
ALTER TABLE seller_payments ADD COLUMN admin_notes TEXT DEFAULT NULL;
ALTER TABLE seller_payments ADD COLUMN verified_by INT DEFAULT NULL;
ALTER TABLE seller_payments ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL;
```

## 🔧 Implementation Details

### 1. AccountManager.php
- **`isAccountFrozen()`**: Enhanced to check trial period and subscription expiry
- **`verifyPayment()`**: Extends subscription by 1 month when subscription payment verified
- **Auto-freeze**: Automatically freezes accounts when trial expires

### 2. Registration System
- **New Sellers**: Automatically get 3-day trial period
- **Buyers**: No subscription requirements
- **Trial Calculation**: Based on `created_at` + 3 days

### 3. Payment Flow
1. Seller submits payment proof with `payment_type = 'subscription'`
2. Admin reviews and verifies payment
3. System automatically:
   - Extends `subscription_expires` by 1 month
   - Unfreezes account
   - Sets `account_status = 'active'`

### 4. Access Control
All seller pages include:
```php
if (AccountManager::isAccountFrozen($user['id'])) {
    header('Location: payment-upload.php');
    exit;
}
```

## 📱 User Experience

### Trial Period
- **Dashboard Banner**: Shows remaining trial days
- **Clear Messaging**: Explains trial terms and subscription requirement
- **Payment Link**: Direct link to subscription page

### Subscription Status
- **Active Subscription**: Green banner with expiry date
- **Expired Subscription**: Red banner with renewal instructions
- **Trial Active**: Yellow banner with countdown

### Payment Process
- **Multiple Options**: Bank transfer, UPI, cash deposit
- **File Upload**: Screenshot, receipt, or transaction details
- **Admin Review**: 24-hour verification process
- **Instant Activation**: Account unfrozen immediately after verification

## 🚀 Setup Instructions

### 1. Database Setup
```bash
# Run the SQL commands above to add required fields
mysql -u root -p homecraft < database_updates.sql
```

### 2. File Updates
Ensure these files are updated:
- `app/AccountManager.php` ✅
- `actions/register.php` ✅
- `seller/dashboard.php` ✅
- All seller action files (already have freeze checks)

### 3. Cron Job Setup
```bash
# Add to crontab for daily execution
0 0 * * * /usr/bin/php /path/to/homecraft-php/cron_auto_freeze.php

# Or run manually
php cron_auto_freeze.php
```

## 🎯 Usage Examples

### Check Account Status
```php
$status = AccountManager::getAccountStatus($sellerId);
if ($status['account_status'] === 'frozen') {
    // Account is frozen
}
```

### Verify Subscription Payment
```php
// Admin verifies payment
AccountManager::verifyPayment($paymentId, 'verified', 'Payment confirmed', $adminId);
// System automatically extends subscription and unfreezes account
```

### Force Freeze Account
```php
AccountManager::freezeAccount($sellerId, 'Manual freeze by admin', $adminId);
```

## 🔒 Security Features

### Access Control
- **Seller Actions**: All product/order operations blocked when frozen
- **Dashboard Access**: Redirects to payment upload when frozen
- **API Protection**: Server-side enforcement in all action files

### Payment Verification
- **Admin Review**: All payments require admin verification
- **Proof Required**: File upload mandatory for payment verification
- **Audit Trail**: Complete logging of admin actions

### Trial Enforcement
- **Automatic**: No manual intervention required
- **Consistent**: Same logic applied across all access points
- **Transparent**: Clear messaging about trial status

## 📊 Monitoring & Maintenance

### Daily Cron Job
- **Auto-freeze**: Expired accounts automatically frozen
- **Logging**: Complete audit trail of actions taken
- **Error Handling**: Graceful failure with detailed logging

### Admin Dashboard
- **Billing Page**: Shows subscription status for all sellers
- **Payment Management**: Review and verify payment submissions
- **Account Control**: Manual freeze/unfreeze capabilities

### System Health
- **Database Integrity**: Regular checks for missing subscription data
- **Legacy Compatibility**: Maintains existing freeze system
- **Performance**: Efficient queries with proper indexing

## 🚨 Troubleshooting

### Common Issues
1. **Account Not Freezing**: Check `subscription_expires` field exists
2. **Payment Not Extending**: Ensure `payment_type = 'subscription'`
3. **Trial Not Working**: Verify `created_at` field is set correctly

### Debug Commands
```bash
# Check account status
php -r "require 'app/AccountManager.php'; var_dump(AccountManager::getAccountStatus(SELLER_ID));"

# Run cron manually
php cron_auto_freeze.php

# Check database schema
mysql -u root -p homecraft -e "DESCRIBE users;"
```

## 🔄 Future Enhancements

### Planned Features
- **Auto-renewal**: Automatic payment processing
- **Multiple Plans**: Basic, Premium, Enterprise tiers
- **Payment Gateway**: Direct online payment integration
- **Email Notifications**: Automated reminders and confirmations

### Scalability
- **Batch Processing**: Handle large numbers of accounts
- **Caching**: Optimize frequent status checks
- **API Endpoints**: RESTful subscription management

## 📞 Support

For technical support or questions about the subscription system:
1. Check this README for common solutions
2. Review the cron job logs for errors
3. Verify database schema matches requirements
4. Test with a new seller account to verify trial period

---

**System Version**: 1.0  
**Last Updated**: <?= date('Y-m-d') ?>  
**Compatibility**: PHP 7.4+, MySQL 5.7+
