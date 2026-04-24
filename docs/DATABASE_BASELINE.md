# Database Baseline (Phase 3)

This project uses a **fresh migration baseline** (domain-grouped, small migrations) designed for:

- **Strict foreign keys** (MySQL) and **SQLite-compatible tests** (PHPUnit)
- **Multi-tenancy**: every business table is tenant-scoped (`tenant_id`) except platform-global tables
- **Normalized schema**: wide “god tables” are split by concern (identity, profile, auth secrets, etc.)
- **Consolidated payments**: gateway-specific tables are replaced by `payments` + `payment_events`

## Running the baseline locally

```bash
php artisan migrate:fresh --seed
```

PHPUnit uses SQLite in-memory by default (`phpunit.xml`). For an on-disk SQLite run:

```bash
php artisan migrate:fresh --seed --force
```

## Cross-domain foreign keys

Some columns are created early (for indexing/querying) and constrained later once the target table exists.

Defined in:
- `database/migrations/2026_05_01_099001_add_cross_domain_foreign_keys.php`

Wired FKs:
- `products.hsn_sac_code_id` → `hsn_sac_codes.id` (nullOnDelete)
- `payments.payment_instrument_id` → `payment_instruments.id` (nullOnDelete)
- `payments.applied_bank_offer_id` → `bank_offers.id` (nullOnDelete)

SQLite note: SQLite cannot `ALTER TABLE ... ADD CONSTRAINT`, so the FK wiring migration is a **no-op** on SQLite. MySQL enforces these constraints.

## Core domains and tables (high level)

- **Tenancy**: `tenants`
- **Identity/Auth**: `users`, `user_profiles`, `user_auth_identities`, `user_auth_secrets`, `user_otp_codes`, `user_contact_channels`, `user_addresses`, `trusted_devices`, `two_factor_secrets`, `api_tokens`, `transaction_pins`
- **Catalog**: `brands`, `categories`, `products` (+ child tables like `product_media`, `product_descriptions`, denominations/ranges, audiences)
- **Cart/Orders**: `carts`, `cart_items`, `cart_gift_details`, `orders` + `order_*` snapshot/audit tables
- **Payments**: `payments`, `payment_events`, `refunds`, `idempotency_keys`
- **Wallet**: `wallets`, `wallet_transactions`, `wallet_load_requests`, `wallet_holds`
- **Fulfilment**: `provider_orders`, `gift_cards`, `gift_card_events`, `gift_card_deliveries`, `gift_card_balance_snapshots`
- **Promotions**: `promotion_campaigns`, `offers` (+ eligibility/audience/rules/usages), `bank_issuers`, `payment_instruments`, `bank_offers` (+ usages)
- **KYC**: `kyc_thresholds`, `kyc_profiles`, `kyc_documents`, `kyc_document_verifications`, `order_kyc_requirements`
- **Loyalty/Referrals**: `loyalty_programs`, `loyalty_tiers`, `loyalty_point_transactions`, `referrals`, `cashback_credits`
- **Support/Security/SEO**: `support_tickets` (+ messages/attachments/history), `audit_logs`, `security_event_logs`, `blocked_ips`, `blocked_mobiles`, `ip_whitelists`, `seo_metas`, `url_redirects`, `sitemap_entries`
- **Provider Integrations**: `provider_connections`, `provider_sync_runs`, `provider_api_call_logs`

## Cascade rules (guiding principles)

- **Tenant deletion**: cascades to tenant-scoped business data (dev/demo), but in production we typically prefer `archived` status + soft deletes over hard deletes.
- **Order deletion**: cascades to order child snapshot tables (they are immutable and exist only in the context of the order).
- **Payment deletion**: cascades to dependent payment events/refunds in baseline; production retention may prefer soft-deletes or archival.

## Seeders included

`DatabaseSeeder` calls:
- `RolesAndPermissionsSeeder`
- `BaselineTenantAndUsersSeeder`
- `BankIssuersAndInstrumentsSeeder`
- `KycThresholdsSeeder`
- `LoyaltyProgramSeeder`
- `TaxReferenceSeeder`

