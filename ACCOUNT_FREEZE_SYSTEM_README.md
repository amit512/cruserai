# HomeCraft Account Freezing System

## Overview
The Account Freezing System is a comprehensive solution that allows administrators to freeze seller accounts when payment is required, preventing them from performing any seller-related actions until payment proof is submitted and verified.

## 🚀 Features

### For Sellers:
- **Account Status Check**: All seller actions are blocked when account is frozen
- **Payment Upload**: Easy-to-use interface for submitting payment proofs
- **Subscription Plans**: Clear pricing and feature breakdown
- **Status Tracking**: Real-time account status updates
- **Payment Methods**: Multiple payment options (Bank Transfer, UPI, etc.)

### For Admins:
- **Account Management**: Freeze/unfreeze seller accounts with reasons
- **Payment Verification**: Review and verify payment submissions
- **Comprehensive Dashboard**: Monitor all seller payments and account statuses
- **Action Logging**: Track all admin actions for audit purposes

## 🗄️ Database Setup

### 1. Run the SQL Script
Execute the `seller_account_freeze_system.sql` file in your MySQL database:

```bash
mysql -u root -p homecraft < seller_account_freeze_system.sql
```

### 2. Database Changes
The script will:
- Add account status fields to the `users` table
- Create `seller_payments` table for payment verification
- Create `seller_subscriptions` table for subscription management
- Add necessary indexes for performance

## 🔧 Installation Steps

### 1. Database Setup
```sql
-- The SQL script will automatically:
-- - Add account_status, frozen_reason, frozen_at, subscription_expires to users table
-- - Create seller_payments table
-- - Create seller_subscriptions table
-- - Insert default subscription plans
```

### 2. File Structure
Ensure these files are in place:
```
app/
├── AccountManager.php          # Core account management class
seller/
├── dashboard.php               # Modified with account checks
├── products.php                # Modified with account checks
├── orders.php                  # Modified with account checks
├── analytics.php               # Modified with account checks
├── add-product.php             # Modified with account checks
├── edit-product.php            # Modified with account checks
├── delete-product.php          # Modified with account checks
└── payment-upload.php          # Payment submission interface
admin/
└── manage-seller-payments.php  # Admin payment management
actions/
├── product_create.php          # Modified with account checks
├── product_update.php          # Modified with account checks
├── product_delete.php          # Modified with account checks
├── product_toggle_status.php   # Modified with account checks
└── order_update_status.php     # Modified with account checks
```

### 3. Upload Directory
Create the payments upload directory:
```bash
mkdir -p public/uploads/payments
chmod 755 public/uploads/payments
```

## 📱 How It Works

### Account Freezing Flow:
1. **Admin freezes account** → Sets `account_status = 'frozen'` with reason
2. **Seller tries to access dashboard** → Redirected to payment upload page
3. **Seller submits payment proof** → Payment stored with 'pending' status
4. **Admin reviews payment** → Verifies or rejects with notes
5. **If verified** → Account automatically unfrozen, status set to 'active'
6. **If rejected** → Account remains frozen, seller can submit new proof

### Account Status Values:
- `active`: Normal operation, all features available
- `frozen`: Account blocked, payment required
- `suspended`: Account temporarily suspended (for future use)

## 🎯 Usage Examples

### Freezing a Seller Account (Admin)
```php
// In admin panel
AccountManager::freezeAccount($sellerId, "Monthly subscription payment overdue", $adminId);
```

### Checking Account Status (Seller)
```php
// In seller files
if (AccountManager::isAccountFrozen($sellerId)) {
    header('Location: payment-upload.php');
    exit;
}
```

### Submitting Payment (Seller)
```php
// Payment data structure
$paymentData = [
    'seller_id' => $user['id'],
    'payment_type' => 'subscription',
    'amount' => 299.00,
    'payment_method' => 'bank_transfer',
    'transaction_id' => 'TXN123456',
    'payment_proof' => 'uploads/payments/payment_123_1234567890.jpg'
];

$paymentId = AccountManager::submitPayment($paymentData);
```

## 🔒 Security Features

### CSRF Protection
- All forms include CSRF tokens
- POST actions verify tokens before processing

### File Upload Security
- File type validation (JPG, PNG, GIF, PDF only)
- File size limits (5MB maximum)
- Unique filename generation
- Secure upload directory

### Access Control
- Role-based access control
- Account status verification on all seller actions
- Admin-only payment verification

## 📊 Admin Dashboard Features

### Statistics Overview
- Pending payments count
- Frozen accounts count
- Active sellers count
- Total sellers count

### Payment Management
- View all pending payments
- Download payment proof files
- Verify or reject payments
- Add admin notes

### Account Management
- Freeze seller accounts with reasons
- Unfreeze accounts after payment verification
- View account status history

## 🎨 UI/UX Features

### Responsive Design
- Mobile-first approach
- Tailwind CSS styling
- Font Awesome icons
- Modern card-based layout

### User Experience
- Clear status indicators
- Intuitive navigation
- Helpful error messages
- Success confirmations

## 🚨 Error Handling

### Common Scenarios
- **Account Frozen**: Redirects to payment page
- **Payment Required**: Shows clear instructions
- **Upload Failed**: Displays specific error messages
- **Verification Pending**: Shows status updates

### Error Messages
- Clear, actionable error descriptions
- User-friendly language
- Specific guidance for resolution

## 🔄 Integration Points

### Existing Systems
- **User Authentication**: Works with existing login system
- **Product Management**: Blocks all product operations when frozen
- **Order Management**: Prevents order status updates when frozen
- **Analytics**: Blocks access to seller analytics when frozen

### Future Enhancements
- **Email Notifications**: Payment verification alerts
- **SMS Notifications**: Payment status updates
- **Auto-renewal**: Automatic subscription renewal
- **Payment Gateway**: Direct payment processing

## 🧪 Testing

### Test Scenarios
1. **Freeze Account**: Admin freezes seller account
2. **Access Blocking**: Verify seller cannot access any features
3. **Payment Submission**: Seller uploads payment proof
4. **Admin Verification**: Admin reviews and verifies payment
5. **Account Unfreezing**: Account automatically activated

### Test Data
```sql
-- Test freezing an account
UPDATE users SET account_status = 'frozen', frozen_reason = 'Test freeze' WHERE id = [SELLER_ID];

-- Test unfreezing an account
UPDATE users SET account_status = 'active', frozen_reason = NULL, frozen_at = NULL WHERE id = [SELLER_ID];
```

## 📝 Configuration

### Payment Settings
- **File Upload Limit**: 5MB
- **Allowed File Types**: JPG, PNG, GIF, PDF
- **Upload Directory**: `public/uploads/payments/`

### Subscription Plans
- **Basic**: ₹299/month
- **Premium**: ₹599/month  
- **Enterprise**: ₹999/month

### Admin Settings
- **Payment Verification**: Manual review required
- **Auto-unfreeze**: Enabled after verification
- **Action Logging**: All admin actions logged

## 🆘 Troubleshooting

### Common Issues
1. **Upload Directory Not Found**
   - Create `public/uploads/payments/` directory
   - Set proper permissions (755)

2. **Account Status Not Updating**
   - Check database connection
   - Verify AccountManager class is loaded
   - Check for PHP errors in logs

3. **Payment Verification Failing**
   - Verify admin permissions
   - Check CSRF token validity
   - Ensure all required fields are filled

### Debug Mode
Enable error reporting in development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📞 Support

### Documentation
- This README file
- Code comments in AccountManager.php
- SQL schema documentation

### Logs
- PHP error logs
- Admin action logs in `admin_logs` table
- Payment verification logs

## 🔮 Future Roadmap

### Phase 2 Features
- Email notifications system
- Payment gateway integration
- Advanced subscription management
- Bulk account operations

### Phase 3 Features
- Mobile app integration
- API endpoints for external systems
- Advanced analytics and reporting
- Multi-currency support

---

**Note**: This system is designed to be non-intrusive and can be easily disabled by setting all seller accounts to 'active' status if needed.