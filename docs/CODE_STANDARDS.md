# AmazePays -- Code Standards

## Controller Rules

1. **Thin controllers** -- Controllers are HTTP adapters only. They:
   - Receive a validated request via a Form Request class
   - Delegate business logic to a Service
   - Return a response using the `ApiResponse` trait (JSON) or Inertia (admin)
2. **Constructor injection** -- All dependencies injected via `__construct()`.
3. **No raw, unvalidated input** -- Prefer `$request->validated()` from a Form Request. If you use `$request->validate([...])`, use its return value (or `$validator->validated()` after `Validator::make`). Avoid arbitrary `$request->input()` for fields that are not part of validated rules.
4. **PHPDoc on every public method** -- Include `@param`, `@return`, and `@throws`.

## HTTP request input & logging

1. **Never** pass `$request->all()` to:
   - `Validator::make()` (use `$request->only([keys matching your rules])` or a Form Request),
   - `session([...])` or sticky checkout state,
   - model `fill()` / `create()` / mass assignment,
   - `Log::info` / `Log::warning` / `Log::error` context arrays.
2. **Webhooks** -- After signature verification, pass **whitelisted** arrays into `PaymentService::handleGatewayCallback()` (see [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md#payload-whitelisting-and-application-logs) and [SECURITY.md](SECURITY.md#7-webhook-security)).
3. **Logs** -- Do not log passwords, OTPs, tokens, full card numbers, decrypted vouchers, complete gateway webhook bodies, or URLs that contain SMS/gateway credentials. Auth, SMS/OTP, profile, checkout, and payment return paths intentionally minimize or omit `Log::` calls; use `security_event_logs` / `audit_logs` when persistence is required.
4. **Exception** -- `ThreatDetectionService` may scan combined request input for attack patterns; that is the only intentional use of a full input bag for inspection. See [SECURITY.md](SECURITY.md#11-threat-detection--auto-blocking).

## Service Layer

- Business logic lives in `app/Services/`.
- Services are stateless and injected via the container.
- Services return DTOs (in `app/Data/`) -- never Eloquent models across layers.
- Financial operations use `DB::transaction()` with `lockForUpdate()`.

## Data Transfer Objects (DTOs)

- Located in `app/Data/`.
- All DTOs are `readonly class` using PHP 8.3 constructor promotion.
- DTOs have a `fromValidated(array $validated): self` factory method.
- DTOs have a `toArray(): array` method.

## Form Requests

- Located in `app/Http/Requests/{Domain}/`.
- Domains: `Admin`, `Api`, `Storefront`, `Payment`.
- Each request class defines `authorize()`, `rules()`, and `messages()`.

## API Response Envelope

All JSON API responses use the `ApiResponse` trait (`app/Http/Traits/ApiResponse.php`):

```json
{
  "success": true,
  "message": "Order placed successfully.",
  "data": { ... }
}
```

Error envelope:

```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "Insufficient balance: required X, available Y.",
    "details": { ... }
  }
}
```

## Domain Exceptions

Custom exceptions in `app/Exceptions/`:

| Exception | HTTP Status | Error Code |
|---|---|---|
| `InsufficientBalanceException` | 422 | `INSUFFICIENT_BALANCE` |
| `VoucherFulfillmentException` | 502 | `VOUCHER_FULFILLMENT_FAILED` |
| `PaymentFailedException` | 502 | `PAYMENT_FAILED` |
| `OrderCreationException` | 422 | `ORDER_CREATION_FAILED` |

All are auto-rendered to the standard error envelope by `bootstrap/app.php`.

## Database Transaction Rules

1. **Wrap all multi-step DB writes in `DB::transaction()`.**
2. **Use `lockForUpdate()`** on any row read-then-written (wallets, balances, limits).
3. **Calculate inside the transaction** -- never pass pre-calculated amounts into a closure.
4. **Idempotency keys** on payment callbacks and wallet operations to prevent double-processing.
5. **Order status transitions** enforced by `OrderStatusMachine::transition()`.

## Database migrations

- **No `try/catch` around schema changes** — migrations must fail loudly; use `Schema::hasTable()` / `Schema::hasColumn()` for idempotent guards.
- Full checklist: [MIGRATION_BEST_PRACTICES.md](./MIGRATION_BEST_PRACTICES.md)

## Response codes & localization

1. **Application codes** -- Use `App\Enums\ResponseCode` for domain/API outcomes. User-facing text lives in `resources/lang/{locale}/responses.php` (`ResponseCode::message()`).
2. **Provider codes** -- Map Woohoo numeric codes and Vouchagram envelope codes via `App\Enums\ProviderErrorCode` and `App\Support\ProviderResponseTranslator`. Messages live in `resources/lang/{locale}/provider_errors.php`.
3. **Legacy** -- `resources/lang/en/errors.php` remains for backward compatibility; prefer `provider_errors` for new code.

## Anti-Tampering

1. `PricingService` is the **single source of truth** for all monetary calculations.
2. Frontend never sends `amount`, `discount`, `gst`, or `grand_total`.
3. Payment callback amounts are verified against `order.grand_total`.
4. Admin role + permission required to update product discount/GST.

## Naming Conventions

- Controllers: `{Resource}Controller` (e.g., `OrderController`, `ProductController`)
- Services: `{Domain}{Verb}Service` (e.g., `OrderCreationService`, `PricingService`)
- Form Requests: `{Verb}{Resource}Request` (e.g., `PlaceOrderRequest`, `StoreProductRequest`)
- DTOs: `{Resource}Data` (e.g., `OrderData`, `BillingData`, `PricingResult`)
- Exceptions: `{Problem}Exception` (e.g., `InsufficientBalanceException`)

## Route Files

| File | Purpose |
|---|---|
| `routes/web.php` | Public storefront + authenticated user panel |
| `routes/auth.php` | Login, registration, OTP, email verification |
| `routes/checkout.php` | Checkout flows, order creation |
| `routes/payments.php` | Payment initiation + return URLs |
| `routes/webhooks.php` | Inbound payment callbacks (signature-verified) |
| `routes/admin.php` | Inertia admin panel at `/panel` |
| `routes/api.php` | Mobile app / B2B API (`/api/v1/`) |
