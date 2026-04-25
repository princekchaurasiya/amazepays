# Refactor Changelog

This file logs every rename and structural change made during the phased refactor, so the project never ends up in a mixed old/new naming state.

## Format

```
- <date> <pr/branch>: <type>(<scope>) <summary>
  - before: <old>
  - after:  <new>
  - why:    <reason / benefit>
  - risk:   <webhook | db | api | ui | none>
```

## Entries

- 2026-04-25: refactor(payments) Drop legacy gateway tables in application code; use consolidated `payments` only.
  - before: `UnlimitPayment` model (`unlimit_payment`), `CcAvenuePayment` model (`cc_avenue_payment`)
  - after: `Payment` with `gateway = unlimit` / `ccavenue` / etc.; `Payment::findUnlimitForOrder()`, `isSuccessfulForFulfillment()`
  - why: Phase 3 baseline has a single `payments` table; legacy models pointed at archived migrations only.
  - risk: db (fresh installs never had legacy tables); api: none; webhook: none

- 2026-04-25: refactor(schema) Bridge legacy checkout fields on `orders`; align `order_summaries` model with baseline table.
  - before: `OrderSummary` pointed at non-existent `order_summary` table; `orders` lacked Woohoo/Unlimit columns; `GiftCard` used wrong table name
  - after: migrations `001303` / `001304`; `Order` / `OrderSummary` / `GiftCard` / `Product::orders()` updated; summary rows use `fulfilment_status` + `primary_gateway`
  - why: Phase 3 baseline migrations must match application code until fulfilment is fully normalized
  - risk: db (migrate:fresh required for new columns); api: none

- 2026-04-25: refactor(fulfilment) Persist Woohoo upstream ids on `provider_orders`; orchestrator resolves provider order id from that table first.
  - before: only `orders.woohoo_order_id`; no `ProviderOrder` model
  - after: `App\Models\ProviderOrder`, `ProviderOrderRecorder`, wired from `WoohooLegacyOrderSyncService` + `OrderFulfillmentOrchestrator`
  - why: Phase 3 baseline centres fulfilment on `provider_orders` + `gift_cards`; legacy column kept in sync via recorder
  - risk: db (new rows on successful sync); webhook: none

- 2026-04-25: refactor(fulfilment) Persist Woohoo card payloads into `gift_cards` + `gift_card_events` (encrypted numbers/pins).
  - before: cards only on `orders.cards` ciphertext blob
  - after: `WoohooGiftCardPersister`, `GiftCardEvent` model, wired from `WoohooLegacyOrderSyncService` (sync + post-fetch path); `Order::giftCards()`
  - why: Phase 3 normalizes instruments; legacy blob retained for existing readers
  - risk: db; `WoohooGiftCardPersistTest` guard

- 2026-04-25: refactor(storefront) Card detail page prefers `gift_cards` via `WoohooGiftCardPersister::displayCardsForOrder()`.
  - before: `ViewCardDetailsController` only decrypted `orders.cards`; eager-loaded nonexistent `Order::product`
  - after: normalized rows first, legacy blob + Woohoo fetch as fallback; fetch path calls `syncWoohooCards`; product image from `items.product`
  - why: single source of truth for issued instruments; fewer decrypt-only paths
  - risk: ui (Inertia `cardArray` shape preserved)

