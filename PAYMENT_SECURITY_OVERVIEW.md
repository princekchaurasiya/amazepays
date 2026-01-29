## Payment & Security Overview – Gift Giggles

This document focuses on **payment, security, and fraud‑prevention related changes**. It is designed to be client‑facing so you can explain risk, impact, and the value of the work.

---

### 1. Critical Fraud Scenario – ₹1 Paid, ₹10,000 Card Issued

**Business risk identified:**  
A user could manipulate the payment amount in their browser, pay only **₹1**, and still receive a **gift card worth ₹10,000**.

#### Root cause

- At checkout, the frontend rendered a **hidden input**:
  - `name="payable_amount"`  
  - Value: `amount_payable_after_discount` from the order.
- The **backend trusted this hidden field** when sending the payment request to the gateway.
- Attackers could:
  - Open browser DevTools.
  - Change `payable_amount` from `10000` to `1`.
  - Submit the form.
- The payment gateway then processed a **legitimate payment of ₹1**.
- Woohoo order creation used the **order’s denomination and quantity** from the database (10,000 × 1), not the actual paid amount, and thus issued a ₹10,000 card.

#### Data flow before the fix

| Step | Layer      | Field / Behaviour                            | Value used       | Trust source             |
|------|-----------|-----------------------------------------------|------------------|--------------------------|
| 1    | Backend   | `QsOrder` creation                            | 10,000           | DB (correct)             |
| 2    | Frontend  | Hidden `payable_amount` rendered              | 10,000           | Server → HTML            |
| 3    | Attacker  | Edits `payable_amount` in DevTools            | 1                | Client‑side (tampered)   |
| 4    | Backend   | Reads `request('payable_amount')`             | 1                | Untrusted, but accepted  |
| 5    | Gateway   | Processes payment for                         | 1                | Correct for tampered req |
| 6    | Woohoo    | Creates card from `denomination * quantity`   | 10,000           | Order in DB              |

**Mismatch:** Paid amount (₹1) ≠ Order value (₹10,000) → direct financial loss.

#### Fix implemented

1. **Removed hidden amount fields from checkout forms**
   - Example: In `resources/views/userpanel/checkout.blade.php`, we removed hidden `payable_amount` and now send **only `order_id`**.

2. **Enforced server‑side amount calculation in controllers**
   - Controllers (`UPIPaymentController`, `UnlimitController`, `VDPaymentController`, etc.) now compute the payable amount **only from the database**:
     - `amount_payable_after_discount`
     - or `grand_payable_amount`
     - or `denomination * quantity` as final fallback.
   - The amount sent to the payment gateway is now **immutable from the client’s perspective**.

3. **Standardized status flow**
   - Order status normalized across gateways:
     - `UnPaid` → `PENDING` (after payment success, before Woohoo card creation)
     - `PENDING` → `COMPLETE` (after card creation) or `FAILED` (if card creation fails).
   - `OrderSummary` and `QsOrder` are kept in sync, which improves monitoring and reconciliation.

**Result:**  
Even if a user attempts to manipulate the browser, the backend **ignores any amount from the request** and always charges what is calculated from the order in the database.

---

### 2. Hidden / Client‑Side Amounts – Policy & Implementation

**Old practice (insecure):**

- Forms included hidden fields such as:
  - `payable_amount`
  - Other amounts that could be modified by the user.
- Backend sometimes trusted these values, creating opportunity for:
  - Underpayment fraud.
  - Inconsistent reporting between payments and issued cards.

**New practice (secure):**

- **No financial amounts are accepted from the client.**
- Allowed client inputs for payment initiation are strictly:
  - `order_id` (and technical metadata like CSRF token).
- All prices, totals, and discounts are **fetched from DB** using `order_id` and the authenticated `user_id`.
- This is now consistently applied across:
  - UPI flows.
  - Unlimit payment flows.
  - Value Design payment flows.
  - Future gateways should follow the same pattern.

---

### 3. Order Ownership & Authorization

**Problem before:**

- `user_id` was not always saved on `QsOrder` due to missing `$fillable` fields.
- This could lead to:
  - Orders without owners.
  - Weak or missing “order belongs to this logged‑in user” checks.

**Fix:**

- Added `user_id` and other critical fields to `QsOrder::$fillable`.
- Controllers now:
  - Load orders by **both** `id` and `user_id` where appropriate.
  - Return “order not found / unauthorized” when mismatch occurs.

**Benefit:**

- Stronger access control: users can only view/pay for **their own** orders.
- Reduces risk of ID‑based enumeration or cross‑account order access.

---

### 4. Monthly Purchase Limit Bypass – Race Condition Fix

**Risk:**

- Monthly purchase limits were checked without locking existing rows.
- In high concurrency (multiple parallel requests), users could potentially:
  - Place several orders rapidly that all pass the “limit check”, and
  - End up exceeding the monthly limit when summed together.

**Fix:**

- In `ProductPageController`:
  - Monthly limit checks now run **inside a DB transaction**.
  - Uses `lockForUpdate()` on relevant `QsOrder` rows when summing monthly totals.
  - If the order would exceed the monthly limit, the transaction is rolled back and the user is shown a clear message.

**Result:**

- The monthly limit is now **atomic** and resilient to race conditions.

---

### 5. Woohoo Order Finalization, Emails & SMS

**Issues identified:**

- In some flows:
  - Order was marked “complete” or “paid” inconsistently.
  - Emails and SMS were not sent on **first** successful purchases, only on resends.
  - This impacted customer communication and auditability.

**Improvements:**

- `WoohooProcessingController` now:
  - Updates `QsOrder.order_status` and `OrderSummary.order_status` to `COMPLETE` only after confirmed Woohoo success.
  - On success, calls `WoohooOrderController::handleSuccessFullOrder()` which:
    - Sends transaction email (invoice / confirmation).
    - Sends gift email with card details.
    - Sends transaction SMS.
    - Sends gift SMS for each card.
  - On failure, sets status to `FAILED` and logs details.

**Security & compliance benefit:**

- Clear audit trail: for every successful card issuance, you have:
  - Matched payment status.
  - Clear order status (`COMPLETE`).
  - Logged notification attempts (emails/SMS).

---

### 6. Card Data Handling – Decryption & Recovery

**Problem:**

- Some orders had encrypted card data (`cards` field) that could no longer be decrypted – causing:
  - “Card decryption failed: The payload is invalid.”
  - Users being unable to view their cards from “My Orders”.

**Security risk (availability & support):**

- Even if data was valid in Woohoo, the local copy being unusable meant support overhead and poor user experience.

**Fix:**

- `ViewCardDetailsController` now implements a **three‑layer fallback**:
  1. Attempt to decrypt with app key.
  2. If that fails, try treating `cards` as plain JSON.
  3. If still empty and `woohoo_order_id` exists:
     - Check order status from Woohoo.
     - If `COMPLETE`, fetch cards from Woohoo’s API.
     - Re‑encrypt and persist cards into the database.

**Result:**

- Users can reliably view cards even if:
  - The original encryption format changed.
  - Legacy data is present.
- Local DB and remote Woohoo data converge again after the first successful fallback.

---

### 7. Standardized Order Status & “My Orders” UX

**Issue:**

- Inconsistent use of `Paid`, `UnPaid`, `PENDING`, `COMPLETE`, etc., across:
  - Payment tables.
  - `QsOrder`.
  - `OrderSummary`.
  - Frontend views.
- “Paid” orders could appear in red and were not clickable, even when cards existed.

**Fix:**

- Status flow is now:
  - **Gateway payment success but Woohoo not yet created** → `PENDING`.
  - **Woohoo card issued** → `COMPLETE`.
  - **Failure in card creation** → `FAILED`.
- In `myOrder.blade.php`:
  - Clickable only when:
    - `woohoo_order_id` is set **and**
    - Status is `COMPLETE` or `PAID`.
  - Color coding:
    - `COMPLETE`/`PAID` → Green.
    - `PENDING` → Yellow.
    - `FAILED` → Red.

**Benefit:**

- Reduces confusion and support calls (“Order paid but not clickable”).
- UX aligns with backend logic and real payment/card status.

---

### 8. Woohoo API Reliability & Retries

**Enhancement:**

- Introduced/configured `MAX_RETRIES` (default 3) for Woohoo API calls.
- Centralized configuration via `.env`:
  - Allows tuning based on environment (UAT vs Production).

**Why it matters:**

- Network/API fluctuations are handled more gracefully.
- Reduces risk of “orphaned” paid orders where Woohoo call failed once but would have succeeded on retry.

---

### 9. Documentation & Test Plan

- **File**: `WOOHOO_API_TEST_PLAN.md`
  - Defines scenarios to test:
    - Normal purchase.
    - Failed purchase.
    - Resend flows.
    - Decryption fallback.
    - Email/SMS sending.
- **File**: `IMPLEMENTATION_CHANGELOG.md`
  - Provides a structured summary of all major implementation changes (beyond just payments/security).

---

### 10. Summary – Value Delivered to Client

In business terms, the work delivered:

- **Eliminated a high‑risk underpayment fraud vector** (₹1 vs ₹10,000 scenario) by:
  - Removing client‑side control of payable amounts.
  - Enforcing server‑side amount calculation from trusted data.
- **Strengthened authorization and ownership checks** on orders, reducing cross‑account access risks.
- **Hardened business rules**:
  - Monthly purchase limits now enforced safely under concurrency.
  - Order and payment statuses are reliable and auditable.
- **Improved resilience and recovery** for card data and Woohoo API interactions.
- **Enhanced transparency** with logs, test plans, and documentation enabling easier audits and future maintenance.

This document, alongside `IMPLEMENTATION_CHANGELOG.md`, can be used to clearly demonstrate the scope and importance of the security and payment‑related work when communicating with the client and when justifying project billing.

