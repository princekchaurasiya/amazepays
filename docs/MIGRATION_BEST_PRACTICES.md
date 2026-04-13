# Database migration best practices (AmazePays)

Use this as the checklist for every new migration under `database/migrations/`.

## Do

- **Fail loudly** — Let schema errors stop the migration. Do not wrap `Schema::` / `Blueprint` work in `try/catch` that swallows exceptions.
- **Guard when idempotent** — Use `Schema::hasTable()` and `Schema::hasColumn()` before adding columns or constraints that might already exist in some environments.
- **Order by filename** — Laravel runs migrations in timestamp order. Create referenced tables **before** foreign keys that point to them (see e.g. `orders.tenant_id` FK after `tenants` exists).
- **Match column types for FKs** — Referenced and referencing columns must be the same sign/size (e.g. `products.id` is unsigned int → use `unsignedInteger('product_id')`, not `foreignId()` alone if it would be bigint).
- **Laravel 13 / DBAL** — Avoid removed APIs such as `getDoctrineSchemaManager()`. Prefer raw `DB::statement('ALTER TABLE ...')` on MySQL when `->change()` is insufficient.

## Primary key types in this project (FK cheat sheet)

MySQL requires compatible types on both sides of a foreign key. Laravel helpers:

| Table / PK | How it is defined | Use for FK column |
|------------|-------------------|-------------------|
| `products.id` | `increments` | `unsignedInteger` + `$table->foreign(...)` (not bare `foreignId`) |
| `categories.id` | `increments` (Voyager; fallback in `2024_10_04_101631_create_categories_table_when_missing` matches this) | `unsignedInteger` + foreign |
| `synced_categories.id`, `users.id`, `tenants.id`, `orders.id`, `storefront_brands.id`, … | `id()` / `foreignId` target | `foreignId` or `unsignedBigInteger` |

When adding a column that references a table, open that table’s `create_*` migration and match its `id` column exactly.

## Don’t

- **Don’t hide errors** — Empty `catch` blocks in migrations lead to half-applied schema and hard-to-debug production drift.
- **Don’t rely on try/catch for "optional" FKs** — Fix migration order or split migrations instead.

## Safe pattern

```php
if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'phone')) {
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone')->nullable();
    });
}
```

## Anti-pattern

```php
try {
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone');
    });
} catch (\Throwable) {
    // Never do this in migrations
}
```

## Transactions

On MySQL with InnoDB, Laravel runs each migration inside a transaction when supported; a failing step rolls back that migration batch—provided the failure is not caught and ignored.

## Audit

To confirm there are no swallowed exceptions in migrations:

```bash
rg "try\s*\{|catch\s*\(" database/migrations
```

(Expected: no matches.)

## Renaming migration files (existing databases)

Laravel records the **filename without `.php`** in the `migrations` table. If you rename files after they have already run in an environment, update that row so Artisan does not try to run the migration again.

**Consolidation:** Dozens of incremental “add column” migrations were merged into the corresponding `create_*` migrations. Those files no longer exist. If an old database’s `migrations` table still lists a removed name, `php artisan migrate` will fail (missing class/file). For non-production data you can use `php artisan migrate:fresh`. For a long-lived DB, remove only rows whose schema work is already reflected in the database (treat folded steps as applied), or restore matching stub migrations—prefer coordinating with the team.

**Renames that still map to files in the repo** (adjust the `WHERE` side to match what your DB actually has):

```sql
UPDATE migrations SET migration = '2024_10_04_101631_create_categories_table_when_missing' WHERE migration = '2024_10_04_101631_create_amazepay_categories_table';
UPDATE migrations SET migration = '2024_10_04_101707_create_category_product_pivot_table' WHERE migration = '2024_10_04_101707_create_amazepay_category_product_table';
UPDATE migrations SET migration = '2024_10_05_102237_create_storefront_brands_table' WHERE migration IN ('2024_10_05_102237_create_available_brands_table','2024_10_05_102237_create_amazepay_available_brands_table');

-- Catalog: `qs_` dropped from filenames
UPDATE migrations SET migration = '2024_10_04_101650_create_products_table' WHERE migration = '2024_10_04_101650_create_qs_products_table';
UPDATE migrations SET migration = '2026_01_13_140000_create_synced_categories_table' WHERE migration = '2026_01_13_140000_create_qs_categories_table';
UPDATE migrations SET migration = '2026_01_13_142909_create_orders_table' WHERE migration = '2026_01_13_142909_create_qs_orders_table';
```

For local/dev, `php artisan migrate:fresh` after a rename or consolidation is simpler.

---

## See also

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Broader Laravel/backend conventions (validation, logging)
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Table inventory
