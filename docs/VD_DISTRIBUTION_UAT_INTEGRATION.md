# VD Distribution UAT API Integration Reference

## Overview
This document captures the UAT integration details shared by the VD team for future reference.

## Distributor Credentials (UAT)
- **Distributor ID:** `VDIDAmazepay`
- **Distributor Name:** `Amazepay`
- **API Username:** `A1102C805D81490C940A1C70A103802D`
- **API Password:** `6W,SHOaV83E63488CC0F4C18B63F01DB`
- **Secret Key:** `f7909baf63ca4e9e8c4b0d5b45313e97`
- **Secret IV:** `c14e3ff4b6eb4e76`

> Security note: Move all credentials to environment variables before production deployment.

## API Endpoints

### 1) Generate Token
- **URL:** `http://cards.vdwebapi.com/distributor/api-generatetoken/`
- **Headers:**
  - `username: <API Username>`
  - `password: <API Password>`
- **Body:**
```json
{"distributor_id": "VDIDAmazepay"}
```

---

### 2) Get Brand
- **URL:** `http://cards.vdwebapi.com/distributor/api-getbrand/`
- **Headers:**
  - `token: <generated_token>`
- **Body:**
```json
{"BrandCode": ""}
```

---

### 3) Get Store
- **URL:** `http://cards.vdwebapi.com/distributor/api-getstore/`
- **Headers:**
  - `token: <generated_token>`
- **Body:**
```json
{"BrandCode": ""}
```

---

### 4) Get EVC
- **URL:** `http://cards.vdwebapi.com/distributor/getevc/`
- **Headers:**
  - `token: <generated_token>`
- **Raw Request Data Example:**
```json
{
  "order_id": "PQL98PQ9IUISPQQID74",
  "distributor_id": "VDIDAmazepay",
  "sku_code": "",
  "no_of_card": 1,
  "amount": "100",
  "receiptNo": "V9IQUZAOJLIY",
  "reqId": "1LLZAOJU92YTkk55",
  "firstname": "Jayanta",
  "lastname": "Keni",
  "email": "keni.jayanta99@gmail.com",
  "mobile_no": "+918689970962",
  "address": "Mulund",
  "city": "Mumbai",
  "state": "Maharashtra",
  "country": "IN",
  "pincode": "400081",
  "curr": "356"
}
```
- **Postman Body Parameter:**
```json
{"payload": ""}
```

---

### 5) Get EVC Status
- **URL:** `http://cards.vdwebapi.com/distributor/getevcstatus/`
- **Headers:**
  - `token: <generated_token>`
- **Body:**
```json
{
  "order_id": "",
  "request_ref_no": ""
}
```

---

### 6) Get Activated EVC
- **URL:** `http://cards.vdwebapi.com/distributor/getactivatedevc/`
- **Headers:**
  - `token: <generated_token>`
- **Body:**
```json
{
  "order_id": "",
  "request_ref_no": ""
}
```

---

### 7) Wallet Balance
- **URL:** `http://cards.vdwebapi.com/distributor/getwalletbalance/`
- **Headers:**
  - `token: <generated_token>`
- **Body:**
```json
{"distributor_id": "VDIDAmazepay"}
```

## Onboarding & Operational Notes
- Provide onboarding details on company letterhead with signature and stamp.
- IP whitelisting is mandatory before transaction APIs can be used.
- Share client/server outgoing IPs with VD team for whitelisting.
- Complete integration setup and testing from our side, then confirm results.

## Internal Tracking (Amazepay)
- **Integration Start Date:** `<to be confirmed>`
- **ETA for Completion:** `<to be confirmed>`
- **Owner:** `Samsher / Engineering`
- **Status:** `Pending kickoff`

## Suggested Integration Sequence
1. Generate token.
2. Fetch brand and store data.
3. Build and encrypt payload for `getevc`.
4. Poll `getevcstatus` / `getactivatedevc`.
5. Verify wallet balance reconciliation.
6. Confirm UAT test cases and sign-off.

