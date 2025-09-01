# HomeCraft PHP - Checkout System Setup

## Overview
This document explains how to set up and use the new checkout system for the HomeCraft PHP application.

## New Files Created

### 1. Checkout Page (`public/checkout.php`)
- **Purpose**: Main checkout page where users enter shipping and payment information
- **Features**:
  - Shipping address form (address, city, state, ZIP, phone)
  - Payment method selection (Cash on Delivery, Bank Transfer)
  - Order summary with cart items
  - Form validation and error handling
  - CSRF protection

### 2. Order Success Page (`public/order-success.php`)
- **Purpose**: Confirmation page shown after successful checkout
- **Features**:
  - Order confirmation message
  - Order details display
  - Next steps information
  - Action buttons (View Orders, Continue Shopping, Go Home)

### 3. My Orders Page (`public/my-orders.php`)
- **Purpose**: Complete order history and management page for buyers
- **Features**:
  - Order summary statistics (total orders, pending, delivered)
  - Complete order history with pagination
  - Order status tracking with color-coded indicators
  - Order cancellation for pending orders
  - Detailed order information (product, seller, shipping, payment)
  - Action buttons based on order status
  - Mobile-responsive design

### 4. Checkout Action (`actions/checkout_action.php`)
- **Purpose**: Handles checkout form submission and order processing
- **Features**:
  - Form validation and error handling
  - Order creation with database transactions
  - Shipping details storage
  - Stock management
  - Cart clearing

### 5. Cart Action (`actions/cart_action.php`)
- **Purpose**: Handles cart operations (add, remove, update quantity)
- **Features**:
  - Add items to cart
  - Remove items from cart
  - Update quantities
  - Stock validation
  - Clear entire cart

### 6. Wishlist Action (`actions/wishlist_action.php`)
- **Purpose**: Handles wishlist operations
- **Features**:
  - Add products to wishlist
  - Remove products from wishlist
  - Duplicate prevention

### 7. Missing Tables SQL (`missing_tables.sql`)
- **Purpose**: SQL file to create additional database tables
- **Tables**:
  - `wishlist`: For users to save favorite products
  - `order_details`: For storing shipping and payment information
  - `product_reviews`: For product ratings and reviews
  - `user_addresses`: For saving multiple shipping addresses

## Database Setup

### 1. Run the Missing Tables SQL
```sql
-- Execute the contents of missing_tables.sql in your MySQL database
-- This will create the necessary tables for the checkout system
```

### 2. Verify Existing Tables
Make sure these tables exist in your database:
- `users` - User accounts
- `products` - Product listings
- `cart_items` - Shopping cart items
- `orders` - Order records

## How the Checkout System Works

### 1. User Flow
1. User adds products to cart (`cart.php` → `actions/cart_action.php`)
2. User clicks "Proceed to Checkout" button
3. User is redirected to checkout page (`checkout.php`)
4. User fills out shipping and payment information
5. User submits checkout form (`actions/checkout_action.php`)
6. System creates orders and clears cart
7. User is redirected to success page (`order-success.php`)

### 2. Order Processing
- **Checkout Action** (`actions/checkout_action.php`):
  - Validates form data
  - Creates individual orders for each cart item
  - Saves shipping details to `order_details` table
  - Updates product stock quantities
  - Clears user's shopping cart
  - Uses database transactions for data integrity

### 3. Cart Management
- **Cart Action** (`actions/cart_action.php`):
  - Handles adding/removing items
  - Updates quantities with stock validation
  - Manages cart operations securely

### 4. Security Features
- CSRF token protection
- User authentication required
- Input validation and sanitization
- SQL injection prevention with prepared statements

## Features

### Payment Methods
- **Cash on Delivery (COD)**: Pay when receiving the product
- **Bank Transfer**: Direct bank transfer (manual processing)

### Shipping Information
- Complete shipping address
- City, state, and ZIP code
- Contact phone number

### Order Management
- Orders are created with "Pending" status
- Sellers can update order status
- Buyers can view order history

## Customization Options

### 1. Add More Payment Methods
Edit `checkout.php` and add new payment options in the payment methods section.

### 2. Modify Shipping Fields
Add or remove shipping fields by editing the form in `checkout.php`.

### 3. Change Order Statuses
Modify the order status options in the seller order management pages.

### 4. Add Email Notifications
Implement email sending functionality for order confirmations.

## Testing the Checkout System

### 1. Test User Flow
1. Create a test user account
2. Add products to cart
3. Go through the complete checkout process
4. Verify orders are created in the database
5. Check that cart is cleared after checkout

### 2. Test Error Handling
1. Try submitting checkout form with missing fields
2. Verify validation errors are displayed
3. Test with invalid data

### 3. Test Database Integrity
1. Verify orders are created correctly
2. Check that product stock is updated
3. Confirm shipping details are saved

## Troubleshooting

### Common Issues

#### 1. "Table doesn't exist" errors
- Run the `missing_tables.sql` file
- Check database connection settings

#### 2. Checkout form not submitting
- Verify CSRF token is being generated
- Check for JavaScript errors in browser console
- Ensure all required fields are filled

#### 3. Orders not being created
- Check database permissions
- Verify transaction handling
- Check error logs for specific error messages

#### 4. Cart not clearing after checkout
- Verify the DELETE query for cart items
- Check user ID matching

## Future Enhancements

### 1. Payment Gateway Integration
- PayPal integration
- Stripe payment processing
- Credit card processing

### 2. Advanced Shipping
- Multiple shipping options
- Shipping cost calculation
- Real-time shipping rates

### 3. Order Tracking
- Tracking number system
- Delivery status updates
- SMS/Email notifications

### 4. Inventory Management
- Low stock alerts
- Automatic stock updates
- Backorder handling

## Support

If you encounter any issues with the checkout system:
1. Check the error logs
2. Verify database connectivity
3. Test with a fresh database
4. Review the code for syntax errors

The checkout system is designed to be robust and user-friendly while maintaining security and data integrity.
