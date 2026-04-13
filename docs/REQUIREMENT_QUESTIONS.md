# AmazePays Requirement Gathering Questions

> **Version:** 2.0  
> **Last Updated:** April 2026  
> **Purpose:** Open questions that need answers before or during implementation to finalize design decisions.

---

## How to Use This Document

Each question is categorized and tagged with priority. Answer these questions as decisions are made, and update this document accordingly. Each answered question should include:
- **Decision:** The chosen approach
- **Date:** When the decision was made
- **Decided by:** Who made the decision

---

## 1. Business Logic

### Pricing & Margins

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 1.1 | How is the margin calculated for B2B clients? Is it a flat % on face value, or on the cost price from the provider? | HIGH | Open |
| 1.2 | Can different products have different margin rates for the same B2B client? | HIGH | Open |
| 1.3 | Is there a minimum margin the platform must maintain? | MEDIUM | Open |
| 1.4 | Are there tiered pricing structures based on volume (e.g., buy 100+ get better rate)? | MEDIUM | Open |
| 1.5 | How is GST calculated and collected? Is it included in the price or added on top? | HIGH | Open |
| 1.6 | Should the platform support multiple currencies or is it INR only? | MEDIUM | Open |

### Commissions & Settlements

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 1.7 | What is the settlement cycle with B2B clients (daily, weekly, monthly)? | MEDIUM | Open |
| 1.8 | Are there commission structures for resellers beyond margins? | MEDIUM | Open |
| 1.9 | How are platform fees structured for B2C transactions (per-transaction, monthly, hybrid)? | LOW | Open |
| 1.10 | Is there an invoicing system needed for B2B clients? | HIGH | Open |

### Refunds

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 1.11 | What is the refund policy for unused/unredeemed vouchers? | HIGH | Open |
| 1.12 | Should refunds go back to the original payment method or always to wallet? | HIGH | Open |
| 1.13 | What is the maximum refund processing time? | MEDIUM | Open |
| 1.14 | Can partial refunds be issued? | MEDIUM | Open |
| 1.15 | Who bears the cost of failed orders -- the platform or the voucher provider? | HIGH | Open |

---

## 2. Voucher Provider Integration

### EZ Pin

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 2.1 | Is EZ Pin API documentation available? If so, provide the URL or PDF. | HIGH | Open |
| 2.2 | Are sandbox/test credentials available for EZ Pin? | HIGH | Open |
| 2.3 | What authentication method does EZ Pin use (API key, OAuth, HMAC)? | HIGH | Open |
| 2.4 | Does EZ Pin support catalog sync with delta updates (changed-since parameter)? | MEDIUM | Open |
| 2.5 | How does EZ Pin deliver voucher codes -- in the order response or via callback? | HIGH | Open |
| 2.6 | What is EZ Pin's rate limiting policy? | MEDIUM | Open |
| 2.7 | Does EZ Pin encrypt voucher codes in transit? If so, what method? | HIGH | Open |

### Gyftrr

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 2.8 | Is Gyftrr API documentation available? | HIGH | Open |
| 2.9 | Are sandbox/test credentials available for Gyftrr? | HIGH | Open |
| 2.10 | What authentication method does Gyftrr use? | HIGH | Open |
| 2.11 | Does Gyftrr support real-time stock checks? | MEDIUM | Open |
| 2.12 | What is Gyftrr's fulfillment model -- synchronous or asynchronous? | HIGH | Open |
| 2.13 | Are there Gyftrr-specific order constraints (min/max qty, denomination rules)? | MEDIUM | Open |

### General Provider Questions

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 2.14 | For multi-provider products (same brand available from multiple providers), what is the selection priority logic? | MEDIUM | Open |
| 2.15 | Should the platform support automatic failover (e.g., if Woohoo is down, try EZ Pin)? | MEDIUM | Open |
| 2.16 | How frequently should each provider's catalog be synced? | MEDIUM | Open |
| 2.17 | Should new products from provider sync be auto-published or require admin review? | HIGH | Open |

---

## 3. B2B Onboarding & Operations

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 3.1 | What is the B2B client onboarding workflow? Is KYC verification required? | HIGH | Open |
| 3.2 | What documents are needed for B2B onboarding (GST cert, PAN, incorporation)? | MEDIUM | Open |
| 3.3 | How is the initial credit limit determined for new B2B clients? | HIGH | Open |
| 3.4 | What triggers automatic suspension (credit limit, overdue payments, suspicious activity)? | HIGH | Open |
| 3.5 | Should B2B clients be able to create sub-users (operators) with limited permissions? | HIGH | Open |
| 3.6 | Is there a self-service B2B registration flow, or is onboarding always admin-initiated? | MEDIUM | Open |
| 3.7 | Do B2B clients need whitelabeled reports or the AmazePays brand? | LOW | Open |
| 3.8 | Should the B2B portal support custom branding per tenant (logo, colors)? | LOW | Open |
| 3.9 | How many B2B clients do you currently have, and how many are expected in 12 months? | MEDIUM | Open |
| 3.10 | Do B2B clients need to manage their own offers/promotions, or is that admin-only? | MEDIUM | Open |

---

## 4. Wallet & Payments

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 4.1 | Is bank transfer verification manual (admin checks bank statement) or automated (bank API)? | HIGH | Open |
| 4.2 | What is the minimum wallet load amount? Maximum? | MEDIUM | Open |
| 4.3 | Should wallets support withdrawal (transfer back to bank account)? | MEDIUM | Open |
| 4.4 | Is there a wallet-to-wallet transfer feature needed (B2B client to sub-users)? | LOW | Open |
| 4.5 | Should wallet balances accrue interest or have any time-based rules? | LOW | Open |
| 4.6 | What happens to wallet balance when a B2B client is deactivated? | HIGH | Open |
| 4.7 | Is Razorpay integration active or purely planned? Are credentials available? | HIGH | Open |
| 4.8 | Are there any specific payment gateway preferences per region or user segment? | MEDIUM | Open |
| 4.9 | Should the platform support UPI as a direct payment method (not via gateway)? | MEDIUM | Open |
| 4.10 | Is Net Banking via Unlimit currently working in production? | MEDIUM | Open |

---

## 5. Mobile App

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 5.1 | Which push notification provider should be used (FCM, OneSignal, custom)? | MEDIUM | Open |
| 5.2 | Are deep links required (e.g., share a product link that opens in the app)? | MEDIUM | Open |
| 5.3 | Should the app work offline (cached catalog, view purchased vouchers)? | MEDIUM | Open |
| 5.4 | Is biometric authentication (fingerprint/face) needed for the app? | LOW | Open |
| 5.5 | Should there be a separate B2B app or a role-based mode in the same app? | HIGH | Open |
| 5.6 | What is the minimum iOS version to support (iOS 15? 16?)? | MEDIUM | Open |
| 5.7 | What is the minimum Android version to support (Android 10? 12?)? | MEDIUM | Open |
| 5.8 | Should the app support dark mode? | LOW | Open |
| 5.9 | Is in-app chat/support needed? | LOW | Open |
| 5.10 | What languages should the app support (English only, or multilingual)? | MEDIUM | Open |

---

## 6. Reseller & Loyalty API

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 6.1 | What are the SLA requirements for API uptime and response time? | HIGH | Open |
| 6.2 | What is the webhook retry policy if the reseller endpoint is down? | MEDIUM | Open |
| 6.3 | What is the maximum bulk order size (items per request)? | HIGH | Open |
| 6.4 | Should the API support sandbox/test mode with fake voucher codes? | HIGH | Open |
| 6.5 | Are there specific resellers already lined up? What are their technical requirements? | MEDIUM | Open |
| 6.6 | How should loyalty points be valued (fixed rate or variable)? | HIGH | Open |
| 6.7 | Should loyalty point redemption support partial points + cash payment? | MEDIUM | Open |
| 6.8 | Is there an existing loyalty program to integrate with, or build from scratch? | HIGH | Open |
| 6.9 | What reporting/analytics do resellers need access to via API? | MEDIUM | Open |
| 6.10 | Should the API support real-time inventory/stock checks? | MEDIUM | Open |

---

## 7. Security & Compliance

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 7.1 | What is the PCI-DSS scope? Does the platform need PCI compliance? | HIGH | Open |
| 7.2 | What data retention policy should be enforced (how long to keep orders, logs)? | HIGH | Open |
| 7.3 | Is GDPR compliance required (for international users)? | MEDIUM | Open |
| 7.4 | What is the password policy (min length, complexity, expiry)? | MEDIUM | Open |
| 7.5 | Should 2FA be mandatory for all admin users or optional? | HIGH | Open |
| 7.6 | Is there a requirement for SOC2 or ISO 27001 certification? | LOW | Open |
| 7.7 | What is the acceptable session timeout for admin panel (30 min, 2 hours)? | MEDIUM | Open |
| 7.8 | Should there be IP-based access control for the admin panel? | HIGH | Open |
| 7.9 | Is there a DLP (Data Loss Prevention) requirement for voucher codes? | MEDIUM | Open |
| 7.10 | What is the incident response notification requirement (who, when, how)? | HIGH | Open |

---

## 8. Offers & Promotions

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 8.1 | Can offers be stacked (multiple offers on same order)? | HIGH | Open |
| 8.2 | Should cashback offers credit immediately or after a hold period? | MEDIUM | Open |
| 8.3 | Are there referral-based offers (user refers friend, both get discount)? | LOW | Open |
| 8.4 | Should offers be visible to all users or targeted (new users, specific segments)? | MEDIUM | Open |
| 8.5 | Are there provider-specific offers (e.g., Woohoo running a promotion)? | MEDIUM | Open |
| 8.6 | Should expired offers be archived or deleted? | LOW | Open |
| 8.7 | Is there an offer approval workflow (marketer creates, admin approves)? | LOW | Open |
| 8.8 | Should the system support time-limited flash sales (e.g., 2-hour window)? | MEDIUM | Open |

---

## 9. Infrastructure & Operations

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 9.1 | Where will the production server be hosted (AWS, GCP, DigitalOcean, on-premise)? | HIGH | Open |
| 9.2 | Is a staging/UAT environment needed in addition to production? | HIGH | Open |
| 9.3 | What is the expected peak traffic (orders per hour, concurrent users)? | MEDIUM | Open |
| 9.4 | Is a CDN required for static assets (images, JS/CSS)? | MEDIUM | Open |
| 9.5 | What is the RTO (Recovery Time Objective) and RPO (Recovery Point Objective)? | HIGH | Open |
| 9.6 | Is there a preference for CI/CD tool (GitHub Actions, GitLab CI, Jenkins)? | MEDIUM | Open |
| 9.7 | Should the app support maintenance mode with a custom page? | LOW | Open |
| 9.8 | Is there a log aggregation preference (ELK, Datadog, CloudWatch)? | LOW | Open |

---

## 10. UI/UX & Branding

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 10.1 | Are there existing brand guidelines (colors, fonts, logo) for the admin panel? | MEDIUM | Open |
| 10.2 | Should the B2B portal be white-labeled or AmazePays branded? | MEDIUM | Open |
| 10.3 | Are wireframes or mockups available for any screens? | MEDIUM | Open |
| 10.4 | What is the preferred notification style (toast, modal, banner)? | LOW | Open |
| 10.5 | Should the admin panel support dark mode? | LOW | Open |
| 10.6 | What email template style is preferred for transactional emails? | MEDIUM | Open |

---

## Decision Log

Track answered questions here:

| Question # | Decision | Date | Decided By |
|------------|----------|------|-----------|
| -- | -- | -- | -- |

---

## Related Documents

- [ARCHITECTURE.md](ARCHITECTURE.md) -- System architecture
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Implementation standards for new features
- [B2B_TENANCY.md](B2B_TENANCY.md) -- B2B model details
- [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md) -- Payment integration
- [VOUCHER_PROVIDERS.md](VOUCHER_PROVIDERS.md) -- Provider integration
- [SECURITY.md](SECURITY.md) -- Security policies
