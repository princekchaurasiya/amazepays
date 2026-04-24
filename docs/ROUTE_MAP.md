# AmazePays -- Route Map

## Route Files

All route files are registered in `bootstrap/app.php`.

### `routes/web.php` -- Public Storefront

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/` | `home` | `HomePageController@homePage` |
| GET | `/product/{slug}` | `get-product-by-slug` | `ProductSlugController@getProductBySlug` |
| GET | `/search` | `search` | `SearchController@search` |
| GET | `/category/{slug}` | `categories.show` | `StorefrontCategoryController@show` |
| GET | `/brand/{slug}` | `brands.show` | `StorefrontBrandController@show` |
| GET | `/business` | `business.landing` | `StorefrontBusinessController@show` |
| GET | `/about` | `about` | static view |
| GET | `/faq` | `faq` | static view |
| GET | `/profile` | `profile` | static view (auth) |
| GET | `/my-order` | `my-order` | `MyOrderController@displayOrder` |

### `routes/auth.php` -- Authentication

| Method | URI | Name | Controller |
|---|---|---|---|
| POST | `/user-login` | `user-login` | `UserPanelController@userLogin` |
| POST | `/user-registration` | `user-registration` | `UserPanelController@userRegistration` |
| POST | `/send-sms` | `send-sms` | `SmsController@loginWithOtp` |
| POST | `/verify-otp` | `verify-otp` | `OtpVerificationController@loginVerifyOtp` |
| GET | `/verify-email` | `verify.email` | `AuthController@showVerifyForm` |

### `routes/checkout.php` -- Checkout & Orders

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/checkout/{slug}` | `checkoutPage` | `StorefrontProductController@showCheckout` |
| POST | `/checkout/{slug}` | `checkoutPage.post` | `CheckoutSessionController@submitCheckout` |
| POST | `/checkout/session/billing` | `checkout.session.billing.update` | `CheckoutSessionController@updateBillingDetails` |
| POST | `/woohoo/create-order` | `woohoo.createOrder` | `WoohooOrderController@createOrder` |
| POST | `/unlimit/checkout` | `unlimit.checkout` | `UnlimitController@checkout` |
| POST | `/vd/checkout/session` | `vd.checkout.session.update` | `Voucher\\ValueDesignCheckoutController@updateSession` |
| POST | `/vd-checkout` | `vdcheckoutPage` | `Voucher\\ValueDesignCheckoutController@submit` |

### `routes/payments.php` -- Payment Processing

| Method | URI | Name | Controller |
|---|---|---|---|
| GET/POST | `/unlimit/return` | `unlimit.return` | `WoohooProcessingController@handleReturn` |
| POST | `/payment/upi` | `payment.upi` | `Payment\\PaymentSessionController@upi` |

### `routes/webhooks.php` -- Inbound Webhooks

| Method | URI | Name | Controller |
|---|---|---|---|
| POST | `/unlimit/webhook` | `unlimit.webhook` | `UnlimitPaymentController@webhook` |
| POST | `/upi/webhook` | `upi.webhook` | `UPIPaymentController@webhook` |

### `routes/api.php` -- Payment webhooks (v1, signature / CSRF exempt group)

These live under prefix `/api/v1/webhooks` (see `routes/api.php`). Handlers whitelist callback fields before invoking `PaymentService`.

| Method | URI | Name | Controller |
|---|---|---|---|
| POST | `/api/v1/webhooks/ccavenue` | `api.v1.webhooks.ccavenue` | `Payment\CCAvenueCallbackController@handle` |
| POST | `/api/v1/webhooks/unlimit` | `api.v1.webhooks.unlimit` | `Payment\UnlimitCallbackController@handle` |
| POST | `/api/v1/webhooks/razorpay` | `api.v1.webhooks.razorpay` | `Payment\RazorpayCallbackController@handle` |
| POST | `/api/v1/webhooks/woohoo` | `api.v1.webhooks.woohoo` | `Voucher\WoohooCallbackController@handle` |

### `routes/admin.php` -- Admin Panel (Inertia)

All routes prefixed with `/panel` and require `auth` + `two.factor` middleware.

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/panel` | `panel.dashboard` | `DashboardController@index` |
| GET | `/panel/products` | `panel.products.index` | `ProductController@index` |
| GET | `/panel/orders` | `admin.orders.index` | `OrderController@index` |
| GET | `/panel/users` | `admin.users.index` | `UserController@index` |
| GET | `/panel/tenants` | `admin.tenants.index` | `TenantController@index` |
| GET | `/panel/wallets` | `admin.wallets.index` | `WalletController@index` |
| GET | `/panel/offers` | `admin.offers.index` | `OfferController@index` |
| GET | `/panel/audit-logs` | `admin.audit-logs.index` | `AuditLogController@index` |
| GET | `/panel/security` | `admin.security.index` | `SecurityDashboardController@index` |
| GET | `/panel/settings` | `admin.settings.index` | `SettingsController@index` |
| GET | `/panel/vouchagram` | `panel.vouchagram.index` | `VouchagramController@index` |
| POST | `/panel/vouchagram/*` | `panel.vouchagram.*` | `VouchagramController` (API tools, sync) |

### `routes/api.php` -- Mobile App & B2B API

All routes prefixed with `/api/v1`.

| Method | URI | Name | Controller |
|---|---|---|---|
| POST | `/api/v1/auth/otp/send` | `api.v1.auth.otp.send` | `AuthController@sendOtp` |
| POST | `/api/v1/auth/otp/verify` | `api.v1.auth.otp.verify` | `AuthController@verifyOtp` |
| POST | `/api/v1/auth/complete-profile` | `api.v1.auth.complete.profile` | `AuthController@completeProfile` |
| POST | `/api/v1/auth/2fa/verify` | `api.v1.auth.2fa.verify` | `AuthController@verifyTwoFactor` |
| POST | `/api/v1/auth/logout` | `api.v1.auth.logout` | `AuthController@logout` |
| GET | `/api/v1/auth/me` | `api.v1.auth.me` | `AuthController@me` |
| GET | `/api/v1/catalog` | `api.v1.catalog.index` | `CatalogController@index` |
| POST | `/api/v1/orders` | `api.v1.orders.store` | `OrderController@placeOrder` |
| GET | `/api/v1/wallet/balance` | `api.v1.wallet.balance` | `WalletController@balance` |
