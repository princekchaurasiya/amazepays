# Test Commands Documentation

This document describes all available test commands for the Gift Giggles application.

## Table of Contents
- [Woohoo API Test Commands](#woohoo-api-test-commands)
- [PHPUnit Tests](#phpunit-tests)
- [Quick Reference](#quick-reference)

---

## Woohoo API Test Commands

### 1. Test Order Creation (`woohoo:test-order`)

Test Woohoo Order API with various scenarios.

**Usage:**
```bash
php artisan woohoo:test-order [options]
```

**Options:**
- `--sku=CNPIN` - Product SKU to test (default: CNPIN)
- `--amount=1` - Order amount/denomination (default: 1)
- `--qty=1` - Quantity of cards (default: 1)
- `--sync-only=true` - Use sync_only mode (default: true)
- `--scenario=success-single` - Test scenario name
- `--refno=` - Custom reference number (auto-generated if not provided)

**Examples:**
```bash
# Test single card success scenario
php artisan woohoo:test-order --sku=CNPIN --amount=1 --qty=1 --scenario=success-single

# Test multiple cards (may take 40-60 seconds)
php artisan woohoo:test-order --sku=CNPIN --amount=1 --qty=5 --scenario=success-multiple

# Test voucher code
php artisan woohoo:test-order --sku=VOUCHERCODE --amount=100 --scenario=voucher-code

# Test validation error
php artisan woohoo:test-order --sku=VOUCHERCODE --amount=90 --scenario=validation-error

# Test timeout scenario
php artisan woohoo:test-order --sku=testsuccess001 --amount=1 --sync-only=true --scenario=timeout-success
```

**Available Scenarios:**
- `success-single` - Single card order (status: COMPLETE)
- `success-multiple` - Multiple cards order (status: PROCESSING initially)
- `voucher-code` - Voucher code only (no PIN)
- `claimcode` - Amazon claim code (16 digit card, 14 digit PIN)
- `activation-url` - UBF card with activation URL
- `validation-error` - Invalid denomination error
- `disabled-product` - Disabled product error
- `processing-status` - Processing status response
- `duplicate-refno` - Duplicate reference number error
- `timeout-success` - Timeout with successful order
- `timeout-failure` - Timeout with failed order
- `multiple-skus` - Multiple SKUs error

---

### 2. Test Fetch Cards (`woohoo:test-fetch-cards`)

Test Woohoo Activated Cards API to fetch card details for an order.

**Usage:**
```bash
php artisan woohoo:test-fetch-cards [options]
```

**Options:**
- `--order-id=` - Woohoo Order ID (required)
- `--refno=` - Reference number (alternative to order-id)

**Examples:**
```bash
# Fetch cards using order ID
php artisan woohoo:test-fetch-cards --order-id=ORDER_123456

# Note: Order ID is required for Activated Cards API
```

---

### 3. Test Catalog API (`woohoo:test-catalog`)

Test Woohoo Catalog API (Categories, Products).

**Usage:**
```bash
php artisan woohoo:test-catalog [options]
```

**Options:**
- `--type=categories` - Type of test: categories, products, or product (default: categories)
- `--category-id=` - Category ID for products test (required for products type)
- `--sku=` - Product SKU for single product test (required for product type)
- `--offset=0` - Offset for pagination (default: 0)
- `--limit=100` - Limit for pagination (default: 100)

**Examples:**
```bash
# Test categories API
php artisan woohoo:test-catalog --type=categories

# Test products by category
php artisan woohoo:test-catalog --type=products --category-id=1

# Test single product
php artisan woohoo:test-catalog --type=product --sku=CNPIN
```

---

### 4. Test Order Status (`woohoo:test-status`)

Test Woohoo Order Status API.

**Usage:**
```bash
php artisan woohoo:test-status <refno>
```

**Arguments:**
- `refno` - Reference number (required)

**Examples:**
```bash
# Check order status
php artisan woohoo:test-status Amz20250112000001
```

---

### 5. Run All Tests (`woohoo:test-all`)

Run all Woohoo API test scenarios.

**Usage:**
```bash
php artisan woohoo:test-all [options]
```

**Options:**
- `--skip-catalog` - Skip catalog API tests
- `--skip-order` - Skip order API tests
- `--detailed` - Show detailed output

**Examples:**
```bash
# Run all tests
php artisan woohoo:test-all

# Run all tests with detailed output
php artisan woohoo:test-all --detailed

# Skip catalog tests
php artisan woohoo:test-all --skip-catalog

# Skip order tests
php artisan woohoo:test-all --skip-order
```

---

## PHPUnit Tests

### Running PHPUnit Tests

**Run all tests:**
```bash
phpunit
# or
composer test
```

**Run specific test suite:**
```bash
# Unit tests only
phpunit --testsuite=Unit
# or
composer test:unit

# Feature tests only
phpunit --testsuite=Feature
# or
composer test:feature
```

**Run specific test class:**
```bash
# Wallet tests
phpunit --filter WalletTest
# or
composer test:wallet

# API tests
phpunit --filter ApiTest
# or
composer test:api

# Woohoo API tests
phpunit --filter WoohooApiTest
```

**Run with coverage:**
```bash
phpunit --coverage-html coverage/
```

### Available Test Classes

1. **WalletTest** (`tests/Feature/WalletTest.php`)
   - Tests wallet creation, credit, debit operations
   - Tests insufficient balance scenarios

2. **WoohooApiTest** (`tests/Feature/WoohooApiTest.php`)
   - Tests Woohoo Order API scenarios
   - Tests Activated Cards API
   - Tests Catalog API (Categories, Products)
   - Tests Order Status API
   - Tests reference number uniqueness
   - Tests payment method validation

3. **ApiTest** (`tests/Feature/ApiTest.php`)
   - Tests authentication APIs (OTP send/verify)
   - Tests products API
   - Tests wallet API endpoints
   - Tests authentication requirements

---

## Quick Reference

### Composer Test Scripts

All test commands are available via composer scripts:

```bash
# PHPUnit tests
composer test              # Run all PHPUnit tests
composer test:unit         # Run unit tests only
composer test:feature      # Run feature tests only
composer test:wallet       # Run wallet tests
composer test:api          # Run API tests

# Woohoo API tests
composer test:woohoo              # Run all Woohoo tests
composer test:woohoo-order         # Test order creation
composer test:woohoo-catalog      # Test catalog API
composer test:woohoo-status       # Test order status
composer test:woohoo-fetch-cards  # Test fetch cards
```

### Artisan Test Commands

```bash
# Woohoo API tests
php artisan woohoo:test-all
php artisan woohoo:test-order [options]
php artisan woohoo:test-fetch-cards [options]
php artisan woohoo:test-catalog [options]
php artisan woohoo:test-status <refno>
```

---

## Test Environment Setup

Before running tests, ensure:

1. **Database Configuration:**
   - Test database is configured in `.env` or `phpunit.xml`
   - Database migrations are run: `php artisan migrate`

2. **Woohoo API Credentials:**
   - `WOOHOO_URL` - Woohoo API base URL
   - `QS_CLIENT_SECRET` - Client secret for signature generation
   - `BEARER_TOKEN` - Bearer token for API authentication
   - These should be set in your `.env` file or via Voyager settings

3. **Test Data:**
   - Products should exist in `qs_products` table
   - Test SKUs: `CNPIN`, `VOUCHERCODE`, `CLAIMCODE`, `UBERLOW`

---

## Troubleshooting

### Common Issues

1. **"Woohoo API credentials not configured"**
   - Check that Voyager settings are configured
   - Verify `api.woohoo_url`, `api.qs_clientSecret`, and `api.bearer_token` are set

2. **"Product with SKU not found"**
   - Ensure products are synced: `php artisan fetch:productData`
   - Or manually add test products to `qs_products` table

3. **"Connection timeout"**
   - This may be expected for timeout test scenarios
   - Check network connectivity
   - Verify Woohoo API is accessible

4. **PHPUnit tests failing**
   - Ensure database is set up: `php artisan migrate`
   - Check `.env` file has correct test database configuration
   - Run `composer install` to ensure all dependencies are installed

---

## Notes

- Test commands use the actual Woohoo API (not mocks) - ensure you're using test/UAT credentials
- Some tests may take 40-60 seconds (e.g., multiple cards, timeout scenarios)
- Test commands log all requests/responses for debugging
- PHPUnit tests use HTTP mocking to avoid actual API calls during automated testing

---

## Related Documentation

- [WOOHOO_API_TEST_PLAN.md](./WOOHOO_API_TEST_PLAN.md) - Detailed test plan and scenarios
- [DATABASE_SETUP.md](./DATABASE_SETUP.md) - Database setup instructions
