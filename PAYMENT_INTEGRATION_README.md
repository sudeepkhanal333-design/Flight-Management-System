# Payment Integration Guide - eSewa & Mobile Banking

## ✅ What's Been Implemented

### 1. **Database Schema Updates**
- Added `payment_method` column to `bookings` table
- Added `payment_status` column (PENDING, COMPLETED, FAILED, REFUNDED)
- Added `payment_transaction_id` for tracking payments
- Added `payment_amount` to store amount at time of payment
- Added `payment_date` timestamp

**To apply:** Run `migration_add_payment_fields.sql` in phpMyAdmin

### 2. **Payment Methods Available**
- ✅ **eSewa** - Nepal's popular digital wallet
- ✅ **Mobile Banking** - Bank transfers via mobile apps
- ✅ **UPI** - Unified Payment Interface
- ✅ **Credit/Debit Card**
- ✅ **Net Banking**

### 3. **Payment Flow**
1. User selects flight and payment method in booking modal
2. Booking is created as **PENDING** status
3. User is redirected to `/flight/user/payment.php`
4. User enters transaction ID/reference number
5. Payment is validated and booking status changes to **CONFIRMED**

### 4. **Payment Validation**
- **eSewa**: Validates transaction ID format (starts with "EP" + numbers)
- **Mobile Banking**: Validates transaction reference (10+ digits)
- Other methods: Accepts any transaction ID

## 🔧 For Real Payment Integration

### eSewa Integration
To integrate with real eSewa API:

1. **Register with eSewa**:
   - Sign up at https://developer.esewa.com.np
   - Get your `merchant_id` and `secret_key`

2. **Update `user/payment.php`**:
   ```php
   // Replace the eSewa validation section with:
   if (strtoupper($paymentMethodPost) === 'ESEWA') {
       // eSewa API verification
       $esewa_url = "https://uat.esewa.com.np/epay/transrec";
       $data = [
           'amt' => $totalAmount,
           'rid' => $transactionId,
           'pid' => 'FLIGHT_' . $bookingId,
           'scd' => 'YOUR_MERCHANT_ID'
       ];
       
       // Make API call to verify payment
       // Update payment_status based on API response
   }
   ```

### Mobile Banking Integration
For real mobile banking (Nepal):

1. **Choose a payment gateway**:
   - **Khalti** (https://khalti.com)
   - **IME Pay** (https://imepay.com.np)
   - **Fonepay** (https://fonepay.com)

2. **Example with Khalti**:
   ```php
   // In payment.php, add Khalti verification
   $khalti_secret = "YOUR_KHALTI_SECRET_KEY";
   $verification_url = "https://khalti.com/api/v2/payment/verify/";
   
   $data = [
       'token' => $transactionId,
       'amount' => $totalAmount * 100 // Khalti uses paisa
   ];
   
   // Verify with Khalti API
   ```

## 📋 Current Implementation Status

### ✅ Working Now:
- Payment method selection in booking modal
- Payment page with transaction ID input
- Payment validation (format-based)
- Booking status updates after payment
- Payment details shown in dashboard

### ⚠️ Demo Mode:
- Payment validation is **format-based only** (not real API calls)
- Transaction IDs are accepted if they match expected format
- No actual money is transferred

### 🚀 To Make It Production-Ready:
1. Add real payment gateway API credentials
2. Implement actual API verification calls
3. Add webhook handlers for payment callbacks
4. Add payment failure handling
5. Implement refund functionality
6. Add payment logs/audit trail

## 📁 Files Modified/Created

1. **`migration_add_payment_fields.sql`** - Database migration
2. **`user/payment.php`** - Payment processing page (NEW)
3. **`user/dashboard.php`** - Updated booking flow and payment display
4. **`PAYMENT_INTEGRATION_README.md`** - This file

## 🧪 Testing

### Test eSewa Payment:
1. Book a flight
2. Select "eSewa" as payment method
3. Enter transaction ID like: `EP1234567890`
4. Payment should be accepted

### Test Mobile Banking:
1. Book a flight
2. Select "Mobile Banking" as payment method
3. Enter transaction reference: `1234567890123` (10+ digits)
4. Payment should be accepted

## 🔐 Security Notes

- Always validate payment amounts server-side
- Use HTTPS for all payment pages
- Store payment credentials securely (environment variables)
- Implement rate limiting on payment endpoints
- Log all payment attempts for audit
- Never trust client-side payment data

## 📞 Support

For production integration:
- Contact eSewa: https://developer.esewa.com.np/support
- Contact Khalti: https://khalti.com/contact
- Contact IME Pay: https://imepay.com.np/contact
