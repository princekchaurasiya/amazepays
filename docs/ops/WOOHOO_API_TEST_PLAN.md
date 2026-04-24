# Woohoo API Integration Test Plan

## Overview
This document outlines all test scenarios that must be validated before going live with the Woohoo API integration.

## Test Environment Setup
- **UAT/Testing**: Timeout set to 10 seconds (as per Woohoo standards)
- **Production**: Timeout set to 40 seconds minimum
- **Retry Configuration**: MAX_RETRIES=3, RETRY_INTERVAL=40 seconds

---

## Test Scenarios

### 1. Success Scenarios

#### 1.1 Success - Card Number & Card PIN (Quantity=1)
**API**: Order API  
**Request**:
- SKU: `CNPIN`
- Amount: `1` (or any valid denomination)
- `delivery_mode`: `API`
- `sync_only`: `true`
- `qty`: `1`

**Expected Response**:
- `status`: `"COMPLETE"`
- `orderId`: Present
- `refno`: Matches request
- `cards` array: Contains 1 card with:
  - `cardNumber`: Present
  - `cardPin`: Present
  - `amount`: `"1000.00"` (or requested amount)
  - `validity`: Present

**Validation Points**:
- ✅ Order status in DB: `COMPLETE`
- ✅ Card data encrypted and stored
- ✅ Email sent with card details
- ✅ SMS sent with card details
- ✅ Order shows as green "Order Complete" in My Orders
- ✅ Order is clickable to view card details

**Test Command**:
```bash
php artisan woohoo:test-order --sku=CNPIN --amount=1 --qty=1 --scenario=success-single
```

---

#### 1.2 Success - Card Number & Card PIN (Quantity=5)
**API**: Order API  
**Request**:
- SKU: `CNPIN`
- Amount: `1` (or any valid denomination)
- `delivery_mode`: `API`
- `sync_only`: `true`
- `qty`: `5` (Note: Test with qty=2 first as per spec)

**Expected Response**:
- `status`: `"PROCESSING"`
- `orderId`: Present
- `refno`: Matches request
- `cards` array: **Empty** (cards not ready yet)

**Post-Processing**:
- Wait 40-60 seconds
- Call Activated Cards API with `orderId`
- Fetch card details

**Validation Points**:
- ✅ Order status in DB: `PENDING` initially, then `COMPLETE` after cards fetched
- ✅ Activated Cards API returns 5 cards
- ✅ All cards encrypted and stored
- ✅ Email/SMS sent after cards are fetched

**Test Command**:
```bash
php artisan woohoo:test-order --sku=CNPIN --amount=1 --qty=5 --scenario=success-multiple
```

---

#### 1.3 Fetch Card Details - Activated Cards API
**API**: Activated Cards API  
**Request**: Order ID from scenario 1.2

**Expected Response**:
- `cards` array: Contains 5 cards, each with:
  - `sku`: `"CNPIN"`
  - `cardNumber`: Present
  - `cardPin`: Present
  - `amount`: `"1000.00"`
  - `validity`: Present

**Validation Points**:
- ✅ Cards fetched successfully
- ✅ Cards encrypted and stored in DB
- ✅ Order status updated to `COMPLETE`
- ✅ Email/SMS sent

**Test Command**:
```bash
php artisan woohoo:test-fetch-cards --order-id=<orderId>
```

---

#### 1.4 Success - Card Number Only (Voucher Code)
**API**: Order API  
**Request**:
- SKU: `VOUCHERCODE`
- Amount: `100`
- `sync_only`: `true` or `false`

**Expected Response**:
- `status`: `"COMPLETE"`
- `cards` array: Contains 1 card with:
  - `sku`: `"VOUCHERCODE"`
  - `cardNumber`: Present (labeled as "Voucher Code")
  - `cardPin`: `null`
  - `amount`: `"100.00"`

**Validation Points**:
- ✅ UI shows "Voucher Code" instead of "Card PIN"
- ✅ Customer hint: "Only voucher code required for redemption"
- ✅ Email/SMS sent with voucher code

**Test Command**:
```bash
php artisan woohoo:test-order --sku=VOUCHERCODE --amount=100 --scenario=voucher-code
```

---

#### 1.5 Success - Amazon 16 Digit Card Number & 14 Digit Card PIN
**API**: Order API  
**Request**:
- SKU: `CLAIMCODE`
- Amount: `10`
- `sync_only`: `true` or `false`

**Expected Response**:
- `status`: `"COMPLETE"`
- `cards` array: Contains 1 card with:
  - `sku`: `"CLAIMCODE"`
  - `cardNumber`: 16 digits
  - `cardPin`: 14 digits
  - `amount`: `"10.00"`

**Validation Points**:
- ✅ Customer hint: "Only PIN required for redemption"
- ✅ No alterations to response data
- ✅ Email/SMS sent

**Test Command**:
```bash
php artisan woohoo:test-order --sku=CLAIMCODE --amount=10 --scenario=claimcode
```

---

#### 1.6 Success - UBF 16 Digit Card Number & Activation URL
**API**: Order API  
**Request**:
- SKU: `UBERLOW`
- Amount: `10`
- `sync_only`: `true` or `false`

**Expected Response**:
- `success`: `true`
- `status`: `"Complete"`
- `cardDetails`:
  - `card_number`: Present
  - `expiry_date`: Present
  - `pin`: Present
  - `activation_url`: **Present** (critical)
  - `available_balance`: Present
  - `currency`: Present

**Validation Points**:
- ✅ Customer hint: "Claim action required - access URL in web browser"
- ✅ Activation URL displayed prominently
- ✅ No alterations to response data
- ✅ Email/SMS sent with activation URL

**Test Command**:
```bash
php artisan woohoo:test-order --sku=UBERLOW --amount=10 --scenario=activation-url
```

---

### 2. Failure Scenarios

#### 2.1 Min/Max Price Validation - Denomination Not Available
**API**: Order API  
**Request**:
- SKU: `VOUCHERCODE`
- Amount: `90` (below min) OR `100000` (above max) OR `100` (not in available denominations)

**Expected Response**:
- `code`: `5320`
- `message`: `"100 Denomination is not available for product SKU - VOUCHERCODE. Please choose different denomination."`

**Validation Points**:
- ✅ Error message displayed to user
- ✅ Order status: `FAILED`
- ✅ OrderSummary status: `FAILED`
- ✅ No email/SMS sent
- ✅ User can retry with correct denomination

**Test Command**:
```bash
php artisan woohoo:test-order --sku=VOUCHERCODE --amount=90 --scenario=validation-error
php artisan woohoo:test-order --sku=VOUCHERCODE --amount=100000 --scenario=validation-error
php artisan woohoo:test-order --sku=VOUCHERCODE --amount=100 --scenario=validation-error
```

---

#### 2.2 Woohoo Product Disabled
**API**: Order API  
**Request**:
- SKU: `DISABLEDSTS`
- Amount: `100`
- `sync_only`: `true` or `false`

**Expected Response**:
- `code`: `5320`
- `message`: `"The SKU DISABLEDSTS which you are looking for is not available."`

**Validation Points**:
- ✅ Error message displayed
- ✅ Order status: `FAILED`
- ✅ Admin notified (log/alert)
- ✅ User cannot proceed

**Test Command**:
```bash
php artisan woohoo:test-order --sku=DISABLEDSTS --amount=100 --scenario=disabled-product
```

---

#### 2.3 Processing Status (sync_only=true only)
**API**: Order API  
**Request**:
- SKU: `PROCESSINGSTS`
- Amount: `100`
- `sync_only`: `true`

**Expected Response**:
- `status`: `"PROCESSING"`
- `orderId`: Present
- `refno`: Present
- `cards` array: **Empty**

**Validation Points**:
- ✅ Order status in DB: `PENDING`
- ✅ Admin notified to contact POC/PM
- ✅ User sees "Order Pending" (yellow)
- ✅ No cards available yet

**Test Command**:
```bash
php artisan woohoo:test-order --sku=PROCESSINGSTS --amount=100 --sync-only=true --scenario=processing-status
```

---

#### 2.4 Duplicate Reference Number
**API**: Order API  
**Request**:
- Product ID: `324`
- Amount: Any
- `sync_only`: `true` or `false`
- **Ref no already stored in DB**

**Expected Response**:
- `success`: `false`
- `error_code`: `5002`
- `message`: `"Duplicate reference number provided."`

**Validation Points**:
- ✅ Error caught before API call (if possible)
- ✅ If API returns error, order status: `FAILED`
- ✅ System generates new unique refno automatically
- ✅ User can retry

**Test Command**:
```bash
php artisan woohoo:test-order --sku=CNPIN --amount=1 --refno=Amz20260112002654 --scenario=duplicate-refno
```

---

#### 2.5 Timeout with Order Success (sync_only=true)
**API**: Order API  
**Request**:
- SKU: `testsuccess001`
- Amount: Any
- `sync_only`: `true`

**Expected Response**:
- a) No response from Order API (timeout)
- b) After 40 seconds: Call Order Status API with `refno`
- c) Status API returns: `status: "COMPLETE"` or `"Success"`
- d) Call Activated Cards API to fetch card details

**Validation Points**:
- ✅ Timeout handled gracefully
- ✅ Status API called automatically after timeout
- ✅ Cards fetched via Activated Cards API
- ✅ Order status: `COMPLETE`
- ✅ Email/SMS sent

**Test Command**:
```bash
php artisan woohoo:test-order --sku=testsuccess001 --amount=1 --sync-only=true --scenario=timeout-success
```

---

#### 2.6 Timeout with Order Failure (sync_only=true)
**API**: Order API  
**Request**:
- SKU: `APIFAILTESTING`
- Amount: Any
- `sync_only`: `true`

**Expected Response**:
- a) No response from Order API (timeout)
- b) After 40 seconds: Call Order Status API with `refno`
- c) Status API returns: Status other than `"Complete"` (e.g., `"Processing"`, `"Pending"`, `"Activate Error"`, `"Closed"`)

**Validation Points**:
- ✅ Timeout handled gracefully
- ✅ Status API called automatically
- ✅ Order status: `FAILED` or `PENDING` (based on status)
- ✅ User can retry with fresh order

**Test Command**:
```bash
php artisan woohoo:test-order --sku=APIFAILTESTING --amount=1 --sync-only=true --scenario=timeout-failure
```

---

#### 2.7 Two Different Product SKUs in One Order
**API**: Order API  
**Request**:
- SKU: `CNPIN`, `VOUCHERCODE` (multiple SKUs)
- Amount: `100`
- `delivery_mode`: `API`

**Expected Response**:
- `code`: `400`
- `message`: `"Decoding error."`

**Validation Points**:
- ✅ Frontend prevents multiple SKUs in one order
- ✅ Backend validates single SKU per order
- ✅ Error message displayed
- ✅ Order not created

**Test Command**:
```bash
php artisan woohoo:test-order --sku=CNPIN,VOUCHERCODE --amount=100 --scenario=multiple-skus
```

---

### 3. Catalog API Scenarios

#### 3.1 Gift Card Category - Fetch Category ID
**API**: Catalog API  
**Request**: `GET /baseurl/rest/v3/catalog/categories`

**Expected Response**:
- JSON object with:
  - `id`: Category ID
  - `name`: Category name
  - `url`: Category URL
  - `description`: Category description
  - `images`: Category images
  - `subCategories`: Array (e.g., "Reliance Retail")

**Validation Points**:
- ✅ API called only during setup
- ✅ Response stored in local DB
- ✅ Can refresh/update monthly
- ✅ Categories displayed in UI

**Test Command**:
```bash
php artisan woohoo:test-catalog --type=categories
```

---

#### 3.2 Gift Card Product - With Valid Category ID
**API**: Product List API  
**Request**: `GET /baseurl/rest/v3/catalog/categories/{id}/products?offset={offset}&limit={limit}`

**Expected Response**:
- Category details
- `products` array with:
  - `sku`: `"VOUCHERCODE"`, `"UBERLOW"`, etc.
  - `name`: Product name
  - `description`: Product description
  - `metaInformation`: Includes `price` range and `denominations`
  - `images`: Product images
  - `tnc`: Terms and conditions

**Validation Points**:
- ✅ Products fetched for category
- ✅ Products stored in DB
- ✅ UI displays products correctly
- ✅ Denominations synced with API

**Test Command**:
```bash
php artisan woohoo:test-catalog --type=products --category-id=<id>
```

---

#### 3.3 Gift Card Product - With Valid Product SKU
**API**: Product API  
**Request**: `GET /baseurl/rest/v3/catalog/products/{sku}`

**Expected Response**:
- Detailed product information:
  - `sku`: `"CNPIN"`
  - `sku_limits`: Limits
  - `discounts`: Discount information
  - `name`: Product name
  - `description`: Product description
  - `metaInformation`: Price range and denominations
  - `images`: Product images
  - `tnc`: Terms and conditions

**Validation Points**:
- ✅ Product details fetched
- ✅ Product stored/updated in DB
- ✅ UI displays correct product info
- ✅ Denominations validated against API

**Test Command**:
```bash
php artisan woohoo:test-catalog --type=product --sku=CNPIN
```

---

### 4. General System Requirements

#### 4.1 Reference Number - Prefix with Org Code
**Validation Points**:
- ✅ Reference number prefixed with organization short code (e.g., "Amz")
- ✅ Reference number is unique
- ✅ No duplicate reference number errors

**Test**: Already implemented in `ProductPageController.php`

---

#### 4.2 Payment Method - 'svc'
**Validation Points**:
- ✅ Payment method always passed as `'svc'`
- ✅ No other payment methods used

**Test**: Check `WoohooOrderController.php` line 133

---

#### 4.3 Valid Billing/Shipping Details
**Validation Points**:
- ✅ Valid shipping/billing details passed
- ✅ Corporate details used if customer details absent (only if `delivery_mode=API`)
- ✅ No removal of persons from address

**Test**: Check `WoohooOrderController.php` address/billing sections

---

#### 4.4 Timeout Process
**Validation Points**:
- ✅ UAT/Testing: Timeout = 10 seconds
- ✅ Production: Timeout = 40 seconds minimum
- ✅ Configurable via environment variables

**Test**: Check `config/unlimit.php` and `.env`

---

#### 4.5 Product Denominations
**Validation Points**:
- ✅ UI denominations synced with Product API response
- ✅ If `price.type = "Range"`: Validate within min/max range
- ✅ If `price.type = "Slab"`: Only show custom denominations (no open range)

**Test**: Check `ProductPageController.php` denomination validation

---

#### 4.6 Card Detail Storage
**Validation Points**:
- ✅ Card Number encrypted before storage
- ✅ Card PIN encrypted before storage
- ✅ Encryption key stored securely
- ✅ Decryption works correctly

**Test**: Check `ViewCardDetailsController.php` and `WoohooOrderController.php` card storage

---

## Test Execution Plan

### Phase 1: Unit Tests (Automated)
1. Create test commands for each scenario
2. Run automated tests in UAT environment
3. Validate responses and database updates

### Phase 2: Integration Tests (Manual + Automated)
1. Test end-to-end order flow
2. Test email/SMS delivery
3. Test UI display and interactions
4. Test error handling and user messages

### Phase 3: Load Tests
1. Test multiple concurrent orders
2. Test timeout scenarios
3. Test retry mechanisms

### Phase 4: Production Readiness
1. Verify all test scenarios pass
2. Review logs for any errors
3. Validate encryption/decryption
4. Confirm email/SMS delivery
5. Test with real payment gateway

---

## Test Commands to Create

1. `php artisan woohoo:test-order` - Test order creation scenarios
2. `php artisan woohoo:test-fetch-cards` - Test Activated Cards API
3. `php artisan woohoo:test-catalog` - Test Catalog API scenarios
4. `php artisan woohoo:test-status` - Test Order Status API
5. `php artisan woohoo:test-all` - Run all test scenarios

---

## Pre-Production Checklist

- [ ] All success scenarios tested and passing
- [ ] All failure scenarios tested and handled correctly
- [ ] Timeout scenarios tested with retry logic
- [ ] Email/SMS delivery verified
- [ ] Card encryption/decryption working
- [ ] Order status consistency verified (QsOrder + OrderSummary)
- [ ] UI displays correct status colors and clickability
- [ ] Reference number uniqueness verified
- [ ] Payment method 'svc' confirmed
- [ ] Billing/shipping details validated
- [ ] Product denominations synced with API
- [ ] Catalog API integration tested
- [ ] Load testing completed
- [ ] Error logging and monitoring in place
- [ ] Production timeout set to 40 seconds
- [ ] Retry configuration set (MAX_RETRIES=3, RETRY_INTERVAL=40)

---

## Notes

- **Timeout**: Minimum 40 seconds for production (10 seconds for UAT)
- **Retry Logic**: After timeout, call Status API, then Activated Cards API if status is COMPLETE
- **Card Storage**: Always encrypt cardNumber and cardPin before storing
- **Reference Number**: Must be unique, prefixed with org code
- **Customer Hints**: Display appropriate hints based on card type (PIN required, Voucher code only, Activation URL, etc.)
