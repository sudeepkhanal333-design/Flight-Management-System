# eSewa Payment Integration – Step-by-Step Guide

Your payment page is already wired to use the **esewa-test** folder. Follow these steps to run and verify it.

---

## What’s already done

1. **Payment page** (`user/payment.php`)  
   - Builds the eSewa form (same fields as `esewa-test/pay.php`).  
   - Sends user to eSewa with **success_url** and **failure_url** pointing to your **esewa-test** folder.

2. **esewa-test/success.php**  
   - Receives eSewa’s success response (e.g. `?data=base64`).  
   - Redirects to `user/esewa_callback.php?data=...` so the booking is confirmed.

3. **esewa-test/failure.php**  
   - Redirects to `user/dashboard.php?payment=failed`.

4. **Config** (`includes/esewa_config.php`)  
   - `$ESEWA_LIVE = true`  
   - Same UAT credentials as `esewa-test/pay.php`: `EPAYTEST`, secret `8gBm/:&EnhH.1/q`.

---

## Step 1: Use the correct project URL

eSewa needs **absolute** success/failure URLs. The app uses:

- `http://` + your host + `/flight`

So:

- If you open the site as **http://localhost/flight/...**  
  → No change.

- If you use **http://127.0.0.1/flight/...** or another host/port  
  → Open the site with that same base (e.g. `http://127.0.0.1/flight/...`) so that the generated success/failure URLs match.

---

## Step 2: Ensure `esewa-test` is under `flight`

Your folder layout should be:

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

So **esewa-test** is inside **flight**. The payment form will use:

- Success: `http://localhost/flight/esewa-test/success.php`
- Failure: `http://localhost/flight/esewa-test/failure.php`

---

## Step 3: Test the flow

1. Log in as a **user** (not admin).
2. Create a **pending** booking (search flight → Book → choose eSewa → you’ll be sent to the payment page).
3. Open:  
   `http://localhost/flight/user/payment.php?booking_id=12&method=ESEWA`  
   (use your real `booking_id` if different).
4. Click the green **“Pay Rs. … with eSewa”** button.  
   → You should be redirected to eSewa (rc-epay.esewa.com.np).
5. On eSewa UAT use:
   - **eSewa ID:** 9806800001 (or 9806800002, 9806800003, …)
   - **Password:** Nepal@123
   - **Token (OTP):** 123456
6. After paying, eSewa redirects to **esewa-test/success.php**, which forwards to **user/esewa_callback.php** and then to the dashboard with “Transaction Successful”.
7. If you cancel on eSewa, you are sent to **esewa-test/failure.php** and then to the dashboard with a failure message.

---

## Step 4: If success doesn’t update the booking

eSewa might not send the response as `?data=...`. To see what they send:

1. Edit **esewa-test/success.php**.
2. At the top, temporarily add:

   ```php
   file_put_contents(__DIR__ . '/debug_success.txt', date('Y-m-d H:i:s') . "\n" . print_r($_GET, true) . "\n" . print_r($_POST, true) . "\n---\n", FILE_APPEND);
   ```

3. Run a test payment again.
4. Open **flight/esewa-test/debug_success.txt** and check:
   - If you see a parameter like `data` with a long base64 string, the current forwarding to `esewa_callback.php?data=...` is correct.
   - If the response is in another parameter (e.g. `response` or in POST), we need to change **success.php** to read that parameter and pass it to the callback in the same way (e.g. `esewa_callback.php?data=...`).
5. Remove the `file_put_contents` line when done.

---

## Step 5: If you use a different base URL

If your app is not at `http://localhost/flight` (e.g. `http://localhost:8080/flight` or a virtual host):

1. **esewa-test/success.php** and **failure.php** use:
   ```php
   $baseUrl = 'http://localhost/flight';
   ```
2. Replace that with your real base URL, for example:
   ```php
   $baseUrl = 'http://localhost:8080/flight';
   // or
   $baseUrl = 'https://yourdomain.com/flight';
   ```
3. The payment page builds success/failure URLs from the current request, so they should already match the host you use to open the site. If you still see wrong redirects, we can switch success/failure to use a config variable (e.g. in `esewa_config.php`).

---

## Step 6: Optional – match pay.php exactly (amount format)

Your **pay.php** sends `amount` as a simple number (e.g. `700`). The payment page sends it as `"18500.00"`. Both are valid. If eSewa ever rejects the request, try sending amount without decimals:

In **user/payment.php**, where the eSewa form is built, you can use:

- `value="<?php echo (int)round($totalAmount); ?>"` for `amount` and `total_amount`  
  so they are integers like in **pay.php**.

Only do this if you get a signature or validation error from eSewa.

---

## Quick checklist

- [ ] Site opened as `http://localhost/flight/...` (or your chosen base URL).
- [ ] **esewa-test** folder is inside **flight**.
- [ ] **includes/esewa_config.php** has `$ESEWA_LIVE = true`.
- [ ] You have a **pending** booking and open payment.php with that `booking_id` and `method=ESEWA`.
- [ ] You click “Pay Rs. … with eSewa” and complete payment on eSewa UAT with test ID and token 123456.
- [ ] After success you land on the dashboard with “Transaction Successful” and the booking is confirmed.

If any step fails, use Step 4 to see what eSewa sends to **success.php** and we can adjust the integration (e.g. parameter name or callback URL) accordingly.
