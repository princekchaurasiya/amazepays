# AmazePays Wallet System

> **Version:** 2.0  
> **Last Updated:** April 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Wallet Architecture](#2-wallet-architecture)
3. [Transaction Types](#3-transaction-types)
4. [Bank Load Flow](#4-bank-load-flow)
5. [Wallet Payment Flow](#5-wallet-payment-flow)
6. [Concurrency & Safety](#6-concurrency--safety)
7. [Wallet Service API](#7-wallet-service-api)
8. [Admin Operations](#8-admin-operations)
9. [Provider Wallet Balances](#9-provider-wallet-balances)
10. [Reporting](#10-reporting)

---

## 1. Overview

The AmazePays wallet system provides internal balance management for both B2B clients and B2C consumers. B2B clients load their wallets via bank transfers and use wallet balance for voucher purchases. B2C consumers can optionally use wallet for faster checkout.

### Wallet Types

| Type | Users | Load Method | Primary Use |
|------|-------|-------------|-------------|
| B2B Wallet | B2B clients | Bank transfer (admin-approved) | Bulk voucher purchases |
| B2C Wallet | Consumers | Payment gateway / refunds | Quick checkout |
| Provider Wallet | System | API balance check | Track balance with Woohoo, KGen, etc. |

---

## 2. Wallet Architecture

### Data Model

```
users
  └──── wallets (1:1)
           ├── id
           ├── user_id (FK, unique)
           ├── tenant_id (FK, nullable)
           ├── balance (DECIMAL 12,2)
           └──── wallet_transactions (1:N)
                    ├── id
                    ├── wallet_id (FK)
                    ├── amount (DECIMAL 12,2)
                    ├── type (credit / debit)
                    ├── reference (unique, e.g. CR-xxxx, DB-xxxx)
                    ├── description
                    └── created_at

wallet_load_requests
  ├── id
  ├── tenant_id (FK)
  ├── user_id (FK)
  ├── amount
  ├── bank_reference (UTR)
  ├── bank_name
  ├── proof_document (file path)
  ├── status (pending / approved / rejected)
  ├── approved_by (FK → users)
  ├── approved_at
  ├── rejection_reason
  └── notes
```

### Wallet Lifecycle

```
User created
    │
    ▼
Wallet auto-created (balance = 0.00)
    │
    ├── B2B: Request bank load → Admin approves → Credit
    ├── B2C: Refund credited → Balance available
    ├── B2C: Direct deposit via gateway (future)
    │
    ▼
Wallet has balance
    │
    ├── Purchase voucher → Debit
    ├── Admin manual adjustment → Credit or Debit
    │
    ▼
Transaction recorded with reference
```

### Auto-Creation on User Registration

```php
// app/Models/User.php (existing)
protected static function booted()
{
    static::created(function ($user) {
        $user->wallet()->create([
            'balance' => 0,
            'tenant_id' => $user->currentTenantId(),
        ]);
    });
}
```

---

## 3. Transaction Types

### Reference Prefixes

| Type | Prefix | Description | Example |
|------|--------|-------------|---------|
| `credit:bank-load` | `BL-` | Bank load approved by admin | `BL-663f1a2b` |
| `credit:refund` | `RF-` | Order refund | `RF-663f1b3c` |
| `credit:adjustment` | `CA-` | Manual admin credit | `CA-663f1c4d` |
| `credit:cashback` | `CB-` | Offer cashback | `CB-663f1d5e` |
| `debit:purchase` | `PU-` | Voucher purchase | `PU-663f1e6f` |
| `debit:adjustment` | `DA-` | Manual admin debit | `DA-663f1f70` |

### Transaction Immutability

- Transactions are **append-only** (never updated or deleted)
- Corrections are made via new offsetting transactions
- `reference` column is unique to prevent duplicate processing
- `wallet.balance` is the authoritative balance (denormalized for performance)
- Periodic reconciliation job verifies `balance == SUM(credits) - SUM(debits)`

---

## 4. Bank Load Flow

### Request Flow

```
B2B Client                 System                    Admin
    │                        │                         │
    │ 1. Submit load request │                         │
    │ ──────────────────────>│                         │
    │   amount: ₹50,000      │                         │
    │   UTR: UTIB12345678    │                         │
    │   bank: Axis Bank      │                         │
    │   proof: receipt.pdf   │                         │
    │                        │                         │
    │                        │ 2. Create record         │
    │                        │    status = pending      │
    │                        │                         │
    │                        │ 3. Notify admin ────────>│
    │                        │    (email + in-app)      │
    │                        │                         │
    │ 4. "Request submitted" │                         │
    │ <──────────────────────│                         │
    │                        │                         │
    │                        │         5. Admin reviews │
    │                        │         - View UTR       │
    │                        │         - Check bank stmt│
    │                        │         - View proof doc │
    │                        │                         │
    │                        │ 6a. APPROVE ────────────│
    │                        │ <──────────────────────│
    │                        │                         │
    │                        │ 7. Credit wallet         │
    │                        │    - DB transaction      │
    │                        │    - Increment balance   │
    │                        │    - Create WalletTxn    │
    │                        │    - Log audit trail     │
    │                        │                         │
    │ 8. Notification         │                         │
    │ <──────────────────────│                         │
    │ "₹50,000 credited"     │                         │
    │                        │                         │
    │                     OR                            │
    │                        │                         │
    │                        │ 6b. REJECT ─────────────│
    │                        │ <──────────────────────│
    │                        │    reason: "UTR invalid" │
    │                        │                         │
    │ 8. Notification         │                         │
    │ <──────────────────────│                         │
    │ "Request rejected"     │                         │
```

### Load Request Statuses

```
PENDING ──── APPROVED
   │
   └──── REJECTED
```

No status can go backwards. A rejected request requires a new submission.

### Admin Load Request Queue

```
┌──────────────────────────────────────────────────────────┐
│ Wallet Load Requests                    Filter: [All ▼]  │
│                                                          │
│ ┌────────────────────────────────────────────────────┐   │
│ │ Client        │ Amount    │ UTR          │ Status  │   │
│ ├───────────────┼───────────┼──────────────┼─────────┤   │
│ │ TechCorp Ltd  │ ₹50,000  │ UTIB12345678 │ PENDING │   │
│ │ RetailMax     │ ₹100,000 │ HDFC98765432 │ PENDING │   │
│ │ GiftHub Inc   │ ₹25,000  │ SBIN44556677 │ APPROVED│   │
│ └────────────────────────────────────────────────────┘   │
│                                                          │
│ [Click row to review]                                    │
└──────────────────────────────────────────────────────────┘

Load Request Detail:
┌──────────────────────────────────────────────────────────┐
│ Load Request #42                                         │
│                                                          │
│ Client:      TechCorp Ltd                                │
│ User:        Rajesh Kumar                                │
│ Amount:      ₹50,000.00                                  │
│ Bank:        Axis Bank                                   │
│ UTR:         UTIB12345678                                │
│ Submitted:   April 7, 2026 at 10:30 AM                  │
│                                                          │
│ Proof Document: [View receipt.pdf]                       │
│                                                          │
│ Admin Notes: [________________________]                  │
│                                                          │
│ [Approve] [Reject]                                       │
│                                                          │
│ If rejecting, reason is required:                        │
│ [________________________]                               │
└──────────────────────────────────────────────────────────┘
```

---

## 5. Wallet Payment Flow

### B2B Order Payment via Wallet

```php
// OrderService.php

public function createOrder(CreateOrderRequest $request): Order
{
    return DB::transaction(function () use ($request) {
        // 1. Create order
        $order = Order::create([...]);

        // 2. Debit wallet
        $this->walletService->debit(
            $request->user(),
            $order->grand_payable_amount,
            "Voucher purchase: {$order->product_name} x{$order->quantity}"
        );

        // 3. Update order status
        $order->update(['order_status' => 'PAID', 'order_payment' => 'wallet']);

        // 4. Create order summary
        OrderSummary::create([
            'order_id' => $order->id,
            'payment_status' => 'Success',
            'order_status' => 'PROCESSING',
            'payment_gateway' => 'wallet',
        ]);

        // 5. Dispatch fulfillment
        PlaceVoucherOrderJob::dispatch($order);

        return $order;
    });
}
```

### B2C Consumer Wallet Payment

Same flow but without tenant scoping. Consumer wallet balance comes from:
- Refunds from failed orders
- Cashback from offers
- Manual credit by admin (rare, for support cases)

---

## 6. Concurrency & Safety

### Row-Level Locking

```php
// app/Services/WalletService.php (enhanced from existing)

class WalletService
{
    public function credit(User $user, float $amount, string $description = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive');
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            // Lock the wallet row to prevent concurrent modifications
            $wallet = Wallet::where('id', $user->wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('balance', $amount);

            return $wallet->transactions()->create([
                'amount'      => $amount,
                'type'        => 'credit',
                'reference'   => $this->generateReference('CR'),
                'description' => $description,
            ]);
        });
    }

    public function debit(User $user, float $amount, string $description = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive');
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = Wallet::where('id', $user->wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException(
                    "Insufficient balance. Required: {$amount}, Available: {$wallet->balance}"
                );
            }

            $wallet->decrement('balance', $amount);

            return $wallet->transactions()->create([
                'amount'      => $amount,
                'type'        => 'debit',
                'reference'   => $this->generateReference('DB'),
                'description' => $description,
            ]);
        });
    }

    private function generateReference(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(8));
    }
}
```

### Safety Mechanisms

| Risk | Mitigation |
|------|-----------|
| Double-debit (race condition) | `lockForUpdate()` on wallet row |
| Negative balance | Balance check inside transaction, after lock |
| Duplicate transaction | Unique `reference` column, idempotent operations |
| Inconsistent balance | Periodic reconciliation job |
| Unauthorized debit | Policy-based authorization on all wallet operations |

### Balance Reconciliation

```php
// Scheduled daily at 3 AM
// Compares wallet.balance with SUM of transactions

$discrepancies = DB::select("
    SELECT w.id, w.user_id, w.balance as stored_balance,
           COALESCE(SUM(CASE WHEN wt.type = 'credit' THEN wt.amount ELSE 0 END), 0) -
           COALESCE(SUM(CASE WHEN wt.type = 'debit' THEN wt.amount ELSE 0 END), 0) as calculated_balance
    FROM wallets w
    LEFT JOIN wallet_transactions wt ON wt.wallet_id = w.id
    GROUP BY w.id, w.user_id, w.balance
    HAVING stored_balance != calculated_balance
");

// Alert admin if any discrepancies found
```

---

## 7. Wallet Service API

### Consumer API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/wallet/balance` | Sanctum | Get current balance |
| GET | `/api/v1/wallet/transactions` | Sanctum | List transactions (paginated) |
| POST | `/api/v1/wallet/load-request` | Sanctum | Request bank load (B2B) |

### Response Examples

**Balance:**
```json
{
  "success": true,
  "data": {
    "balance": 45250.00,
    "currency": "INR",
    "last_updated": "2026-04-07T12:00:00Z"
  }
}
```

**Transactions:**
```json
{
  "success": true,
  "data": [
    {
      "id": 142,
      "type": "debit",
      "amount": 4750.00,
      "reference": "PU-a1b2c3d4e5f6g7h8",
      "description": "Voucher purchase: Amazon Gift Card x5",
      "created_at": "2026-04-07T11:30:00Z"
    },
    {
      "id": 141,
      "type": "credit",
      "amount": 50000.00,
      "reference": "BL-f8e7d6c5b4a39281",
      "description": "Bank load approved (UTR: UTIB12345678)",
      "created_at": "2026-04-07T10:00:00Z"
    }
  ],
  "pagination": { "current_page": 1, "per_page": 20, "total": 89 }
}
```

---

## 8. Admin Operations

### Manual Credit / Debit

Super admins can manually adjust wallet balances:

```
┌──────────────────────────────────────────┐
│ Manual Wallet Adjustment                  │
│                                          │
│ User: Rajesh Kumar (TechCorp Ltd)        │
│ Current Balance: ₹45,250.00              │
│                                          │
│ Operation: ( ) Credit  ( ) Debit         │
│ Amount: [₹ ________]                    │
│ Reason: [________________________]       │
│                 (required)               │
│                                          │
│ [Cancel] [Submit Adjustment]             │
└──────────────────────────────────────────┘
```

All manual adjustments:
- Require a reason (stored in transaction description)
- Are logged in audit_logs
- Trigger notification to the user
- Require `super-admin` or `admin` role with `wallets.credit` / `wallets.debit` permission

---

## 9. Provider Wallet Balances

Separate from user wallets, these track AmazePays's balance with each voucher provider:

| Provider | Model | Check Method |
|----------|-------|-------------|
| Woohoo | Voyager settings (legacy) | API call on demand |
| KGen | `k_gen_wallet_balances` | Artisan command (`FetchKGenWalletBalance`) |
| Value Design | API | Artisan command (`get:vdWalletBalance`) |
| Lysto/Athena | API | Artisan command (`get:lystoWalletBalance`) |

### Dashboard Widget

```
Provider Balances:
┌─────────────────────────────────────────┐
│ Woohoo:       ₹ 2,45,000   [Refresh]   │
│ KGen:         ₹ 1,12,500   [Refresh]   │
│ Value Design: ₹    85,000   [Refresh]   │
│ Lysto:        ₹    42,000   [Refresh]   │
│                                         │
│ Total:        ₹ 4,84,500               │
│ Last checked: 2 hours ago               │
└─────────────────────────────────────────┘
```

Balances cached in Redis (TTL: 5 minutes) and refreshed on button click or by scheduled job.

---

## 10. Reporting

### Wallet Reports

| Report | Content | Scope |
|--------|---------|-------|
| Balance Summary | All wallets with current balance | Admin: all, B2B: own tenant |
| Load Request History | All load requests with status | Admin: all, B2B: own |
| Transaction Report | All transactions in date range | Admin: all, B2B: own tenant |
| Reconciliation Report | Balance vs transaction sum | Admin only |

### Export Formats

- CSV (for import into Excel/accounting software)
- XLSX (formatted with headers)
- PDF (for printing/archival)

---

## 11. Fraud Detection Integration

Every wallet operation passes through `WalletFraudDetector` before execution:

| Check | Trigger | Action |
|-------|---------|--------|
| VPN/proxy detected | Any wallet operation | Block transaction |
| >10 transactions/hour | Velocity limit | Require OTP re-verification |
| Amount > 3x user average | Unusual pattern | Flag for admin review |
| New IP + high amount | Combined risk | Require OTP |
| Load → immediate full spend | Drain pattern | Block + alert admin |
| Different country from registration | Geo anomaly | Block + alert admin |
| Multiple failed attempts | 5 failures in 10 min | Temporary lockout |

All fraud checks are logged to `security_event_logs` with event_type `wallet_fraud_check`. Admins can review flagged transactions in the Security Dashboard.

See [SECURITY.md](SECURITY.md) Section 10 for full `WalletFraudDetector` implementation.

---

## Related Documents

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Validated wallet API inputs and log hygiene
- [B2B_TENANCY.md](B2B_TENANCY.md) -- B2B client wallet access
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) -- Wallet API endpoints
- [SECURITY.md](SECURITY.md) -- VPN detection, fraud prevention, security event logging
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Wallet table schemas
- [ADMIN_PANEL.md](ADMIN_PANEL.md) -- Admin wallet management UI
