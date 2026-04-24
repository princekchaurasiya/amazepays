# Fraud Log Extraction Commands - Quick Reference

**Use these commands directly in production to extract fraud-related logs from large log files.**

---

## 🚀 **QUICK START - Most Useful Commands**

### **1. Extract Logs for Specific Order ID**
```bash
# Replace 2654 with your actual order ID
grep "order_id.*2654\|Order.*2654\|order_id: 2654" storage/logs/laravel.log > fraud_order_2654.log
```

### **2. Extract Logs for Multiple Order IDs**
```bash
# Replace with your actual order IDs
grep -E "order_id.*(2654|2655|2656)" storage/logs/laravel.log > fraud_orders.log
```

### **3. Extract Payment-Related Logs for Specific Date**
```bash
# Replace date as needed
grep "2026-01-12" storage/logs/laravel.log | grep -E "payment|Payment|PAYMENT" > fraud_payment_2026-01-12.log
```

### **4. Extract ERROR and WARNING Logs Only**
```bash
# Filters only ERROR and WARNING level logs (reduces size significantly)
grep -E "\.(ERROR|WARNING)" storage/logs/laravel.log | grep "2026-01" > fraud_errors_jan.log
```

### **5. Extract Logs with Amount Information**
```bash
# Finds logs mentioning amounts (payment amounts, discrepancies)
grep -E "amount_payable_after_discount|grand_payable_amount|Amount.*difference" storage/logs/laravel.log | grep "2026-01" > fraud_amounts.log
```

---

## 📅 **DATE-BASED EXTRACTION**

### **Extract Logs for Specific Date**
```bash
grep "2026-01-12" storage/logs/laravel.log > fraud_2026-01-12.log
```

### **Extract Logs for Date Range**
```bash
# Using awk for date range (more efficient for large files)
awk '/2026-01-12 00:00:00/,/2026-01-31 23:59:59/' storage/logs/laravel.log > fraud_jan_range.log
```

### **Extract Logs for Last 7 Days**
```bash
# Adjust date as needed
grep -E "2026-01-(0[5-9]|1[0-2]|2[0-9]|3[0-1])" storage/logs/laravel.log > fraud_last_week.log
```

---

## 🔍 **PATTERN-BASED SEARCH**

### **Find Woohoo Order Creation Logs**
```bash
grep -E "woohoo_order_id|Woohoo.*order|order.*created.*successfully" storage/logs/laravel.log | grep "2026-01" > fraud_woohoo.log
```

### **Find Payment Status Changes**
```bash
grep -E "status.*(success|paid|approved|confirmed|failed)" storage/logs/laravel.log | grep "2026-01" > fraud_status_changes.log
```

### **Find Logs with IP Addresses**
```bash
grep -E "ip_address|IP.*address" storage/logs/laravel.log | grep "2026-01" > fraud_ips.log
```

### **Find User-Related Logs**
```bash
# Replace user_id or email
grep -E "user_id.*92|email.*@gmail.com" storage/logs/laravel.log | grep "2026-01" > fraud_user.log
```

---

## 🎯 **COMBINED SEARCHES (Most Effective)**

### **Extract Complete Transaction Flow for Order**
```bash
# Shows order creation → payment → Woohoo order → card issuance
grep -E "order_id.*2654|Order.*2654|Payment.*2654|Woohoo.*2654" storage/logs/laravel.log > fraud_order_2654_complete.log
```

### **Extract All Fraud Indicators at Once**
```bash
# Searches for multiple fraud patterns
grep -E "order_id|user_id|amount|payment|woohoo" storage/logs/laravel.log \
  | grep -E "2026-01" \
  | grep -v "DEBUG" \
  | grep -E "(ERROR|WARNING|INFO)" > fraud_combined.log
```

### **Extract Logs with Context (5 lines before/after)**
```bash
# Useful to see what happened before and after fraud transaction
grep -B 5 -A 5 "order_id.*2654" storage/logs/laravel.log > fraud_order_2654_context.log
```

---

## 📊 **ANALYSIS COMMANDS**

### **Count Suspicious Transactions**
```bash
# Count how many times fraud patterns appear
grep -c "amount.*difference\|discrepancy\|fraud" storage/logs/laravel.log
```

### **List All Unique Order IDs in Logs**
```bash
# Extract all order IDs mentioned in logs
grep -oE "order_id[^,}]*[0-9]+" storage/logs/laravel.log | sort -u > fraud_order_ids.txt
```

### **Find Orders with Amount Mismatches**
```bash
# Searches for logs indicating amount discrepancies
grep -E "amount_payable_after_discount.*[0-9]+|grand_payable_amount.*[0-9]+" storage/logs/laravel.log \
  | grep -E "2026-01" \
  | grep -v "DEBUG" > fraud_amount_mismatches.log
```

---

## 💾 **EFFICIENT EXTRACTION FOR LARGE FILES**

### **Extract and Compress in One Step**
```bash
# Compresses output to save space
grep -E "order_id|payment|amount|woohoo" storage/logs/laravel.log \
  | grep "2026-01" \
  | gzip > fraud_logs_compressed.log.gz

# To read compressed file:
zcat fraud_logs_compressed.log.gz | less
```

### **Extract Only Last N Lines (Most Recent)**
```bash
# Get last 10000 lines (most recent logs)
tail -n 10000 storage/logs/laravel.log | grep -E "order_id|payment" > fraud_recent.log
```

### **Extract and Remove Duplicates**
```bash
# Removes duplicate log entries
grep -E "order_id|payment" storage/logs/laravel.log | sort -u > fraud_unique.log
```

---

## 🔐 **PRODUCTION-SAFE COMMANDS**

### **Read-Only Operations (Safe)**
All commands above are **read-only** - they don't modify the original log file.

### **Check Log File Size First**
```bash
# Check log file size before extraction
ls -lh storage/logs/laravel.log

# Check how many lines
wc -l storage/logs/laravel.log
```

### **Test Command on Small Sample First**
```bash
# Test on first 1000 lines
head -n 1000 storage/logs/laravel.log | grep "order_id" > test_sample.log
```

---

## 📋 **STEP-BY-STEP WORKFLOW**

### **Step 1: Identify Fraud Order IDs from Database**
```bash
# Run SQL query first to get order IDs (use FRAUD_EVIDENCE_QUERIES.sql)
# Then use those order IDs in log extraction
```

### **Step 2: Extract Logs for Those Order IDs**
```bash
# Replace with actual order IDs from database
grep -E "order_id.*(2654|2655|2656)" storage/logs/laravel.log > fraud_orders.log
```

### **Step 3: Extract Payment Logs for Same Period**
```bash
# Extract payment-related logs for the date range
grep -E "2026-01-(0[1-9]|1[0-2]|2[0-9]|3[0-1])" storage/logs/laravel.log \
  | grep -E "payment|Payment" > fraud_payments.log
```

### **Step 4: Extract ERROR/WARNING Logs**
```bash
# Get error logs that might show fraud attempts
grep -E "\.(ERROR|WARNING)" storage/logs/laravel.log \
  | grep -E "2026-01" > fraud_errors.log
```

### **Step 5: Combine All Extracted Logs**
```bash
# Combine all extracted logs into one file
cat fraud_orders.log fraud_payments.log fraud_errors.log > fraud_complete.log

# Or if files are large, compress them
cat fraud_orders.log fraud_payments.log fraud_errors.log | gzip > fraud_complete.log.gz
```

---

## 🎯 **MOST USEFUL SINGLE COMMAND**

**If you only want ONE command to extract everything fraud-related:**

```bash
# This extracts all fraud-related logs for January 2026
grep -E "2026-01" storage/logs/laravel.log \
  | grep -E "order_id|user_id|amount|payment|woohoo|ERROR|WARNING" \
  | grep -v "DEBUG" \
  | gzip > fraud_all_jan_2026.log.gz
```

**To read the compressed file:**
```bash
zcat fraud_all_jan_2026.log.gz | less
# or
zcat fraud_all_jan_2026.log.gz | grep "order_id.*2654"
```

---

## 📝 **EXPORT FOR POLICE COMPLAINT**

### **Create Final Evidence Log File**
```bash
# Extract all relevant logs and create a clean evidence file
grep -E "2026-01" storage/logs/laravel.log \
  | grep -E "order_id|user_id|amount|payment|woohoo|ip_address" \
  | grep -v "DEBUG" \
  | sort -u \
  > fraud_evidence_for_police.log

# Count lines to verify
wc -l fraud_evidence_for_police.log
```

---

## ⚠️ **IMPORTANT NOTES**

1. **Always test on a small sample first** (use `head -n 1000`)
2. **Check disk space** before extracting large files
3. **Use compression** (`gzip`) for large extractions
4. **Keep original log file untouched** (all commands are read-only)
5. **Run during low-traffic hours** if possible (for large files)
6. **Verify extracted files** before deleting or archiving

---

## 🔧 **TROUBLESHOOTING**

### **If Command Takes Too Long:**
```bash
# Use more specific patterns
# Add date filter first
# Use awk instead of grep for very large files
```

### **If Output File is Still Too Large:**
```bash
# Filter more aggressively
grep -E "ERROR|WARNING" storage/logs/laravel.log \
  | grep -E "order_id|payment" \
  | grep "2026-01" \
  | gzip > fraud_filtered.log.gz
```

### **If You Need Specific Time Range:**
```bash
# Extract logs for specific hour
grep "2026-01-12 14:" storage/logs/laravel.log > fraud_2026-01-12_14h.log
```

---

**Last Updated:** [Date]  
**For:** Production Log Extraction - Fraud Investigation
