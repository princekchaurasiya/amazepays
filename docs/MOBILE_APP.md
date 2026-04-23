# AmazePays Mobile App Architecture

> **Version:** 1.0  
> **Last Updated:** April 2026  
> **Platform:** iOS + Android (React Native)

**Implementation status:** see [`amazepays-mobile/docs/MOBILE_PROGRESS.md`](../../amazepays-mobile/docs/MOBILE_PROGRESS.md). **Local emulators:** [`amazepays-mobile/docs/EMULATOR_SETUP.md`](../../amazepays-mobile/docs/EMULATOR_SETUP.md).

---

## Table of Contents

1. [Overview](#1-overview)
2. [Technology Stack](#2-technology-stack)
3. [Project Structure](#3-project-structure)
4. [Navigation Architecture](#4-navigation-architecture)
5. [Screen Specifications](#5-screen-specifications)
6. [State Management](#6-state-management)
7. [API Consumption](#7-api-consumption)
8. [Authentication Flow](#8-authentication-flow)
9. [Payment Integration](#9-payment-integration)
10. [Push Notifications](#10-push-notifications)
11. [Offline Support](#11-offline-support)
12. [App Distribution](#12-app-distribution)

---

## 1. Overview

AmazePays mobile app is a cross-platform application built with React Native, targeting both iOS and Android from a single TypeScript codebase. The app serves primarily B2C consumers, with an optional role-based B2B client mode.

### App Variants

| Variant | Users | Features |
|---------|-------|----------|
| B2C Consumer | End consumers | Browse, buy vouchers, wallet, offers |
| B2B Client (role-based) | Business distributors | Dashboard, bulk orders, wallet, reports |

Both variants share the same app binary. The UI adapts based on the authenticated user's role.

---

## 2. Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Framework | React Native (Expo managed workflow) | SDK 52+ |
| Language | TypeScript | 5.x |
| Navigation | React Navigation | v7 |
| State Management | Zustand | 5.x |
| Server State | TanStack Query (React Query) | v5 |
| HTTP Client | Axios | 1.x |
| Forms | React Hook Form + Zod | -- |
| Secure Storage | react-native-keychain | -- |
| Push Notifications | expo-notifications + FCM | -- |
| Payments | react-native-webview (gateway redirects) | -- |
| Image Loading | expo-image | -- |
| Barcode/QR | expo-barcode-scanner | -- |
| Analytics | Firebase Analytics | -- |
| Crash Reporting | Sentry React Native | -- |

---

## 3. Project Structure

```
amazepays-mobile/
├── app/                          # Expo Router file-based routing
│   ├── (auth)/                   # Auth screens (not authenticated)
│   │   ├── login.tsx             # Unified: phone → OTP → profile (new users) + 2FA step
│   │   └── onboarding.tsx
│   ├── (tabs)/                   # Main tab navigator
│   │   ├── index.tsx             # Home
│   │   ├── browse.tsx            # Browse/Search
│   │   ├── orders.tsx            # Order History
│   │   ├── wallet.tsx            # Wallet
│   │   └── profile.tsx           # Profile/Settings
│   ├── product/[id].tsx          # Product detail
│   ├── checkout/index.tsx        # Checkout
│   ├── payment/index.tsx         # Payment webview
│   ├── order/[id].tsx            # Order detail
│   └── voucher/[id].tsx          # Voucher detail (card, pin)
├── src/
│   ├── api/
│   │   ├── client.ts             # Axios instance with interceptors
│   │   ├── auth.ts               # Auth API calls
│   │   ├── products.ts           # Product API calls
│   │   ├── orders.ts             # Order API calls
│   │   ├── wallet.ts             # Wallet API calls
│   │   └── offers.ts             # Offers API calls
│   ├── hooks/
│   │   ├── useAuth.ts            # Auth state hook
│   │   ├── useProducts.ts        # TanStack Query hooks for products
│   │   ├── useOrders.ts          # TanStack Query hooks for orders
│   │   ├── useWallet.ts          # Wallet hooks
│   │   └── useOffers.ts          # Offer hooks
│   ├── stores/
│   │   ├── authStore.ts          # Zustand auth store
│   │   ├── cartStore.ts          # Cart state
│   │   └── appStore.ts           # App-wide state (theme, etc.)
│   ├── components/
│   │   ├── ui/                   # Design system components
│   │   ├── products/             # Product cards, lists
│   │   ├── wallet/               # Wallet balance, transaction list
│   │   └── common/               # Headers, loaders, empty states
│   ├── utils/
│   │   ├── storage.ts            # Secure storage helpers
│   │   ├── format.ts             # Currency, date formatting
│   │   └── validation.ts         # Zod schemas
│   └── types/
│       ├── api.ts                # API response types
│       ├── models.ts             # Domain models
│       └── navigation.ts         # Navigation param types
├── assets/                       # Images, fonts
├── app.json                      # Expo config
├── eas.json                      # EAS Build config
└── tsconfig.json
```

---

## 4. Navigation Architecture

```
Root Navigator
├── Auth Stack (unauthenticated)
│   ├── Onboarding
│   ├── Login (phone number)
│   └── Verify OTP
│
└── Main Tab Navigator (authenticated)
    ├── Home Tab
    │   └── Home Screen
    │       ├── → Product Detail
    │       │   └── → Checkout
    │       │       └── → Payment WebView
    │       │           └── → Order Confirmation
    │       ├── → Category Products
    │       └── → Brand Products
    │
    ├── Browse Tab
    │   └── Browse/Search Screen
    │       └── → Product Detail (shared)
    │
    ├── Orders Tab
    │   └── Order History Screen
    │       └── → Order Detail
    │           └── → Voucher Detail (card number, pin, barcode)
    │
    ├── Wallet Tab
    │   └── Wallet Screen
    │       ├── Balance + Transactions
    │       └── → Request Load (B2B only)
    │
    └── Profile Tab
        └── Profile Screen
            ├── Edit Profile
            ├── Notification Settings
            ├── Help / Support
            └── Logout
```

---

## 5. Screen Specifications

### 5.1 Home Screen

| Element | Source | Refresh |
|---------|--------|---------|
| Hero banners | `GET /api/v1/home` → `data.slides` (each slide: `image_mobile`, `desktop_image`, optional links) | Pull-to-refresh / TanStack refetch |
| Categories row | `GET /api/v1/catalog/categories` | Cached ~15 min |
| Product list | `GET /api/v1/catalog` (pagination; filters: `search`, `category_id`, `brand_id`) | Cached ~5 min |
| Active offers | (when exposed) offer validate endpoints — see API docs | — |
| Brands grid | From catalog / product payloads or dedicated endpoint if added | — |

**Admin:** Upload hero images under **Settings → Hero carousel** (`/panel/settings/hero-slides`). The **Homepage sections** `banner` row only toggles visibility of the hero block on the web home.

### 5.2 Product Detail Screen

| Element | Description |
|---------|-------------|
| Images | Swipeable image carousel |
| Name + Brand | Product title and brand badge |
| Price | Range display (₹100 - ₹10,000) or fixed price |
| Denomination selector | Dropdown or pill selector for SLAB pricing |
| Custom amount input | For RANGE pricing (within min/max) |
| Quantity | Stepper (1-10) |
| Discount badge | Shows discount % if applicable |
| Active offers | Applicable offers displayed |
| How to Redeem | Expandable accordion |
| Terms & Conditions | Expandable accordion |
| [Add to Cart] | Primary CTA button |
| [Buy Now] | Secondary CTA -- skip cart |

### 5.3 Checkout Screen

| Element | Description |
|---------|-------------|
| Order summary | Product, denomination, qty, price |
| Promo code input | Enter code + [Apply] |
| Applied offer | Shows discount breakdown |
| Receiver details | Name, email, mobile, message |
| Payment method | Wallet / CCAvenue / Razorpay / Unlimit |
| Billing address | Pre-filled from profile, editable |
| Price breakdown | Subtotal, discount, GST, total |
| [Place Order] | Submit order |

### 5.4 Voucher Detail Screen

| Element | Description |
|---------|-------------|
| Product name | Voucher title |
| Card number | Masked by default, tap to reveal |
| PIN | Hidden by default, tap to reveal |
| Barcode/QR | Scannable code (if applicable) |
| Expiry date | Card expiry |
| Balance | Remaining balance |
| [Copy Card Number] | Clipboard copy |
| [Copy PIN] | Clipboard copy |
| [Share] | Share via native share sheet |

### 5.5 Wallet Screen

| Element | Description |
|---------|-------------|
| Balance card | Current balance with currency |
| Quick actions | [Add Money] (B2B: load request) |
| Transaction list | Infinite scroll, filterable by type |
| Transaction detail | Type, amount, reference, date, description |

---

## 6. State Management

### Zustand Stores

```typescript
// stores/authStore.ts
interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (token: string, user: User) => void;
  logout: () => void;
  updateUser: (user: Partial<User>) => void;
}

// stores/cartStore.ts
interface CartState {
  items: CartItem[];
  promoCode: string | null;
  discount: number;
  addItem: (item: CartItem) => void;
  removeItem: (sku: string) => void;
  updateQuantity: (sku: string, qty: number) => void;
  applyPromo: (code: string) => void;
  clearCart: () => void;
  getTotal: () => number;
}
```

### TanStack Query (Server State)

```typescript
// hooks/useProducts.ts
export function useProducts(filters: ProductFilters) {
  return useQuery({
    queryKey: ['products', filters],
    queryFn: () => api.products.list(filters),
    staleTime: 5 * 60 * 1000,    // 5 minutes
    gcTime: 30 * 60 * 1000,      // 30 minutes cache
  });
}

export function useProduct(id: number) {
  return useQuery({
    queryKey: ['product', id],
    queryFn: () => api.products.get(id),
    staleTime: 5 * 60 * 1000,
  });
}

// hooks/useOrders.ts
export function useCreateOrder() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateOrderRequest) => api.orders.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['orders'] });
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
    },
  });
}
```

---

## 7. API Consumption

The backend **only trusts fields validated per endpoint**; extra JSON keys are ignored for persistence. Never send passwords, OTPs, or card data in custom headers or undocumented fields—see [CODE_STANDARDS.md](CODE_STANDARDS.md#http-request-input--logging) and the overview in [API_DOCUMENTATION.md](API_DOCUMENTATION.md). Payment webhooks are server-to-gateway only (not called from the app).

### API Client Setup

```typescript
// api/client.ts
import axios from 'axios';
import * as Keychain from 'react-native-keychain';

import { Platform } from 'react-native';

// Android emulator: host machine is 10.0.2.2 (not localhost). Use composer `serve:mobile` (0.0.0.0:8000).
const DEV_API_ORIGIN =
  Platform.OS === 'android' ? 'http://10.0.2.2:8000' : 'http://localhost:8000';

const API_BASE = __DEV__
  ? `${DEV_API_ORIGIN}/api/v1`
  : 'https://api.amazepays.com/api/v1';

const client = axios.create({
  baseURL: API_BASE,
  timeout: 15000,
  headers: { 'Accept': 'application/json' },
});

// Request interceptor: attach auth token
client.interceptors.request.use(async (config) => {
  const credentials = await Keychain.getGenericPassword();
  if (credentials) {
    config.headers.Authorization = `Bearer ${credentials.password}`;
  }
  return config;
});

// Response interceptor: handle 401, format errors
client.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    if (error.response?.status === 401) {
      await Keychain.resetGenericPassword();
      // Navigate to login
    }
    return Promise.reject(error.response?.data?.error || error);
  }
);

export default client;
```

### API Modules

```typescript
// api/products.ts
export const products = {
  list: (filters: ProductFilters) =>
    client.get('/products', { params: filters }),

  get: (id: number) =>
    client.get(`/products/${id}`),

  search: (query: string) =>
    client.get('/products', { params: { search: query } }),
};

// api/orders.ts
export const orders = {
  create: (data: CreateOrderRequest) =>
    client.post('/orders', data),

  list: (params?: OrderListParams) =>
    client.get('/orders', { params }),

  get: (merchantOrderId: string) =>
    client.get(`/orders/${merchantOrderId}`),
};

// api/wallet.ts
export const wallet = {
  balance: () => client.get('/wallet/balance'),
  transactions: (params?: PaginationParams) =>
    client.get('/wallet/transactions', { params }),
  requestLoad: (data: LoadRequest) =>
    client.post('/wallet/load-request', data),
};
```

---

## 8. Authentication Flow

Aligned with the **web** `UnifiedAuthController` flow: **mobile-first OTP only** (no passwords).

```
App Launch
    │
    ▼
Check secure storage for Sanctum token
    │
    ├── Token exists → GET /api/v1/auth/me
    │   ├── Valid → Navigate to Main Tab
    │   └── Invalid (401) → Clear token → Navigate to Auth
    │
    └── No token → Navigate to Auth Stack
         │
         ▼
    Single login.tsx — Step 1: 10-digit mobile
         │
         ▼
    POST /api/v1/auth/otp/send
         │
         ▼
    Step 2: 6-digit OTP (SMS)
         │
         ▼
    POST /api/v1/auth/otp/verify
         │
         ├── action: logged_in → Store token → Main Tab
         ├── action: 2fa_required → TOTP screen → POST /api/v1/auth/2fa/verify → Main Tab
         └── action: needs_profile → Step 3: name + optional email/referral
                    │
                    ▼
              POST /api/v1/auth/complete-profile
                    │
                    ▼
              Store token → Main Tab
```

Branding: use `assets/logo.png` (sync with web `public/images/logo.png` when available).

### Token Storage

```typescript
// Secure storage using react-native-keychain
async function storeToken(token: string): Promise<void> {
  await Keychain.setGenericPassword('amazepays_token', token, {
    accessible: Keychain.ACCESSIBLE.WHEN_UNLOCKED,
    securityLevel: Keychain.SECURITY_LEVEL.SECURE_HARDWARE,
  });
}

async function getToken(): Promise<string | null> {
  const credentials = await Keychain.getGenericPassword();
  return credentials ? credentials.password : null;
}

async function clearToken(): Promise<void> {
  await Keychain.resetGenericPassword();
}
```

---

## 9. Payment Integration

### Gateway WebView Flow

Since payment gateways (CCAvenue, Razorpay, Unlimit) require browser-based checkout, the app uses a WebView:

```
Checkout Screen
    │
    ▼
POST /api/v1/checkout/initiate
    → Returns { payment_url, merchant_order_id }
    │
    ▼
Open WebView with payment_url
    │
    ├── User completes payment on gateway
    │   │
    │   ▼
    │   Gateway redirects to return_url
    │   │
    │   ▼
    │   WebView detects return_url navigation
    │   │
    │   ▼
    │   Close WebView → Poll order status
    │
    └── User cancels
        │
        ▼
        Gateway redirects to cancel_url
        │
        ▼
        Close WebView → Show "Payment cancelled"
```

### WebView Payment Component

```typescript
function PaymentWebView({ paymentUrl, onComplete, onCancel }) {
  const handleNavigationChange = (navState) => {
    if (navState.url.includes('/payment/return')) {
      onComplete();
    }
    if (navState.url.includes('/payment/cancel')) {
      onCancel();
    }
  };

  return (
    <WebView
      source={{ uri: paymentUrl }}
      onNavigationStateChange={handleNavigationChange}
      startInLoadingState
      javaScriptEnabled
    />
  );
}
```

### Wallet Payment (No WebView)

For wallet payments, no WebView is needed:

```
POST /api/v1/orders (payment_method: "wallet")
    → Wallet debited server-side
    → Order placed immediately
    → Navigate to Order Confirmation
```

---

## 10. Push Notifications

### Setup (FCM via Expo)

```typescript
// Initialize in app root
import * as Notifications from 'expo-notifications';

async function registerForPush(): Promise<string | null> {
  const { status } = await Notifications.requestPermissionsAsync();
  if (status !== 'granted') return null;

  const token = await Notifications.getExpoPushTokenAsync();

  // Send token to backend
  await client.post('/profile/push-token', {
    token: token.data,
    platform: Platform.OS,
  });

  return token.data;
}
```

### Notification Types

| Event | Title | Body | Action |
|-------|-------|------|--------|
| Order placed | "Order Confirmed" | "Your order #ORD-123 has been placed" | Open order detail |
| Voucher ready | "Voucher Delivered" | "Your Amazon Gift Card is ready!" | Open voucher detail |
| Order failed | "Order Issue" | "There was an issue with order #ORD-123" | Open order detail |
| Wallet credited | "Wallet Credited" | "₹5,000 added to your wallet" | Open wallet |
| New offer | "Special Offer" | "Get 15% off on Swiggy cards!" | Open offer/product |

---

## 11. Offline Support

### Caching Strategy

| Data | Offline Available | Cache Duration |
|------|-------------------|---------------|
| Product catalog | Yes (last fetched) | 30 minutes |
| Categories/brands | Yes | 1 hour |
| Order history | Yes (last fetched) | 15 minutes |
| Wallet balance | Yes (last fetched) | 5 minutes |
| Voucher details | Yes (for purchased) | Permanent (encrypted) |
| User profile | Yes | Until logout |

### Offline Behavior

- Browsing: Show cached products with "Last updated X minutes ago" banner
- Ordering: Block with "Internet connection required" message
- Voucher viewing: Available offline (data stored locally encrypted)
- Pull-to-refresh: Show error toast if no connection

---

## 12. App Distribution

### Build & Release

| Platform | Distribution | Tool |
|----------|-------------|------|
| iOS | App Store | EAS Build + EAS Submit |
| Android | Google Play | EAS Build + EAS Submit |
| Testing | Internal | Expo Dev Client / TestFlight / Internal Track |

### Environment Configuration

```json
// app.json
{
  "expo": {
    "name": "AmazePays",
    "slug": "amazepays",
    "version": "1.0.0",
    "ios": {
      "bundleIdentifier": "com.amazepays.app",
      "supportsTablet": true
    },
    "android": {
      "package": "com.amazepays.app",
      "adaptiveIcon": { "foregroundImage": "./assets/adaptive-icon.png" }
    },
    "extra": {
      "apiUrl": process.env.API_URL
    }
  }
}
```

---

## Related Documents

- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) -- Full API spec the app consumes
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- How the server validates and logs requests
- [SECURITY.md](SECURITY.md) -- Token storage and security
- [ARCHITECTURE.md](ARCHITECTURE.md) -- System architecture overview
