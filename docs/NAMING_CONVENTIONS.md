# Naming Conventions (AmazePays)

This document is the single source of truth for naming across the AmazePays Laravel + Inertia React codebase.

## Backend (Laravel)

- **Controllers**: `PascalCase`, singular domain noun + `Controller` (`CheckoutController`, `PaymentSessionController`). Webhooks only: `...CallbackController`.
- **Methods**: intent verbs (`createCheckoutSession`, `verifyPaymentCallback`, `refundPayment`).
- **Models**: `PascalCase` singular (`Order`, `Payment`, `GiftCard`).
- **Tables**: `snake_case` plural (`orders`, `payments`, `gift_cards`).
- **Columns**: `snake_case` nouns (`order_id`, `gateway_payment_id`).
  - Booleans: `is_*` / `has_*`
  - Timestamps: `*_at`
  - Money: `*_minor` integer + `currency` (`char(3)`)
- **Enums**: `PascalCase` (`PaymentStatus`, `ResponseCode`) and values are lowercase snake (`succeeded`, `pending`).

## Frontend (Inertia React)

- **Components/Pages**: `PascalCase.tsx`
- **Hooks**: `useCamelCase.ts`
- **Folders**: `kebab-case`
- **API / services**: noun-based (`ordersApi`, `productsApi`), avoid `common.ts`.

## General

- No ambiguous names like `TestController`, `CommonController`, `DataCard`, `tbl_*`, `col1`, `data`.
- Every rename must be logged in `REFACTOR_CHANGELOG.md` with old → new and rationale.

