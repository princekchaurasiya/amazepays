# Quick Log Extraction - January 6, 2026 Fraud

**Ready-to-use commands to extract fraud logs for January 6, 2026**

---

## 🚀 **MOST USEFUL COMMANDS (Copy & Paste)**

### **1. Extract ALL logs for January 6, 2026**
```bash
grep "2026-01-06" storage/logs/laravel.log > fraud_jan6_all.log
```

### **2. Extract ERROR/WARNING logs only (Smaller file)**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -E "\.(ERROR|WARNING)" > fraud_jan6_errors.log
```

### **3. Extract Payment-Related Logs**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -E "payment|Payment|PAYMENT" > fraud_jan6_payments.log
```

### **4. Extract Order-Related Logs**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -E "order_id|Order.*created" > fraud_jan6_orders.log
```

### **5. Extract Amount-Related Logs (Most Important for Fraud)**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -E "amount|Amount|payable|denomination" > fraud_jan6_amounts.log
```

### **6. Extract Woohoo Order Creation Logs**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -E "woohoo|Woohoo|card.*issued" > fraud_jan6_woohoo.log
```

### **7. Extract IP Address Logs**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -E "ip_address|IP.*address" > fraud_jan6_ips.log
```

---

## 🎯 **BEST SINGLE COMMAND (Extract Everything Fraud-Related)**

```bash
# Extracts all fraud-related logs for Jan 6, 2026
grep "2026-01-06" storage/logs/laravel.log \
  | grep -E "order_id|user_id|amount|payment|woohoo|ERROR|WARNING" \
  | grep -v "DEBUG" \
  > fraud_jan6_complete.log
```

**Or compressed version (saves space):**
```bash
grep "2026-01-06" storage/logs/laravel.log \
  | grep -E "order_id|user_id|amount|payment|woohoo|ERROR|WARNING" \
  | grep -v "DEBUG" \
  | gzip > fraud_jan6_complete.log.gz
```

---

## 📋 **STEP-BY-STEP WORKFLOW**

### **Step 1: Check log file size first**
```bash
ls -lh storage/logs/laravel.log
wc -l storage/logs/laravel.log
```

### **Step 2: Extract all logs for Jan 6**
```bash
grep "2026-01-06" storage/logs/laravel.log > fraud_jan6_all.log
```

### **Step 3: Check how many lines extracted**
```bash
wc -l fraud_jan6_all.log
```

### **Step 4: Extract specific patterns (if file is still large)**
```bash
# Extract only errors and warnings
grep -E "\.(ERROR|WARNING)" fraud_jan6_all.log > fraud_jan6_errors.log

# Extract payment-related
grep -E "payment|Payment" fraud_jan6_all.log > fraud_jan6_payments.log

# Extract order-related
grep -E "order_id|Order" fraud_jan6_all.log > fraud_jan6_orders.log
```

### **Step 5: View the extracted logs**
```bash
# View all logs
less fraud_jan6_all.log

# View errors only
less fraud_jan6_errors.log

# Search for specific order ID in logs
grep "order_id.*2654" fraud_jan6_all.log
```

---

## 🔍 **FIND SPECIFIC ORDER ID IN LOGS**

### **If you know the order ID:**
```bash
# Replace 2654 with your actual order ID
grep "2026-01-06" storage/logs/laravel.log | grep "order_id.*2654" > fraud_order_2654_jan6.log
```

### **If you know multiple order IDs:**
```bash
# Replace with actual order IDs
grep "2026-01-06" storage/logs/laravel.log | grep -E "order_id.*(2654|2655|2656)" > fraud_orders_jan6.log
```

---

## 📊 **ANALYSIS COMMANDS**

### **Count how many fraud-related entries**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -c "order_id\|payment\|amount"
```

### **List all unique order IDs from Jan 6**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -oE "order_id[^,}]*[0-9]+" | sort -u > order_ids_jan6.txt
```

### **Find logs with amount discrepancies**
```bash
grep "2026-01-06" storage/logs/laravel.log | grep -E "amount_payable_after_discount|grand_payable_amount" > fraud_amounts_jan6.log
```

---

## 💾 **COMPRESSED VERSION (For Large Files)**

### **Extract and compress in one step**
```bash
grep "2026-01-06" storage/logs/laravel.log \
  | grep -E "order_id|user_id|amount|payment|woohoo|ERROR|WARNING" \
  | grep -v "DEBUG" \
  | gzip > fraud_jan6_compressed.log.gz
```

### **To read compressed file:**
```bash
zcat fraud_jan6_compressed.log.gz | less
# or
zcat fraud_jan6_compressed.log.gz | grep "order_id.*2654"
```

---

## 🎯 **MOST EFFICIENT COMMAND (Recommended)**

**This extracts everything fraud-related for Jan 6, 2026 and compresses it:**

```bash
grep "2026-01-06" storage/logs/laravel.log \
  | grep -E "order_id|user_id|amount|payment|woohoo|ip_address|ERROR|WARNING" \
  | grep -v "DEBUG" \
  | sort -u \
  | gzip > fraud_jan6_evidence.log.gz
```

**Then extract and view:**
```bash
zcat fraud_jan6_evidence.log.gz > fraud_jan6_evidence.log
less fraud_jan6_evidence.log
```

---

## ⚡ **QUICK ONE-LINER (Fastest)**

```bash
grep "2026-01-06" storage/logs/laravel.log | grep -v "DEBUG" | gzip > fraud_jan6.log.gz
```

This extracts all non-DEBUG logs for Jan 6 and compresses them. You can then filter further:
```bash
zcat fraud_jan6.log.gz | grep "order_id\|payment\|amount" > fraud_filtered.log
```

---

## 📝 **FOR POLICE COMPLAINT**

### **Create final evidence file:**
```bash
# Extract all relevant logs for Jan 6, 2026
grep "2026-01-06" storage/logs/laravel.log \
  | grep -E "order_id|user_id|amount|payment|woohoo|ip_address" \
  | grep -v "DEBUG" \
  | sort -u \
  > fraud_evidence_jan6_2026.log

# Count lines
wc -l fraud_evidence_jan6_2026.log

# Compress for submission
gzip fraud_evidence_jan6_2026.log
```

---

## ✅ **VERIFICATION**

After extraction, verify the logs:
```bash
# Check file size
ls -lh fraud_jan6*.log

# Check line count
wc -l fraud_jan6*.log

# Verify date is correct (should only show Jan 6)
head -n 5 fraud_jan6_all.log | grep "2026-01-06"
```

---

## 🚨 **IMPORTANT NOTES**

1. **All commands are read-only** - safe for production
2. **Replace `storage/logs/laravel.log`** with your actual log file path if different
3. **Test on small sample first** if unsure:
   ```bash
   head -n 1000 storage/logs/laravel.log | grep "2026-01-06"
   ```
4. **Check disk space** before extracting large files
5. **Use compression** (`gzip`) to save space

---

**Ready to use! Just copy and paste the commands above.**
