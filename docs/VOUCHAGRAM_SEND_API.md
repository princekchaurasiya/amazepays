# Vouchagram Communication Engine – Send Voucher API

**Version:** 1.1.0 (per vendor documentation, Sept 2025)  
**Classification (vendor):** Internal use / shareable for integrations  

This file is a Markdown reference derived from the vendor PDF. All requests must use **HTTPS**. The integration domain is provided by Vouchagram.

---

## Revision history (vendor)

| Date | Author        | Version | Summary |
|------------|---------------|---------|---------|
| 23 Jun 2024 | Brijesh Kumar | 1.0.0   | Initial draft |
| 29 Sept 2025 | Akash Singh | 1.1.0   | Reformatted; check status API; error list updates |

---

## 1. Copyright & confidentiality (vendor)

All rights reserved by Vouchagram group of companies. Do not reproduce or distribute without permission. Receiving parties must protect confidential information and limit disclosure to personnel bound by confidentiality obligations.

---

## 2. Encryption and decryption

1. Encrypt the JSON request body before sending it to the API (where `payload` is required).
2. Decrypt the `data` field in the response to obtain JSON (or a JWT string for token responses).

The application implements AES-256-CBC helpers in `App\Services\Voucher\VouchagramService` (see also **§11** below for Node examples).

---

## 3. GET TOKEN API

**Purpose:** Obtain a JWT used on all subsequent calls. Token lifetime ~**30 minutes**. Reuse until expiry, then call again.

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/gettoken` |
| **Method** | `GET` |

### Headers

| Name | Type | Mandatory | Example |
|------|------|-----------|---------|
| Content-Type | string | Yes | `application/json` |
| username | string | Yes | Provided by Vouchagram |
| password | string | Yes | Provided by Vouchagram |

### Sample request

```http
GET [domain]/API/v1/gettoken HTTP/1.1
Content-Type: application/json
username: <provided-username>
password: <provided-password>
```

### Sample response (encrypted `data`)

```json
{
  "status": "success",
  "data": "<base64 AES ciphertext>",
  "desc": "Process successfully completed",
  "code": "0000"
}
```

After decryption, `data` is typically a JWT string, e.g. `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`.

---

## 4. GET BRANDS API

**Purpose:** List brands or fetch one brand by `BrandProductCode`.

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/getbrands` |
| **Method** | `POST` |

### Headers

| Name | Type | Mandatory |
|------|------|-----------|
| Content-Type | string | Yes – `application/json` |
| token | string | Yes – decrypted JWT from GET TOKEN |

### Body

| Name | Type | Mandatory | Description |
|------|------|-----------|-------------|
| BrandProductCode | string (≤100) | No | Alphanumeric; empty string `""` returns all brands |

### Specific brand request

```json
{
  "BrandProductCode": "Bata4xfRrUnT46Uv4iol"
}
```

### All brands request

```json
{
  "BrandProductCode": ""
}
```

### Decrypted `data` (example – single brand array element)

```json
[
  {
    "BrandProductCode": "Bata4xfRrUnT46Uv4iol",
    "BrandName": "Bata",
    "Brandtype": "VOUCHER",
    "RedemptionType": "2",
    "OnlineRedemptionUrl": "https://www.bata.in/",
    "BrandImage": "https://cdn.gyftr.com/...",
    "denominationList": "500",
    "MinValue": null,
    "MaxValue": null,
    "DenomType": "F",
    "stockAvailable": "true",
    "Category": "BATA-API,Food & Beverages,Lifestyle",
    "Descriptions": "...",
    "tnc": "...",
    "importantInstruction": "...",
    "redeemSteps": {
      "1": { "text": "...", "image": "https://..." }
    },
    "updated_at": "2024-07-17T09:41:20.000Z",
    "EpayMinValue": 10,
    "EpayMaxValue": 1050,
    "ServiceType": "E",
    "EpayDiscount": 10
  }
]
```

### Notes (vendor)

- **RedemptionType:** `1` = Online, `2` = Offline, `3` = Both  
- **DenomType:** `F` = Fixed, `D` = Dynamic  
- **ServiceType:** `E` = Epay, `V` = Voucher  

---

## 5. SEND VOUCHER API

**Purpose:** Deliver vouchers to a customer for a given brand and denomination.

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/sendvoucher` |
| **Method** | `POST` |

### Headers

| Name | Type | Mandatory |
|------|------|-----------|
| Content-Type | string | Yes |
| token | string | Yes – decrypted JWT |

### Body

| Name | Type | Mandatory | Description |
|------|------|-----------|-------------|
| payload | string | Yes | Entire inner JSON encrypted as one value |

### Inner JSON (decrypted payload) – fields

| Name | Type | Required | Description |
|------|------|----------|-------------|
| BrandProductCode | string(100) | Yes | Brand identifier |
| ExternalOrderId | string(50) | Yes | Unique client order id |
| Quantity | number | Yes | Max recommended: **10** |
| Denomination | number | Yes | Denomination value |
| CustomerFName | string(50) | Yes | First name |
| CustomerMName | string(50) | No | Middle name |
| CustomerLName | string(50) | No | Last name |
| CommunicationMode | number | Yes | Provided by Vouchagram |
| EmailTo | string(150) | Yes | Email |
| EmailSubject | string(100) | No | Subject |
| MobileNo | string(10) | Yes | Mobile |
| TemplateId | number | Yes | Provided by Vouchagram |
| ServiceType | enum | Yes | `E` (Epay) or `V` (Voucher) |
| DynamicVars | object | Optional | `{}` or `{ "key": "value" }` |

### Decrypted payload example

```json
{
  "ExternalOrderId": "TEST_stag_29092025_00001",
  "BrandProductCode": "internalfixedtestingePvroJd8rW3OJkwQ",
  "Denomination": "1000",
  "Quantity": "1",
  "MobileNo": "87*****71",
  "EmailTo": "a******@gmail.com",
  "EmailSubject": "",
  "CommunicationMode": "4",
  "TemplateId": "198",
  "CustomerFName": "John",
  "CustomerLName": "Wick",
  "CustomerMName": "",
  "ServiceType": "V",
  "DynamicVars": {}
}
```

### Outer request example

```json
{
  "payload": "<base64 encrypted JSON>"
}
```

### Decrypted success `data` example

```json
{
  "service_type": "V",
  "reference_num": "19g6img4yjlrz",
  "external_order_id": "TEST_stag_29092025_00001",
  "brand_details": [
    {
      "product_name": "internal_fixed_testing",
      "voucher_name": "internal_fixed_testing",
      "items": [
        {
          "end_date": "31 Dec 2025",
          "value": "1000.00",
          "unique_id": "e15398e1-bde1-44af-bdfb-85cc978765f4",
          "voucher_no": "###116244",
          "voucher_pin": "",
          "email_status": "pending",
          "sms_status": "pending",
          "whatsapp_status": "pending"
        }
      ]
    }
  ]
}
```

---

## 6. CHECK STATUS API (send voucher)

**Purpose:** Check status of a **send voucher** request.

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/checkvoucherstatus` |
| **Method** | `POST` |

### Body

| Name | Type | Mandatory |
|------|------|-----------|
| payload | string | Yes – encrypted JSON |

### Inner JSON (decrypted)

```json
{
  "sv_ex_order_id": "TEST_stag_29092025_00002"
}
```

### Decrypted success `data` (illustrative)

```json
{
  "service_type": "V",
  "reference_num": "19g6img4yyff5",
  "external_order_id": "TEST_stag_29092025_00002",
  "brand_details": [
    {
      "product_name": "Test_Brand_Fixed_Promo_code",
      "items": [
        {
          "end_date": "31 Dec 2025",
          "value": "100.00",
          "unique_id": "134709d1-8aa4-4e6d-b1ac-e4895ac70736",
          "voucher_no": "######147525",
          "voucher_pin": "",
          "voucher_status": "Valid",
          "email_status": "pending",
          "sms_status": "success",
          "whatsapp_status": "failed"
        }
      ]
    }
  ]
}
```

---

## 7. GET STOCK API

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/getstock` |
| **Method** | `POST` |

Encrypted `payload` inner JSON example:

```json
{
  "BrandProductCode": "BenettonRwJ6cqVWqPPMLlBH",
  "Denomination": "5000"
}
```

Decrypted `data` example:

```json
{
  "AvailableQuantity": "23",
  "BrandName": "Benetton"
}
```

---

## 8. STORE LIST API

| Item | Value |
|------|--------|
| **Endpoint** | `[domain]/API/v1/getstorelist` |
| **Method** | `POST` |

### Body (plaintext JSON per vendor doc)

| Name | Type | Mandatory |
|------|------|-----------|
| BrandProductCode | string(50) | Optional |
| shop | string(10) | Optional |

### Sample request

```json
{
  "BrandProductCode": "WestsidemFqa2lBrMlsl87jO",
  "shop": ""
}
```

### Sample response (success)

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
          "address": " sec- 65 b- 76",
          "city": " ADILABAD",
          "state": " ANDHRA PRADESH",
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

## 9. Error codes (Send – vendor list)

| Code | Description |
|------|-------------|
| 0000 | Process successfully completed |
| ER048 | Order id already processed (duplicate) |
| ER031 | No data for given send voucher external order id |
| ER061 | External Order ID not exists |
| ER001 / ER002 | Wrong credentials |
| ER003 | Valid GUID required for buyer |
| ER006 | Product GUID mandatory |
| ER007 | Valid GUID required for product |
| ER010 | External OrderID missing |
| ER015 | Incomplete mobile number |
| ER016 | Invalid email |
| ER022 | Quantity must be numeric |
| ER024 | Unauthorized access |
| ER025 | Inactive client |
| ER032 | Inactive product / order in process (context-specific) |
| ER041 | Product not available |
| ER042 | Template not mapped / invalid field values (many variants) |
| ER047 / ER1006 | Vouchers not available for blast |
| ER057 | IP not whitelisted |
| ER076 | Invalid BrandProductCode |
| ER077 | Invalid denomination |
| ER079 | External order id and prior request data mismatch |
| ER080 | Multiple products found |
| ER082 | Decrypt error |
| ER083 | Invalid token |
| ER1011 / ER1012 | Validation / insufficient balance / generic processing |
| ER1057 | Requested quantity not available for duration |
| EROIP | Order/request already in process |
| 1007 | Invalid JSON |
| 1018 | Unauthorized API access |
| 1048 / 1051 | Processing / timeout errors |
| 1063–1065, 1084 | Product/brand/denomination/epay/payload errors |

*(Vendor PDF contains duplicate codes with different messages; treat `code` + `desc` together in live responses.)*

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

**Note:** Key and IV are supplied by Vouchagram per environment (`send` vs `pull` modes may use different credentials in this codebase—see `config` / `VouchagramService`).

---

## Related project docs

- [VOUCHAGRAM_PULL_API.md](./VOUCHAGRAM_PULL_API.md) – Pull (B2B) API  
- [VOUCHER_PROVIDERS.md](./VOUCHER_PROVIDERS.md) – App integration overview  
