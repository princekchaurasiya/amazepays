# AmazePays Security Architecture

> **Version:** 2.1  
> **Last Updated:** April 2026

---

## Table of Contents

1. [Security Overview](#1-security-overview)
2. [Authentication](#2-authentication)
3. [Authorization (RBAC)](#3-authorization-rbac)
4. [Two-Factor Authentication](#4-two-factor-authentication)
5. [Data Encryption](#5-data-encryption)
6. [API Security](#6-api-security)
7. [Webhook Security](#7-webhook-security)
8. [Network Security & VPN/Proxy Detection](#8-network-security--vpnproxy-detection)
9. [Voucher Code Protection](#9-voucher-code-protection)
10. [Wallet Fraud Detection](#10-wallet-fraud-detection)
11. [Threat Detection & Auto-Blocking](#11-threat-detection--auto-blocking)
12. [Security Event Logging](#12-security-event-logging)
13. [Audit Logging](#13-audit-logging)
14. [Vulnerability Remediation](#14-vulnerability-remediation)
15. [Compliance Considerations](#15-compliance-considerations)
16. [Incident Response](#16-incident-response)

---

## 1. Security Overview

### Security Principles

1. **Defense in depth** -- Multiple layers of security at every level
2. **Least privilege** -- Users and services get minimum required access
3. **Encrypt everything sensitive** -- At rest and in transit
4. **Audit everything** -- Complete trail of who did what and when
5. **Fail secure** -- Errors default to denying access, not granting it

### Current Vulnerabilities (to fix immediately)

| Issue | Risk | File | Fix |
|-------|------|------|-----|
| `.env.example` contains real credentials | HIGH | `.env.example` | Replace with placeholders |
| Git merge conflict in job file | MEDIUM | `app/Jobs/ProcessWoohooOrder.php` | Resolve conflict markers |
| `is.admin` middleware alias mismatch | MEDIUM | `app/Http/Kernel.php` | Align with `admin` alias |
| Missing OTP methods in API controller | LOW | `app/Http/Controllers/APIs/AuthenticationController.php` | Implement methods |
| Voyager exposes raw DB access | HIGH | `config/voyager.php` | Replace with custom admin |
| VPN check fails open on API error | MEDIUM | `app/Http/Middleware/BlockVPNUsers.php` | Fail closed or log |

---

## 2. Authentication

### Web Authentication (Admin + B2B Portal)

- **Method:** Laravel session-based auth with Inertia.js
- **Session driver:** Redis (not file -- enables horizontal scaling)
- **Session lifetime:** 120 minutes (configurable per role)
- **Remember me:** 30 days with encrypted cookie
- **CSRF protection:** Enabled on all web routes (Inertia handles this automatically)
- **Password hashing:** bcrypt with cost factor 12

### API Authentication (Mobile App)

- **Method:** Laravel Sanctum token-based auth
- **Token type:** Personal access tokens (stateless)
- **Token storage (client):** react-native-keychain (hardware-backed secure storage)
- **Token expiry:** 30 days (configurable)
- **Token revocation:** On logout, password change, or admin action

### Reseller API Authentication

- **Method:** API key + HMAC-SHA256 request signing
- **API key:** 64-character random string, stored hashed in DB
- **HMAC secret:** 128-character random string, stored encrypted in DB
- **Timestamp validation:** Request must be within 5 minutes of server time
- **Replay prevention:** Timestamp + nonce prevents replay attacks

```
Signature = HMAC-SHA256(request_body + timestamp, api_secret)

Headers:
  X-Api-Key: ak_live_<key>
  X-Timestamp: <unix_timestamp>
  X-Signature: <hex_signature>
```

### OTP Authentication (Mobile)

- OTP sent via SMS to registered mobile number
- OTP length: 6 digits
- OTP validity: 5 minutes
- Max attempts: 5 per OTP (then OTP expires)
- Rate limit: 3 OTPs per phone number per 10 minutes
- OTPs hashed in DB (not stored in plain text)

---

## 3. Authorization (RBAC)

### Implementation

Using **Spatie laravel-permission** package (replacing Voyager's built-in roles):

```php
// Policy-based authorization on every controller action
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        // Super admin / admin can view all
        if ($user->hasRole(['super-admin', 'admin', 'finance'])) return true;

        // B2B client can only view their tenant's orders
        if ($user->hasRole(['b2b-client', 'b2b-operator'])) {
            return $order->tenant_id === $user->currentTenantId();
        }

        // B2C user can only view their own orders
        return $order->user_id === $user->id;
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }
}
```

### Route-Level Protection

```php
// Every admin route has explicit permission middleware
Route::middleware(['auth', 'role:super-admin|admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/tenants', [TenantController::class, 'index'])
            ->middleware('permission:tenants.view');
        Route::post('/tenants', [TenantController::class, 'store'])
            ->middleware('permission:tenants.create');
    });
```

---

## 4. Two-Factor Authentication

### Implementation (TOTP)

- **Algorithm:** TOTP (RFC 6238) compatible with Google Authenticator, Authy, etc.
- **Secret storage:** Encrypted in `two_factor_secrets` table
- **Recovery codes:** 8 single-use codes, encrypted in DB
- **Required for:** Super Admin, Admin, Finance, B2B Client (owner)
- **Optional for:** B2B Operator, B2C User

### 2FA Flow

```
User enables 2FA:
1. POST /admin/2fa/enable
   → Generate TOTP secret
   → Return QR code (otpauth:// URI)
2. User scans QR code with authenticator app
3. POST /admin/2fa/confirm
   Body: { code: "123456" }
   → Verify code matches secret
   → Mark 2FA as confirmed
   → Generate and return recovery codes (show once)

Login with 2FA:
1. User enters email + password → valid
2. System checks: user.two_factor_enabled?
   → Yes: redirect to 2FA challenge page
3. User enters 6-digit code from authenticator
4. POST /admin/2fa/verify
   → Verify against TOTP secret
   → If valid: complete login
   → If invalid: increment attempts (max 5, then lockout)

Recovery:
1. User clicks "Use recovery code"
2. Enter recovery code
3. POST /admin/2fa/recover
   → Verify against stored recovery codes
   → Mark code as used
   → Complete login
```

### Recovery Codes

```php
// Generate 8 recovery codes
$codes = Collection::times(8, fn() => Str::random(10))->toArray();

// Store encrypted
$user->twoFactorSecret->update([
    'recovery_codes' => Crypt::encryptString(json_encode($codes)),
]);
```

---

## 5. Data Encryption

### Encryption at Rest

| Data | Method | Key |
|------|--------|-----|
| Payment gateway credentials | `Crypt::encryptString()` (AES-256-CBC) | `APP_KEY` |
| Voucher provider credentials | `Crypt::encryptString()` | `APP_KEY` |
| 2FA secrets | `Crypt::encryptString()` | `APP_KEY` |
| Recovery codes | `Crypt::encryptString()` | `APP_KEY` |
| API key secrets | `Crypt::encryptString()` | `APP_KEY` |
| Voucher card numbers/PINs | `encrypted` Eloquent cast | `APP_KEY` |
| HMAC secrets (reseller) | `Crypt::encryptString()` | `APP_KEY` |

### Eloquent Encrypted Casts

```php
// Order model -- cards field contains sensitive voucher data
class Order extends Model
{
    protected $casts = [
        'cards' => 'encrypted:array',
    ];
}
```

### Encryption in Transit

- **TLS 1.2+** enforced on all endpoints
- **HSTS** header with 1-year max-age
- **ForceHttps middleware** redirects payment-related URLs
- **API endpoints** reject non-HTTPS requests in production

### APP_KEY Management

- `APP_KEY` is the master encryption key for all Laravel Crypt operations
- **Never commit** `APP_KEY` to version control
- **Key rotation** requires re-encrypting all encrypted fields (migration command provided)
- **Backup** `APP_KEY` in a secure vault (e.g., AWS Secrets Manager)

```php
// Command to re-encrypt all data after key rotation
// php artisan app:rotate-encryption-key <old_key>

class RotateEncryptionKey extends Command
{
    // Decrypt with old key, encrypt with new key for:
    // - tenant_payment_gateways.credentials
    // - tenant_voucher_providers.credentials
    // - two_factor_secrets.secret, recovery_codes
    // - api_keys.secret
    // - orders.cards (where encrypted)
}
```

---

## 6. API Security

### Rate Limiting

```php
// app/Providers/RouteServiceProvider.php

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('reseller', function (Request $request) {
    $apiKey = $request->header('X-Api-Key');
    $keyRecord = ApiKey::where('key', hash('sha256', $apiKey))->first();
    $limit = $keyRecord?->rate_limit ?? 120;
    return Limit::perMinute($limit)->by($apiKey);
});

RateLimiter::for('payment-callbacks', function (Request $request) {
    return Limit::perMinute(30)->by($request->ip());
});
```

### Input Validation

- All API inputs validated via Form Request classes (or `$request->validate()` / `Validator::make()` on **whitelisted** keys only).
- **Do not** pass `$request->all()` into validators, sessions, `fill()`, or logging—clients can inject extra keys (mass-assignment and log leaks). Use `$request->validated()`, the array returned by `validate()`, or `$validator->validated()` after rules pass; for ad-hoc validation use `$request->only([...])` with exactly the rule keys. See [CODE_STANDARDS.md](CODE_STANDARDS.md#http-request-input--logging).
- Strict type checking on all parameters
- Maximum payload size: 1MB
- JSON depth limit: 5 levels

### Application logging (sensitive flows)

- **Auth, OTP/SMS, password reset, profile, checkout, and payment return/webhook handlers** should not write `Log::info`/`debug` payloads that contain mobile numbers, emails, OTPs, passwords, full gateway responses, or constructed URLs that embed gateway/SMS secrets.
- Prefer **no** unstructured application logs in those paths; use dedicated `security_event_logs` / `audit_logs` (with redacted metadata) when compliance requires a record.

### IP Whitelisting

```php
// Per-tenant IP whitelist for API access
class VerifyIpWhitelist
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = app('currentTenant');
        if (!$tenant) return $next($request);

        $whitelist = IpWhitelist::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->pluck('ip_address')
            ->toArray();

        // If no whitelist configured, allow all
        if (empty($whitelist)) return $next($request);

        if (!in_array($request->ip(), $whitelist)) {
            Log::warning('API request from non-whitelisted IP', [
                'tenant_id' => $tenant->id,
                'ip' => $request->ip(),
            ]);
            abort(403, 'IP address not whitelisted');
        }

        return $next($request);
    }
}
```

### CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => [env('APP_URL')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Content-Type', 'Authorization', 'Accept',
        'X-Api-Key', 'X-Timestamp', 'X-Signature',
        'Idempotency-Key',
    ],
    'exposed_headers' => [
        'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset',
    ],
    'max_age' => 86400,
    'supports_credentials' => true,
];
```

---

## 7. Webhook Security

### Inbound Webhooks (from payment gateways)

| Gateway | Verification Method | Implementation |
|---------|-------------------|----------------|
| Unlimit | SHA-512 hash of body + secret | `VerifyUnlimitSignature` middleware (existing) |
| CCAvenue | AES-128-CBC decryption with working_key | Decrypt and validate in controller |
| Razorpay | HMAC-SHA256 of order_id\|payment_id | `VerifyRazorpaySignature` middleware (new) |

### Outbound Webhooks (to resellers)

```php
// When sending order status webhooks to resellers
$payload = json_encode($orderData);
$signature = hash_hmac('sha256', $payload, $apiKeySecret);

Http::withHeaders([
    'Content-Type' => 'application/json',
    'X-Webhook-Signature' => $signature,
    'X-Webhook-Timestamp' => now()->timestamp,
])->post($reseller->webhook_url, $orderData);
```

### Webhook Security Rules

- CSRF exemption only for payment callback routes
- Rate limiting on all webhook endpoints (30/minute per IP)
- Signature verification before any processing
- Idempotent processing (duplicate callbacks ignored)
- Pass only **expected** keys from the HTTP request into `PaymentService::handleGatewayCallback()` (e.g. CCAvenue: `encResp`; Razorpay: webhook `event` + `payload` or redirect signature fields; Unlimit: `payment_data` + `merchant_order`). Do not forward `$request->all()` to domain logic.
- **Do not** log full inbound webhook bodies to application logs; persist gateway outcomes via payment/order records when audit is needed.
- Response always 200 (to prevent gateway retries on business errors)

---

## 8. Network Security & VPN/Proxy Detection

### Security Headers Middleware

```php
// app/Http/Middleware/SecurityHeaders.php

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self' https://api.amazepays.com"
            );
        }

        return $response;
    }
}
```

### VPN / Proxy / Tor Detection

The existing `BlockVPNUsers` middleware calls IPHub API. This must be hardened with a **multi-layer approach**:

#### Detection Architecture

```
┌─────────────┐     ┌───────────────────────────────────────────────┐
│ User Request │────▶│ VPN/Proxy Detection Pipeline                  │
└─────────────┘     │                                               │
                    │  Layer 1: IP Reputation (IPHub / ip-api.com)  │
                    │  Layer 2: DNS Leak Check (reverse DNS)        │
                    │  Layer 3: GeoIP Anomaly (MaxMind GeoLite2)    │
                    │  Layer 4: Behavioral Analysis                 │
                    │  Layer 5: Device Fingerprint (mobile app)     │
                    └──────────────────┬────────────────────────────┘
                                       │
                        ┌──────────────┴──────────────┐
                        ▼                             ▼
                 ┌──────────────┐            ┌───────────────┐
                 │ ALLOW        │            │ FLAG / BLOCK   │
                 │ (clean IP)   │            │ (VPN detected) │
                 └──────────────┘            └───────────────┘
```

#### Enhanced VPN Detection Middleware

```php
// app/Http/Middleware/DetectVpnProxy.php

class DetectVpnProxy
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $cacheKey = "vpn_check:{$ip}";

        $result = Cache::remember($cacheKey, now()->addHours(24), function () use ($ip) {
            return $this->checkIp($ip);
        });

        if ($result['is_vpn'] || $result['is_proxy'] || $result['is_tor']) {
            SecurityEventLog::create([
                'event_type'    => 'vpn_detected',
                'severity'      => 'high',
                'ip_address'    => $ip,
                'user_id'       => auth()->id(),
                'metadata'      => [
                    'vpn_provider'  => $result['provider'] ?? 'unknown',
                    'country'       => $result['country_code'],
                    'detection_method' => $result['detection_method'],
                    'url'           => $request->fullUrl(),
                    'user_agent'    => $request->userAgent(),
                ],
            ]);

            $action = config('security.vpn_action', 'block');

            if ($action === 'block') {
                abort(403, 'Access denied. VPN/proxy connections are not permitted.');
            }

            if ($action === 'flag') {
                $request->attributes->set('vpn_flagged', true);
                $request->attributes->set('vpn_details', $result);
            }
        }

        return $next($request);
    }

    private function checkIp(string $ip): array
    {
        $checks = [];

        // Layer 1: IPHub API (primary)
        $checks['iphub'] = $this->checkIpHub($ip);

        // Layer 2: ip-api.com (fallback -- free tier: 45 req/min)
        if (!$checks['iphub']['success']) {
            $checks['ipapi'] = $this->checkIpApi($ip);
        }

        // Layer 3: Reverse DNS check (data center hosting detection)
        $checks['rdns'] = $this->checkReverseDns($ip);

        // Layer 4: GeoIP via MaxMind
        $checks['geoip'] = $this->checkGeoIp($ip);

        return $this->aggregateResults($checks);
    }

    private function checkIpHub(string $ip): array
    {
        try {
            $response = Http::timeout(3)
                ->withHeaders(['X-Key' => config('services.iphub.api_key')])
                ->get("https://v2.api.iphub.info/ip/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success'    => true,
                    'is_vpn'     => $data['block'] === 1,
                    'is_hosting' => $data['block'] === 2,
                    'provider'   => $data['isp'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'detection_method' => 'iphub',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('IPHub API failed', ['error' => $e->getMessage()]);
        }

        return ['success' => false];
    }

    private function checkReverseDns(string $ip): array
    {
        $hostname = gethostbyaddr($ip);
        $datacenterKeywords = [
            'digitalocean', 'amazonaws', 'linode', 'vultr', 'hetzner',
            'ovh', 'cloudflare', 'azure', 'googlecloud', 'oracle',
            'nordvpn', 'expressvpn', 'surfshark', 'cyberghost',
        ];

        $isDatacenter = false;
        foreach ($datacenterKeywords as $keyword) {
            if (stripos($hostname, $keyword) !== false) {
                $isDatacenter = true;
                break;
            }
        }

        return [
            'is_datacenter' => $isDatacenter,
            'hostname'      => $hostname,
            'detection_method' => 'rdns',
        ];
    }

    private function aggregateResults(array $checks): array
    {
        $isVpn = false;
        $detectionMethod = 'none';

        foreach ($checks as $source => $check) {
            if (isset($check['is_vpn']) && $check['is_vpn']) {
                $isVpn = true;
                $detectionMethod = $check['detection_method'] ?? $source;
                break;
            }
            if (isset($check['is_datacenter']) && $check['is_datacenter']) {
                $isVpn = true;
                $detectionMethod = 'rdns_datacenter';
                break;
            }
        }

        return [
            'is_vpn'    => $isVpn,
            'is_proxy'  => $isVpn,
            'is_tor'    => false,
            'provider'  => $checks['iphub']['provider'] ?? null,
            'country_code' => $checks['iphub']['country_code']
                ?? $checks['geoip']['country_code'] ?? null,
            'detection_method' => $detectionMethod,
        ];
    }
}
```

#### Configuration

```php
// config/security.php

return [
    'vpn_detection' => [
        'enabled'        => env('VPN_DETECTION_ENABLED', true),
        'action'         => env('VPN_ACTION', 'block'),    // block | flag | log_only
        'cache_ttl'      => 86400,                         // 24 hours
        'bypass_ips'     => explode(',', env('VPN_BYPASS_IPS', '')),
        'fail_behavior'  => 'closed',                      // closed = block if API down
        'exempt_routes'  => ['api/health', 'api/status'],
    ],

    'geo_restrictions' => [
        'enabled'          => env('GEO_RESTRICT_ENABLED', false),
        'allowed_countries' => ['IN'],
        'action'           => 'block',
    ],
];
```

#### VPN Detection for Different Contexts

| Context | Action | Reason |
|---------|--------|--------|
| B2C Purchase | Block + log | Prevent voucher fraud |
| Wallet Load/Transfer | Block + log + alert | Financial fraud |
| Voucher Code Redemption | Block + log + alert | Code theft prevention |
| B2B API Access | Log only (IP whitelisting handles this) | B2B clients may use corporate VPNs |
| Admin Panel Login | Flag + require 2FA + log | Admins may need VPN for remote access |
| Catalog Browsing | Log only | Low risk, don't block customers |

### DDoS Protection

- Cloudflare or similar WAF in front of application
- Laravel rate limiting as second layer
- Redis-backed rate limiting for sub-second response
- Auto-block IPs with > 1000 requests/minute

---

## 9. Voucher Code Protection

Voucher codes/PINs are the primary target for attackers. Multi-layer protection:

### Voucher Data Security

```
┌────────────────────────────────────────────────────────────┐
│                  VOUCHER CODE LIFECYCLE                     │
│                                                            │
│  Provider API ──▶ Encrypted in transit (TLS)               │
│        │                                                   │
│        ▼                                                   │
│  Application ──▶ Decrypt provider response                 │
│        │         (AES-256-GCM for Athena, etc.)            │
│        │                                                   │
│        ▼                                                   │
│  Database ──▶ Re-encrypt with APP_KEY                      │
│               (orders.cards column, encrypted:array cast)   │
│        │                                                   │
│        ▼                                                   │
│  User Access ──▶ Decrypt only for display                  │
│                  Rate-limited, logged, one-time view        │
└────────────────────────────────────────────────────────────┘
```

### Voucher Access Controls

```php
// app/Http/Controllers/VoucherCodeController.php

class VoucherCodeController extends Controller
{
    public function show(Order $order)
    {
        $this->authorize('viewVoucherCode', $order);

        // Check VPN status
        if (request()->attributes->get('vpn_flagged')) {
            SecurityEventLog::create([
                'event_type' => 'voucher_access_vpn',
                'severity'   => 'critical',
                'ip_address' => request()->ip(),
                'user_id'    => auth()->id(),
                'metadata'   => [
                    'order_id'    => $order->id,
                    'vpn_details' => request()->attributes->get('vpn_details'),
                ],
            ]);
            abort(403, 'Cannot view voucher codes over VPN connection.');
        }

        // Rate limit: max 10 code views per hour per user
        $rateLimitKey = 'voucher_view:' . auth()->id();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            SecurityEventLog::create([
                'event_type' => 'voucher_rate_limit_hit',
                'severity'   => 'high',
                'user_id'    => auth()->id(),
                'ip_address' => request()->ip(),
                'metadata'   => ['order_id' => $order->id],
            ]);
            abort(429, 'Too many voucher code views. Try again later.');
        }
        RateLimiter::hit($rateLimitKey, 3600);

        // Device consistency check (mobile app)
        if ($this->isNewDevice($order)) {
            SecurityEventLog::create([
                'event_type' => 'voucher_new_device',
                'severity'   => 'medium',
                'user_id'    => auth()->id(),
                'ip_address' => request()->ip(),
                'metadata'   => [
                    'order_id'     => $order->id,
                    'device_id'    => request()->header('X-Device-Id'),
                    'purchase_device' => $order->device_fingerprint,
                ],
            ]);
        }

        // Log successful access
        SecurityEventLog::create([
            'event_type' => 'voucher_code_accessed',
            'severity'   => 'info',
            'user_id'    => auth()->id(),
            'ip_address' => request()->ip(),
            'metadata'   => [
                'order_id'  => $order->id,
                'view_count' => $order->code_view_count + 1,
            ],
        ]);

        $order->increment('code_view_count');

        return response()->json([
            'cards' => $order->cards,  // decrypted via Eloquent cast
        ]);
    }

    private function isNewDevice(Order $order): bool
    {
        $currentDevice = request()->header('X-Device-Id');
        return $currentDevice && $currentDevice !== $order->device_fingerprint;
    }
}
```

### Voucher Fraud Indicators

| Indicator | Risk Level | Auto-Action |
|-----------|-----------|-------------|
| Viewing codes from VPN/proxy | Critical | Block + alert admin |
| >10 code views in 1 hour | High | Temporary lockout + alert |
| Code viewed from different device than purchase | Medium | Flag + require OTP verification |
| Multiple orders with immediate code view | High | Throttle + review |
| Code view from different country than purchase | Critical | Block + freeze account |
| Bulk code view via API scraping pattern | Critical | Block IP + suspend account |

---

## 10. Wallet Fraud Detection

### Wallet Abuse Scenarios

```
┌────────────────────────────────────────────────────────────┐
│                 WALLET THREAT MODEL                         │
│                                                            │
│  1. Stolen bank credentials → Load wallet → Buy vouchers   │
│  2. Multiple accounts → Abuse referral/promo credits       │
│  3. VPN hopping → Bypass geo restrictions → Load wallet    │
│  4. Account takeover → Drain wallet balance                │
│  5. Chargeback fraud → Load wallet → Spend → Dispute bank  │
│  6. API manipulation → Tamper with amount/transaction       │
└────────────────────────────────────────────────────────────┘
```

### Wallet Transaction Monitoring

```php
// app/Services/WalletFraudDetector.php

class WalletFraudDetector
{
    private array $rules = [];

    public function evaluate(User $user, string $type, float $amount): FraudResult
    {
        $signals = [];

        // Rule 1: VPN detection
        if (request()->attributes->get('vpn_flagged')) {
            $signals[] = new FraudSignal('vpn_detected', 'critical', 90);
        }

        // Rule 2: Velocity check -- too many transactions in short period
        $recentTxns = WalletTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recentTxns > 10) {
            $signals[] = new FraudSignal('high_velocity', 'high', 70);
        }

        // Rule 3: Unusual amount (> 2x user's average transaction)
        $avgAmount = WalletTransaction::where('user_id', $user->id)
            ->where('type', $type)
            ->avg('amount') ?? 0;
        if ($avgAmount > 0 && $amount > ($avgAmount * 3)) {
            $signals[] = new FraudSignal('unusual_amount', 'medium', 50);
        }

        // Rule 4: New device or location
        $knownIps = SecurityEventLog::where('user_id', $user->id)
            ->where('event_type', 'login_success')
            ->distinct('ip_address')
            ->pluck('ip_address');
        if (!$knownIps->contains(request()->ip())) {
            $signals[] = new FraudSignal('new_ip_address', 'medium', 40);
        }

        // Rule 5: Rapid spend after load (load → buy → drain pattern)
        $recentLoad = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();
        if ($recentLoad && $type === 'debit' && $amount > ($user->wallet->balance * 0.8)) {
            $signals[] = new FraudSignal('rapid_drain', 'critical', 85);
        }

        // Rule 6: GeoIP mismatch from registration country
        $currentCountry = $this->getCountryFromIp(request()->ip());
        if ($currentCountry && $currentCountry !== $user->registration_country) {
            $signals[] = new FraudSignal('geo_mismatch', 'high', 60);
        }

        return $this->calculateRisk($signals, $user, $type, $amount);
    }

    private function calculateRisk(array $signals, User $user, string $type, float $amount): FraudResult
    {
        if (empty($signals)) {
            return new FraudResult('allow', 0, []);
        }

        $maxScore = max(array_map(fn($s) => $s->score, $signals));

        $action = match(true) {
            $maxScore >= 85 => 'block',    // Block transaction, alert admin
            $maxScore >= 60 => 'verify',   // Require OTP re-verification
            $maxScore >= 40 => 'flag',     // Allow but flag for review
            default         => 'allow',
        };

        SecurityEventLog::create([
            'event_type' => 'wallet_fraud_check',
            'severity'   => $maxScore >= 85 ? 'critical' : ($maxScore >= 60 ? 'high' : 'medium'),
            'user_id'    => $user->id,
            'ip_address' => request()->ip(),
            'metadata'   => [
                'action'      => $action,
                'risk_score'  => $maxScore,
                'signals'     => array_map(fn($s) => $s->toArray(), $signals),
                'txn_type'    => $type,
                'txn_amount'  => $amount,
            ],
        ]);

        return new FraudResult($action, $maxScore, $signals);
    }
}
```

### Wallet Security Rules Summary

| Rule | Threshold | Action |
|------|-----------|--------|
| VPN/proxy detected | Any wallet operation | Block transaction |
| >10 transactions/hour | Velocity limit | Require re-auth |
| Amount > 3x user average | Unusual pattern | Flag for review |
| New IP + high amount | Combined risk | Require OTP |
| Load then immediate full spend | Drain pattern | Block + alert |
| Different country from registration | Geo anomaly | Block + alert |
| Multiple failed attempts | 5 failures in 10 min | Temporary lockout |

---

## 11. Threat Detection & Auto-Blocking

### IP Threat Intelligence

```php
// app/Services/ThreatDetectionService.php

class ThreatDetectionService
{
    public function analyzeRequest(Request $request): ThreatLevel
    {
        $ip = $request->ip();
        $userId = auth()->id();

        $threatScore = 0;
        $reasons = [];

        // Check 1: Known blocked IP
        if ($this->isBlockedIp($ip)) {
            return ThreatLevel::blocked('IP is in blocklist');
        }

        // Check 2: VPN/proxy (cached result)
        $vpnResult = Cache::get("vpn_check:{$ip}");
        if ($vpnResult && ($vpnResult['is_vpn'] || $vpnResult['is_proxy'])) {
            $threatScore += 50;
            $reasons[] = 'VPN/proxy detected';
        }

        // Check 3: Failed login attempts from this IP
        $failedLogins = SecurityEventLog::where('ip_address', $ip)
            ->where('event_type', 'login_failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($failedLogins > 5) {
            $threatScore += 30;
            $reasons[] = "Failed logins: {$failedLogins}/hr";
        }

        // Check 4: Suspicious user-agent patterns
        $ua = $request->userAgent();
        if ($this->isSuspiciousUserAgent($ua)) {
            $threatScore += 20;
            $reasons[] = 'Suspicious user-agent';
        }

        // Check 5: Request pattern analysis (scraping/enumeration)
        $requestCount = Cache::increment("req_count:{$ip}", 1);
        Cache::put("req_count:{$ip}", $requestCount, 60);
        if ($requestCount > 200) {
            $threatScore += 40;
            $reasons[] = "High request rate: {$requestCount}/min";
        }

        // Check 6: Known attack patterns in request
        if ($this->hasAttackPayload($request)) {
            $threatScore += 80;
            $reasons[] = 'Attack payload detected';
        }

        // Auto-block if threat score is critical
        if ($threatScore >= 80) {
            $this->autoBlockIp($ip, $reasons);
            return ThreatLevel::blocked(implode('; ', $reasons));
        }

        return new ThreatLevel(
            score: $threatScore,
            action: $threatScore >= 50 ? 'challenge' : 'allow',
            reasons: $reasons,
        );
    }

    private function isSuspiciousUserAgent(?string $ua): bool
    {
        if (!$ua || strlen($ua) < 10) return true;

        $suspicious = [
            'python-requests', 'curl/', 'wget/', 'scrapy',
            'phantom', 'selenium', 'headless', 'bot',
            'spider', 'crawl', 'sqlmap', 'nikto', 'nmap',
        ];

        foreach ($suspicious as $pattern) {
            if (stripos($ua, $pattern) !== false) return true;
        }

        return false;
    }

    private function hasAttackPayload(Request $request): bool
    {
        // Intentional: ThreatDetectionService may inspect the full input bag for SQLi/XSS/path patterns.
        // This is the documented exception to avoiding $request->all() elsewhere.
        $input = json_encode($request->all());
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',       // SQL injection
            '/<script[^>]*>/i',                   // XSS
            '/\.\.\//i',                          // Path traversal
            '/\b(eval|exec|system|passthru)\b/i', // RCE
            '/\$\{.*\}/i',                        // Template injection
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) return true;
        }

        return false;
    }

    private function autoBlockIp(string $ip, array $reasons): void
    {
        BlockedIp::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason'     => implode('; ', $reasons),
                'blocked_at' => now(),
                'expires_at' => now()->addHours(24),
                'auto_blocked' => true,
            ],
        );

        SecurityEventLog::create([
            'event_type' => 'ip_auto_blocked',
            'severity'   => 'critical',
            'ip_address' => $ip,
            'metadata'   => ['reasons' => $reasons, 'duration' => '24h'],
        ]);

        // Alert admin via notification
        $admins = User::role('super-admin')->get();
        Notification::send($admins, new IpAutoBlockedNotification($ip, $reasons));
    }
}
```

### Auto-Block Rules

```php
// config/security.php (additional entries)

'threat_detection' => [
    'enabled'                  => true,
    'failed_login_threshold'   => 5,       // per hour per IP
    'request_rate_threshold'   => 200,     // per minute per IP
    'auto_block_duration'      => 86400,   // 24 hours
    'auto_block_score'         => 80,      // threat score threshold
    'permanent_block_after'    => 3,       // auto-blocks before permanent
    'admin_alert_on_block'     => true,
    'challenge_method'         => 'otp',   // otp | captcha
],

'blocked_ip_cleanup' => [
    'expired_cleanup_interval' => 'hourly',
    'archive_after_days'       => 90,
],
```

### Blocked IPs Table

```sql
CREATE TABLE blocked_ips (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address    VARCHAR(45) NOT NULL,
    reason        TEXT NOT NULL,
    blocked_at    TIMESTAMP NOT NULL,
    expires_at    TIMESTAMP NULL,
    auto_blocked  BOOLEAN DEFAULT FALSE,
    blocked_by    BIGINT UNSIGNED NULL,
    block_count   INT UNSIGNED DEFAULT 1,
    permanent     BOOLEAN DEFAULT FALSE,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_blocked_ip (ip_address),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### Threat Detection Middleware

```php
// app/Http/Middleware/ThreatDetection.php

class ThreatDetection
{
    public function handle(Request $request, Closure $next)
    {
        $detector = app(ThreatDetectionService::class);
        $threat = $detector->analyzeRequest($request);

        if ($threat->isBlocked()) {
            abort(403, 'Access denied. Your IP has been blocked due to suspicious activity.');
        }

        if ($threat->requiresChallenge()) {
            $request->attributes->set('threat_challenge', true);
            $request->attributes->set('threat_score', $threat->score);
        }

        return $next($request);
    }
}
```

---

## 12. Security Event Logging

A dedicated `security_event_logs` table captures all security-relevant events separately from the general `audit_logs`, enabling fast threat analysis and alerting.

### Security Event Categories

| Category | Events | Severity |
|----------|--------|----------|
| **Authentication** | login_success, login_failed, otp_sent, otp_verified, otp_failed, 2fa_challenge, 2fa_failed, account_locked | info → critical |
| **VPN/Proxy** | vpn_detected, vpn_bypass_attempt, tor_detected, proxy_detected | high → critical |
| **Voucher Security** | voucher_code_accessed, voucher_access_vpn, voucher_rate_limit_hit, voucher_new_device, voucher_geo_mismatch | info → critical |
| **Wallet Fraud** | wallet_fraud_check, wallet_velocity_exceeded, wallet_unusual_amount, wallet_drain_attempt, wallet_geo_anomaly | medium → critical |
| **API Abuse** | api_rate_limit_hit, api_invalid_signature, api_expired_token, api_brute_force | medium → critical |
| **IP Threats** | ip_auto_blocked, ip_manual_blocked, ip_unblocked, suspicious_request, attack_payload_detected | high → critical |
| **Account** | password_changed, email_changed, phone_changed, role_changed, account_suspended | info → high |
| **Data Access** | bulk_export, sensitive_data_view, admin_impersonation | medium → high |

### Security Event Log Schema

```sql
CREATE TABLE security_event_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type      VARCHAR(50) NOT NULL,
    severity        ENUM('info', 'low', 'medium', 'high', 'critical') NOT NULL,
    ip_address      VARCHAR(45) NULL,
    user_id         BIGINT UNSIGNED NULL,
    tenant_id       BIGINT UNSIGNED NULL,
    user_agent      TEXT NULL,
    request_url     VARCHAR(2048) NULL,
    request_method  VARCHAR(10) NULL,
    country_code    CHAR(2) NULL,
    city            VARCHAR(100) NULL,
    is_vpn          BOOLEAN DEFAULT FALSE,
    device_id       VARCHAR(255) NULL,
    metadata        JSON NULL,
    resolved        BOOLEAN DEFAULT FALSE,
    resolved_by     BIGINT UNSIGNED NULL,
    resolved_at     TIMESTAMP NULL,
    resolution_note TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_ip (ip_address),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_unresolved (resolved, severity, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### Security Dashboard Queries

```php
// app/Services/SecurityDashboardService.php

class SecurityDashboardService
{
    public function getOverview(): array
    {
        return [
            'active_threats' => SecurityEventLog::where('severity', 'critical')
                ->where('resolved', false)
                ->where('created_at', '>=', now()->subDay())
                ->count(),

            'vpn_attempts_today' => SecurityEventLog::where('event_type', 'vpn_detected')
                ->whereDate('created_at', today())
                ->count(),

            'blocked_ips' => BlockedIp::where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })->count(),

            'failed_logins_today' => SecurityEventLog::where('event_type', 'login_failed')
                ->whereDate('created_at', today())
                ->count(),

            'wallet_fraud_flags' => SecurityEventLog::where('event_type', 'wallet_fraud_check')
                ->where('severity', '>=', 'high')
                ->where('resolved', false)
                ->count(),

            'top_threat_ips' => SecurityEventLog::where('severity', '>=', 'high')
                ->where('created_at', '>=', now()->subDay())
                ->select('ip_address', DB::raw('COUNT(*) as event_count'))
                ->groupBy('ip_address')
                ->orderByDesc('event_count')
                ->limit(10)
                ->get(),
        ];
    }

    public function getTimelinForIp(string $ip): Collection
    {
        return SecurityEventLog::where('ip_address', $ip)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function getUserSecurityProfile(int $userId): array
    {
        return [
            'recent_logins' => SecurityEventLog::where('user_id', $userId)
                ->where('event_type', 'login_success')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),

            'unique_ips' => SecurityEventLog::where('user_id', $userId)
                ->distinct('ip_address')
                ->count('ip_address'),

            'vpn_detections' => SecurityEventLog::where('user_id', $userId)
                ->where('event_type', 'vpn_detected')
                ->count(),

            'fraud_flags' => SecurityEventLog::where('user_id', $userId)
                ->where('event_type', 'wallet_fraud_check')
                ->where('severity', '>=', 'medium')
                ->count(),
        ];
    }
}
```

### Real-Time Alerts

```php
// app/Listeners/SecurityAlertListener.php

class SecurityAlertListener
{
    public function handle(SecurityEventCreated $event): void
    {
        $log = $event->securityEventLog;

        if ($log->severity === 'critical') {
            // Immediate notification to all super-admins
            $admins = User::role('super-admin')->get();
            Notification::send($admins, new CriticalSecurityAlert($log));

            // Send to Slack/Discord webhook
            $this->sendSlackAlert($log);

            // If repeated critical events from same IP, auto-block
            $recentCritical = SecurityEventLog::where('ip_address', $log->ip_address)
                ->where('severity', 'critical')
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($recentCritical >= 3) {
                app(ThreatDetectionService::class)->autoBlockIp(
                    $log->ip_address,
                    ['Repeated critical security events']
                );
            }
        }
    }

    private function sendSlackAlert(SecurityEventLog $log): void
    {
        if (!config('security.slack_webhook_url')) return;

        Http::post(config('security.slack_webhook_url'), [
            'text' => sprintf(
                "🚨 *CRITICAL SECURITY EVENT*\nType: %s\nIP: %s\nUser: %s\nTime: %s\nDetails: %s",
                $log->event_type,
                $log->ip_address,
                $log->user_id ? "User #{$log->user_id}" : 'Anonymous',
                $log->created_at->toISOString(),
                json_encode($log->metadata)
            ),
        ]);
    }
}
```

### Log Retention & Archival

| Log Type | Hot Storage | Archive | Deletion |
|----------|------------|---------|----------|
| `security_event_logs` (critical) | 1 year | 5 years | Never |
| `security_event_logs` (high) | 6 months | 3 years | After 3 years |
| `security_event_logs` (medium/low/info) | 3 months | 1 year | After 1 year |
| `blocked_ips` (expired) | 90 days | 1 year | After 1 year |
| `audit_logs` | 6 months | 2 years | Never |

---

## 13. Audit Logging

### What Gets Logged

| Category | Events |
|----------|--------|
| Authentication | Login, logout, failed login, 2FA challenge, password change |
| Users | Create, update, block, unblock, role change |
| Tenants | Create, update, suspend, activate, credential change |
| Orders | Create, status change, cancel, refund |
| Payments | Initiate, verify, webhook received, refund |
| Wallets | Credit, debit, load request, load approval/rejection |
| Products | Create, update, visibility toggle, sync |
| Offers | Create, update, delete, usage |
| API Keys | Create, revoke, usage spike |
| Settings | Any system setting change |

### Audit Log Entry Structure

```php
AuditLog::create([
    'tenant_id'      => $user->currentTenantId(),
    'user_id'        => $user->id,
    'action'         => 'wallet.credit',
    'auditable_type' => Wallet::class,
    'auditable_id'   => $wallet->id,
    'old_values'     => ['balance' => 5000.00],
    'new_values'     => ['balance' => 10000.00],
    'ip_address'     => request()->ip(),
    'user_agent'     => request()->userAgent(),
    'url'            => request()->fullUrl(),
]);
```

### Audit Service

```php
// app/Services/AuditService.php

class AuditService
{
    public function log(string $action, ?Model $model = null, array $old = [], array $new = []): void
    {
        AuditLog::create([
            'tenant_id'      => auth()->user()?->currentTenantId(),
            'user_id'        => auth()->id(),
            'action'         => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id'   => $model?->id,
            'old_values'     => empty($old) ? null : $old,
            'new_values'     => empty($new) ? null : $new,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
        ]);
    }
}
```

### Retention Policy

- Audit logs retained for **2 years** minimum
- Automatic archival to cold storage after 6 months
- Archived logs searchable but not in primary DB
- Never delete audit logs (compliance requirement)

---

## 14. Vulnerability Remediation

### Immediate Actions (Pre-deployment)

1. **Sanitize `.env.example`** -- Remove all real credentials, replace with `your_xxx_here` placeholders
2. **Resolve merge conflicts** -- Fix `ProcessWoohooOrder.php` conflict markers
3. **Fix middleware aliases** -- Align `is.admin` → `admin` in Kernel.php
4. **Remove Voyager** -- After admin panel migration, fully remove `tcg/voyager`

### Ongoing Security Practices

- **Dependency scanning:** Run `composer audit` weekly
- **Static analysis:** PHPStan level 6+ in CI pipeline
- **Penetration testing:** Quarterly by external firm
- **Secret scanning:** Pre-commit hook to prevent credential leaks
- **Code review:** All PRs require security-aware review for payment/auth code
- **Laravel updates:** Apply security patches within 48 hours of release

---

## 15. Compliance Considerations

### PCI-DSS

- AmazePays does **not** store raw card numbers (payment gateway handles PCI scope)
- Voucher card numbers/PINs are encrypted at rest
- Payment page is served by gateway (redirect model) -- reduces PCI scope
- SAQ-A or SAQ-A-EP compliance level expected

### Data Privacy

- User PII (name, email, phone) stored with purpose limitation
- Right to deletion: user can request account deletion (data anonymized, not hard-deleted)
- Data export: user can request their data in JSON format
- Consent: explicit consent collected for marketing communications
- Retention: user data retained for 3 years after last activity, then anonymized

### Indian Compliance

- **RBI guidelines** on digital payments adhered to
- **GST compliance** -- invoices include GST breakdowns
- **Data localization** -- all data stored on servers within India

---

## 16. Incident Response

### Severity Levels

| Level | Description | Response Time | Example |
|-------|-------------|---------------|---------|
| P0 (Critical) | Data breach, payment system compromise | 15 minutes | Credentials leaked, DB exposed |
| P1 (High) | Service outage, payment failures | 1 hour | Gateway down, orders failing |
| P2 (Medium) | Feature degradation, non-critical bug | 4 hours | Catalog sync failing, slow queries |
| P3 (Low) | Minor issue, cosmetic | Next business day | UI glitch, non-critical error |

### Response Procedure

1. **Detect** -- Monitoring alerts (Sentry, Horizon, custom health checks)
2. **Triage** -- Assign severity level
3. **Contain** -- Isolate affected systems (disable gateway, block IP)
4. **Investigate** -- Review audit logs, application logs, access logs
5. **Remediate** -- Fix root cause, deploy patch
6. **Communicate** -- Notify affected users/tenants if data involved
7. **Post-mortem** -- Document incident, update procedures

---

## Related Documents

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Validated input, webhooks, logging policy
- [B2B_TENANCY.md](B2B_TENANCY.md) -- RBAC and permission details
- [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md) -- Payment security specifics
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) -- API auth details
- [DEPLOYMENT.md](DEPLOYMENT.md) -- Infrastructure security
