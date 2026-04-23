# Vouchagram Communication Engine – Pull Voucher API

**Version:** 1.1 (per vendor documentation, Sept 2025)  
**Classification (vendor):** Internal use / shareable for integrations  

This file is a Markdown reference derived from the vendor PDF. All requests must use **HTTPS**. The integration domain is provided by Vouchagram.

---

## Revision history (vendor)

| Date | Author | Version | Summary |
|------|--------|---------|---------|
| 23 Jun 2024 | Brijesh Kumar | 1.0.0 | Initial draft |
| 29 Sept 2025 | Akash Singh | 1.1.0 | Reformatted; check status; error list |

---

## 1. Copyright & confidentiality (vendor)

All rights reserved by Vouchagram group of companies. Receiving parties must not disclose confidential information except as needed for integration, and only to personnel under confidentiality obligations.

---

## 2. Encryption and decryption

1. Encrypt the JSON request before sending (for endpoints that use `payload`).
2. Decrypt the `data` field in responses to validate JSON.

Implementation reference: `App\Services\Voucher\VouchagramService` (mode `pull`). See **§11** for Node.js samples.

---

## 3. GET TOKEN API

Same contract as Send API: JWT in decrypted `data`, ~**30 minute** lifetime.

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/gettoken` |
| **Method** | `GET` |

### Headers

| Name | Type | Mandatory |
|------|------|-----------|
| Content-Type | string | Yes – `application/json` |
| username | string | Yes |
| password | string | Yes |

### Sample response shape

```json
{
  "status": "success",
  "data": "<base64 ciphertext>",
  "desc": "Process successfully completed",
  "code": "0000"
}
```

Decrypted `data`: JWT string.

---

## 4. GET BRANDS API

Identical to Send API: `[domain]/API/v1/getbrands`, `POST`, header `token`, body `BrandProductCode` optional / `""` for all.

See [VOUCHAGRAM_SEND_API.md §4](./VOUCHAGRAM_SEND_API.md) for field notes (`RedemptionType`, `DenomType`, `ServiceType`).

---

## 5. PULL VOUCHER API

**Purpose:** Allocate vouchers for a brand and denomination; response includes voucher details or an error.

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/pullvoucher` |
| **Method** | `POST` |

### Headers

| Name | Mandatory |
|------|-----------|
| Content-Type | Yes |
| token | Yes – decrypted JWT |

### Body

| Name | Type | Mandatory |
|------|------|-----------|
| payload | string | Yes – single encrypted JSON |

### Inner JSON (decrypted)

| Name | Type | Required |
|------|------|----------|
| BrandProductCode | string(50) | Yes |
| ExternalOrderId | string(50) | Yes |
| Quantity | number | Yes (max recommended 10) |
| Denomination | number | Yes |

### Example inner JSON

```json
{
  "BrandProductCode": "internalfixedtestingePvroJd8rW3OJkwQ",
  "Denomination": "1000",
  "Quantity": 1,
  "ExternalOrderId": "TEST_stag_29092025_00003"
}
```

### Outer request

```json
{
  "payload": "<base64 encrypted JSON>"
}
```

### Decrypted success `data` example

```json
{
  "PullVouchers": [
    {
      "Vouchers": [
        {
          "ProductDenomination": "FIXED_DENOMINATION",
          "VoucherNo": "191774775",
          "VoucherGuid": "0bae3545-2f3a-4c7a-b4a9-32dfb94483e0",
          "EndDate": "31 Dec 2025",
          "Value": "1000.00",
          "Voucherpin": "",
          "VoucherGCcode": "7041849782660454"
        }
      ],
      "ProductGuid": "c23a1776-8de3-447c-84b4-01c6073bc804",
      "ProductName": "internal_fixed_testing",
      "VoucherName": "internal_fixed_testing"
    }
  ],
  "ErrorCode": "",
  "ErrorMessage": "",
  "ExternalOrderIdOut": "TEST_stag_29092025_00003",
  "Message": "Process successfully completed",
  "ResultType": "SUCCESS",
  "BrandProductCode": "internalfixedtestingePvroJd8rW3OJkwQ"
}
```

---

## 6. CHECK STATUS API (pull voucher)

**Purpose:** Check status of a **pull voucher** request.

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/pullvoucher/checkstatus` |
| **Method** | `POST` |

### Body

| Name | Mandatory |
|------|-----------|
| payload | Yes – encrypted JSON |

### Inner JSON (decrypted)

```json
{
  "sv_ex_order_id": "TEST_stag_29092025_00003"
}
```

### Decrypted `data` example (illustrative)

```json
{
  "PullVouchers": [
    {
      "Vouchers": [
        {
          "VoucherNo": "191774775",
          "VoucherGuid": "0bae3545-2f3a-4c7a-b4a9-32dfb94483e0",
          "EndDate": "31 Dec 2025",
          "Value": "1000.00",
          "Voucherpin": "",
          "VoucherGCcode": "7041849782660454"
        }
      ],
      "ProductName": "internal_fixed_testing",
      "VoucherName": "internal_fixed_testing"
    }
  ],
  "ErrorCode": "1",
  "ErrorMessage": "",
  "ExternalOrderIdOut": "TEST_stag_29092025_00003",
  "Message": "Process successfully completed",
  "ResultType": "SUCCESS",
  "BrandProductCode": "internalfixedtestingePvroJd8rW3OJkwQ"
}
```

---

## 7. GET STOCK API

Same as Send API: `[domain]/API/v1/getstock`, encrypted `payload` with `BrandProductCode` and `Denomination`.

---

## 8. STORE LIST API

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/getstorelist` |
| **Method** | `POST` |

### Body

| Name | Type | Mandatory |
|------|------|-----------|
| BrandProductCode | string(50) | Optional |
| shop | integer | Optional |

### Sample request

```json
{
  "BrandProductCode": "WestsidemFqa2lBrMlsl87jO",
  "shop": ""
}
```

### Sample success response

```json
{
  "status": "success",
  "data": [
    {
      "brand_id": "28",
      "brand_name": "Westside",
      "brand_status": "Y",
      "shop_details": [
        {
          "shop_id": "16907",
          "shop_name": "Westside - Iscon Mall Ahmedaba",
          "shop_guid": "f251437b-3ee3-40f9-8971-4054cfaca174",
          "shop_code": "W029",
          "address": "",
          "city": "",
          "state": "",
          "shop_contact": "",
          "shop_status": "A"
        }
      ]
    }
  ],
  "desc": "Process successfully completed",
  "code": "0000"
}
```

---

## 9. Error codes (Pull – vendor list)

| Code | Description |
|------|-------------|
| 0000 | Success |
| ER031 | No data for send-voucher external order id (vendor wording) |
| ER061 | External Order ID not exists / not found |
| ER001 / ER002 | Wrong credentials |
| ER003 | Valid GUID for buyer |
| ER006 / ER007 | Product GUID issues |
| ER010 | External OrderID missing |
| ER022 | Quantity must be numeric |
| ER023 | Quantity must be greater than 0 |
| ER024 | Unauthorized |
| ER025 | Inactive client |
| ER032 | Inactive product |
| ER041 | Product not available |
| ER047 / ER1006 | Vouchers not available |
| ER057 | IP not whitelisted |
| ER059 | Max quantity per product |
| ER062 | External ID / GUID mapping error |
| ER076 / ER077 | Invalid BrandProductCode / denomination |
| ER079 | External order id mismatch |
| ER080 | Multiple products |
| ER082 / ER083 | Decrypt / invalid token |
| ER1000 | Unexpected error |
| ER1056 | Contact customer service |
| ER1057 | Quantity not available for duration |
| ER1011 | Generic / validation errors |
| EROIP | Order already in process |
| 1007 | Invalid JSON |
| 1018 | Unauthorized API |
| 1043 | Valid ExternalOrderId required |
| 1048 | Processing error |
| 1063 | Product/brand not available |
| 1084 | Invalid payload |

---

## 10. Node.js AES-256-CBC (vendor sample)

```javascript
const crypto = require('crypto');

const encryptPiDataNew = (data, key, iv) => {
  try {
    if (typeof data == 'object') {
      data = JSON.stringify(data);
    }
    const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
    let encrypted = cipher.update(data);
    encrypted = Buffer.concat([encrypted, cipher.final()]);
    return encrypted.toString('base64');
  } catch (e) {
    console.log({ encryptPiDataNew: e });
    return data;
  }
};

const decryptPiDataNew = (data, key, iv) => {
  try {
    const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
    const decrypted = decipher.update(data, 'base64');
    return Buffer.concat([decrypted, decipher.final()]).toString();
  } catch (e) {
    console.log({ decryptPiDataNew: e });
    return data;
  }
};
```

---

## Application integration (AmazePays admin)

This document describes the **vendor** Pull API. In the app:

- **Fetch brands** in **Panel → Vouchagram → Brands** (Pull mode) persists a **snapshot** in **`vouchagram_catalog_snapshots`** / **`vouchagram_catalog_snapshot_items`**. Snapshots are not the same as **`products`**.
- To populate **`products`** for the B2B admin catalog (`catalog_audience` **b2b**, `source_provider` = `vouchagram_pull`; new rows default to **hidden** on the public storefront): run **Catalog sync** in Pull mode, `php artisan vouchagram:sync-catalog --mode=pull`, or **import from snapshot** after a fetch.
- See [VOUCHER_PROVIDERS.md](./VOUCHER_PROVIDERS.md) §12.

---

## Related project docs

- [VOUCHAGRAM_SEND_API.md](./VOUCHAGRAM_SEND_API.md) – Send (B2C) API  
- [VOUCHER_PROVIDERS.md](./VOUCHER_PROVIDERS.md) – App integration overview  
