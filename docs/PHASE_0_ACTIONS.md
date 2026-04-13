# Phase 0: External Actions & Requirements Checklist

> **Status:** Must complete ALL items marked CRITICAL before Phase 1 dev begins.  
> **Owner:** Business / Project Manager  
> **Deadline:** End of Week 1

---

## 1. Provider API Documentation Requests

### EZ Pin (CRITICAL - blocks Phase 8)

**Send this email to your EZ Pin account manager:**

```
Subject: API Documentation & Sandbox Access Request — AmazePays Integration

Dear EZ Pin Team,

We are building a B2B/B2C voucher distribution platform (AmazePays) and would
like to integrate your product catalog into our system.

We require the following to begin integration:

1. Full REST API documentation (OpenAPI/Swagger preferred)
2. Sandbox / test environment credentials
3. Authentication method (API Key, OAuth2, HMAC - please confirm)
4. Catalog sync API: full dump endpoint + delta/incremental update endpoint
5. Order placement API: sync or async fulfillment?
6. Voucher code delivery: returned in API response or webhook callback?
7. Webhook/callback format for order status updates (sample payload please)
8. Rate limits (requests per minute/hour)
9. Voucher code encryption method (if any)
10. Stock availability API
11. Error codes reference

Please provide sandbox credentials so we can begin integration testing.

Timeline: We plan to begin integration in approximately 14 weeks.

Thank you,
[Your Name]
AmazePays Team
```

**Track:** Send Date: ___________ | Response Due: ___________ | Received: ___________

---

### Gyftrr (CRITICAL - blocks Phase 8)

**Send this email to your Gyftrr account manager:**

```
Subject: API Documentation & Sandbox Access Request — AmazePays Integration

Dear Gyftrr Team,

We are building a B2B/B2C voucher distribution platform (AmazePays) and would
like to integrate your product catalog into our system.

We require the following to begin integration:

1. Full REST API documentation (OpenAPI/Swagger preferred)
2. Sandbox / test environment credentials
3. Authentication method (API Key, OAuth2, HMAC - please confirm)
4. Catalog sync API: full dump + incremental updates
5. Order placement flow (sync vs async)
6. Voucher code delivery mechanism
7. Order status webhook / callback format (sample payload)
8. Rate limits per endpoint
9. Error codes and error handling guidance
10. Support contact for integration queries

Please provide sandbox credentials.

Timeline: Integration planned to start approximately 14 weeks from now.

Thank you,
[Your Name]
AmazePays Team
```

**Track:** Send Date: ___________ | Response Due: ___________ | Received: ___________

---

### Woohoo (Active - but submit any pending change requests NOW)

Items to request from Woohoo that take 7-10 days:

- [ ] Confirm sandbox credentials are still valid
- [ ] Request any catalog delta sync endpoint if not yet available
- [ ] Confirm webhook callback format has not changed
- [ ] Request rate limit documentation
- [ ] Submit any code changes/fixes that require Woohoo-side action

**Track:** Requests Sent: ___________ | Expected By: ___________

---

## 2. Payment Gateway — Razorpay (blocks Phase 7)

**Steps:**

1. Go to https://razorpay.com/
2. Click "Sign Up" → create business account
3. Complete KYC (GST, PAN, bank account details)
4. Submit for activation
5. Once active, collect:
   - `RAZORPAY_KEY_ID`
   - `RAZORPAY_KEY_SECRET`
   - Webhook secret key
   - Enable webhooks in Razorpay dashboard → point to `https://yourdomain.com/api/v1/payments/razorpay/webhook`

**Track:** Applied: ___________ | Activated: ___________ | Keys obtained: ___________

---

## 3. SMS Gateway — MSG91 (blocks Phase 3 OTP)

**Why MSG91:** Native India DLT compliance, OTP auto-retry (SMS → voice), affordable (~0.15 INR/SMS), good Laravel support.

**Steps:**

1. Go to https://msg91.com/ → Sign Up
2. Complete account verification
3. **DLT Registration (mandatory for India):**
   - Register on https://www.trai.gov.in/ DLT portal
   - Register as Principal Entity (PE)
   - Submit header/sender ID: e.g., `AMZPAY`
   - Submit all SMS templates (OTP, transaction alert, login alert, etc.)
   - DLT approval: 2-5 working days
4. Once approved, collect from MSG91 dashboard:
   - `MSG91_AUTH_KEY`
   - `MSG91_SENDER_ID` (e.g., `AMZPAY`)
   - Template IDs for each SMS type (OTP, wallet alert, login alert, etc.)

**SMS Templates to register on DLT:**

| Purpose | Template |
|---------|----------|
| OTP (Login) | `Your AmazePays login OTP is {#var#}. Valid for 5 minutes. Do not share. -AMZPAY` |
| OTP (Transaction) | `Your AmazePays transaction OTP is {#var#}. Valid for 5 minutes. Do not share. -AMZPAY` |
| Login Alert | `New login to your AmazePays account from {#var#}. If not you, contact support immediately. -AMZPAY` |
| Wallet Credit | `Rs.{#var#} credited to your AmazePays wallet. Balance: Rs.{#var#}. -AMZPAY` |
| Wallet Debit | `Rs.{#var#} debited from your AmazePays wallet. Balance: Rs.{#var#}. -AMZPAY` |
| Security Alert | `Security alert on your AmazePays account: {#var#}. Contact support if not you. -AMZPAY` |
| Account Lock | `Your AmazePays account has been temporarily locked due to suspicious activity. Contact support. -AMZPAY` |

**Track:** Account Created: ___________ | DLT Applied: ___________ | Templates Approved: ___________

---

## 4. VPN Detection API — IPHub (blocks Phase 2)

1. Go to https://iphub.info/
2. Sign up for free account (50k requests/day free tier)
3. For production: upgrade to paid plan (~$49/month for 500k requests/day)
4. Collect API key: `IPHUB_API_KEY`

**Alternative:** ip-api.com (free tier: 45 req/min, no key needed for basic use)

**Track:** Key Obtained: ___________

---

## 5. Open Business Questions (resolve before Phase 1)

Taken from `docs/REQUIREMENT_QUESTIONS.md` — these are the MUST-RESOLVE items:

### Must Resolve (blocks dev work)

- [ ] **B2B Margin Model:** How is B2B pricing calculated? (flat margin % or per-product override or both?)
- [ ] **GST Handling:** Is GST collected from B2C customers? If yes, rate per category? Is B2B GST invoice needed?
- [ ] **Refund Policy:** Who bears the cost of failed voucher delivery? Auto-refund to wallet or manual?
- [ ] **Transaction PIN:** Mandatory for all users or optional B2C, mandatory B2B?
- [ ] **2FA Enforcement:** Mandatory for which roles? (Super Admin: yes. B2B Owner: yes. B2C: optional?)
- [ ] **Wallet Load:** Is bank verification automated (penny drop / UPI verification) or manual UTR check?
- [ ] **VPN Policy:** Block VPN for B2C purchases (yes). Block for B2B API? (Plan says log-only -- confirm)
- [ ] **Purchase Limits:** What are the default B2C and B2B daily/monthly limits?
- [ ] **Maker-Checker Threshold:** At what order value does B2B require dual authorization? (e.g., >50,000 INR?)
- [ ] **Business Hours:** Which tenants should have business-hours restriction enabled by default?
- [ ] **Support Domain:** What is the production domain? (affects CORS, CSP, cookie config)
- [ ] **Staging Environment:** Is a staging server available? Details?
- [ ] **Production Host:** Which hosting? (AWS / GCP / DigitalOcean / XAMPP → proper server needed)

---

## 6. Infrastructure Setup (can start in parallel with Phase 0)

- [ ] Install Redis on server (`apt install redis-server` or use Redis Cloud)
- [ ] Confirm PHP 8.2+ is installable on the server
- [ ] Set up staging environment
- [ ] Set up GitHub Actions or similar CI/CD pipeline
- [ ] Set up Sentry account for error tracking (`SENTRY_LARAVEL_DSN`)
- [ ] Set up Cloudflare account (free tier is fine) for WAF + DDoS protection

---

## 7. Environment Variables to Collect

Add these to `.env` and `.env.example` as placeholders:

```env
# SMS Gateway
MSG91_AUTH_KEY=your_msg91_auth_key_here
MSG91_SENDER_ID=AMZPAY
MSG91_OTP_TEMPLATE_ID=your_otp_template_id_here
MSG91_WALLET_CREDIT_TEMPLATE_ID=your_wallet_credit_template_id_here
MSG91_WALLET_DEBIT_TEMPLATE_ID=your_wallet_debit_template_id_here
MSG91_LOGIN_ALERT_TEMPLATE_ID=your_login_alert_template_id_here
MSG91_SECURITY_ALERT_TEMPLATE_ID=your_security_alert_template_id_here

# VPN Detection
IPHUB_API_KEY=your_iphub_api_key_here
VPN_DETECTION_ENABLED=true
VPN_ACTION=block
VPN_BYPASS_IPS=

# Razorpay
RAZORPAY_KEY_ID=your_razorpay_key_id_here
RAZORPAY_KEY_SECRET=your_razorpay_key_secret_here
RAZORPAY_WEBHOOK_SECRET=your_razorpay_webhook_secret_here

# Security
SECURITY_SLACK_WEBHOOK_URL=your_slack_webhook_url_here
GEO_RESTRICT_ENABLED=false
GEO_ALLOWED_COUNTRIES=IN

# App
APP_ENV=local
APP_KEY=base64:GENERATE_WITH_ARTISAN
APP_URL=https://yourdomain.com

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# FCM (Mobile Push Notifications)
FCM_SERVER_KEY=your_fcm_server_key_here
```

---

## Checklist Summary

| Item | Owner | By When | Status |
|------|-------|---------|--------|
| Send EZ Pin API request | BD Team | Day 1 | ⬜ |
| Send Gyftrr API request | BD Team | Day 1 | ⬜ |
| Submit Woohoo pending requests | Dev | Day 1 | ⬜ |
| Apply for Razorpay account | Finance | Day 1 | ⬜ |
| Create MSG91 account | Dev/Finance | Day 1 | ⬜ |
| Submit DLT SMS templates | Dev | Day 2 | ⬜ |
| Get IPHub API key | Dev | Day 1 | ⬜ |
| Resolve 13 business questions | Business/PM | Day 5 | ⬜ |
| Set up Redis on server | Dev | Day 3 | ⬜ |
| Set up Sentry account | Dev | Day 3 | ⬜ |
| Set up staging environment | Dev/Infra | Day 5 | ⬜ |
| Collect all env variables | Dev | Day 7 | ⬜ |

---

## Engineering standards

New code should follow [CODE_STANDARDS.md](CODE_STANDARDS.md) (validated input, no `$request->all()` at trust boundaries, minimal sensitive logging) and [SECURITY.md](SECURITY.md).
