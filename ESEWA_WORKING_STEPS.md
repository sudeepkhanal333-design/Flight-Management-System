# Working eSewa Payment – Step-by-Step

## What’s fixed

1. **“Transaction ID is required”** – For eSewa you only use the green **Pay with eSewa** button. The “Confirm Payment” button and any transaction ID field are hidden, and the main form is disabled from submitting when paying with eSewa.
2. **esewa-test used** – Success and failure URLs point to your **flight/esewa-test/** folder; success.php and failure.php forward back into the app.

---

## Step 1: Check folder structure

Your **esewa-test** folder must be **inside** the **flight** folder:

```
flight/
  esewa-test/
    success.php
    failure.php
    pay.php
    index.php
  user/
    payment.php
    esewa_callback.php
  includes/
    esewa_config.php
```

So the URL to success is: `http://localhost/flight/esewa-test/success.php`

---

## Step 2: Check config

File: **includes/esewa_config.php**

- `$ESEWA_LIVE = true`  ← must be **true** so the green button redirects to eSewa.
- `product_code` = `EPAYTEST`
- `secret_key` = `8gBm/:&EnhH.1/q` (same as your pay.php)

No changes needed if these match your pay.php.

---

## Step 3: Open payment page

1. Log in as a **user** (not admin).
2. Have a **pending** booking (e.g. book a flight, choose eSewa, you’ll land on payment page; or use an existing pending booking).
3. Open:  
   `http://localhost/flight/user/payment.php?booking_id=12&method=ESEWA`  
   Replace `12` with your real booking ID if different.

---

## Step 4: Pay with eSewa (no transaction ID)

1. You should see the green **“Pay Rs. … with eSewa”** button and the text:  
   *“Click the green Pay with eSewa button above. You will be redirected to eSewa to complete payment. No transaction ID needed here.”*
2. **Do not** look for a “Confirm Payment” button or a transaction ID field. For eSewa you only use the green button.
3. Click **“Pay Rs. … with eSewa”**.
4. You will be sent to eSewa (rc-epay.esewa.com.np).
5. On eSewa UAT use:
   - **eSewa ID:** 9806800001 (or 9806800002, 9806800003, …)
   - **Password:** Nepal@123
   - **Token (OTP):** 123456
6. Complete payment. eSewa will redirect to:
   - **Success:** `flight/esewa-test/success.php` → then to `user/esewa_callback.php` → then to dashboard with “Transaction Successful”.
   - **Failure/Cancel:** `flight/esewa-test/failure.php` → then to dashboard with payment failed message.

---

## Step 5: If your site is not at `http://localhost/flight`

In **esewa-test/success.php** and **esewa-test/failure.php**, change:

```php
$baseUrl = 'http://localhost/flight';
```

to your real base URL, e.g.:

```php
$baseUrl = 'http://127.0.0.1:8080/flight';
// or
$baseUrl = 'https://yourdomain.com/flight';
```

---

## Step 6: If success doesn’t confirm the booking

eSewa might send the response with a different parameter name. To see what they send:

1. In **esewa-test/success.php**, after the first `<?php`, add:

```php
file_put_contents(__DIR__ . '/debug.txt', date('c') . "\nGET: " . print_r($_GET, true) . "\nPOST: " . print_r($_POST, true) . "\n\n", FILE_APPEND);
```

2. Do a test payment again.
3. Open **flight/esewa-test/debug.txt** and check the parameter names (e.g. `data`, `response`, etc.).
4. If the response is not in `data`, we can change success.php to forward the correct parameter to the callback. Remove the debug line after fixing.

---

## Quick checklist

- [ ] **esewa-test** is inside **flight** (so URL is `.../flight/esewa-test/success.php`).
- [ ] **includes/esewa_config.php** has `$ESEWA_LIVE = true`.
- [ ] You open the payment page with a **pending** booking and `method=ESEWA`.
- [ ] You **only** click the green **“Pay with eSewa”** button (no “Confirm Payment”, no transaction ID).
- [ ] You use eSewa test ID **9806800001**, password **Nepal@123**, token **123456**.

If all of the above are correct, eSewa payment should work end-to-end without “Transaction ID is required.”
