# REFACTOR_AUDIT (Phase 7)

This document records the cross-cutting changes introduced during the refactor so future work can be traced and safely removed/expired.

## Phase 4 — Routes & API cleanup (PR 5)
- **Canonical API base**: `/api/v1/*` (see `routes/api.php`)
- **Payments sessions**
  - `POST /api/v1/payments/sessions`
  - `POST /api/v1/payments/sessions/{id}/verify`
  - `GET /api/v1/payments/sessions/{id}`
- **Orders**
  - `GET /api/v1/orders/{order}`
  - `POST /api/v1/orders/{order}/refund`
- **Customers**
  - `GET /api/v1/customers/{customer}`
- **Legacy payment endpoints (one release)**
  - `/payment-process`, `/vd-payment`, `/kgen-payment/*`, `/payment/netbnk` → temporary redirects (to be deleted later)
- **OpenAPI**
  - Generated via Scramble to `openapi.json`

## Phase 5 — Inertia-only UI consolidation (PR 6a/6b/6c)
- **Views policy**
  - Only `resources/views/app.php` is allowed as the Inertia shell.
  - Any legacy helper templates under `resources/views/**` are removed.
- **Checkout**
  - `Checkout/Index` is the single payment entry with gateway tiles (CCAvenue/Razorpay/Unlimit).
- **Admin**
  - Promotions/Integrations/Pricing/Payments screens added under `resources/js/Pages/Admin/**`
- **Pricing envelope**
  - `GET /api/v1/products/{sku}/pricing` returns `PricingResult` envelope.
- **Shared pricing UI**
  - `resources/js/Components/Pricing/PriceBreakdown.tsx`

## Phase 6 — Perf, security, queues (PR 7)
- **Fulfillment**: paid order fulfillment is queued (`FulfillPaidOrderJob`, queue `fulfillment`)
- **Emails**: order emails are queued via `Mail::queue()`

## Phase 7 — Docs & cleanup (PR 8)
- **Managed redirects**
  - `url_redirects` table is enforced via `ApplyUrlRedirects` middleware.
  - Expired redirects return HTTP **410 Gone**.

