# Admin Log Viewer Guide - Viewing Fraud Logs

Your application uses **`rap2hpoutre/laravel-log-viewer`** package which provides a web-based interface to view logs.

---

## 🔗 **Access the Log Viewer**

### **URL:**
```
http://your-domain.com/admin/cactus/logs
```

**Or if using Voyager admin panel:**
- Navigate to: **Admin Panel → Logs** (if menu item exists)
- Direct URL: `/admin/cactus/logs`

---

## 🔐 **Access Requirements**

The log viewer is protected by the `admin.user` middleware, so you need to:
1. Be logged in as an admin user
2. Have admin privileges

---

## 📋 **How to View Fraud Logs for January 6, 2026**

### **Method 1: Using the Web Interface**

1. **Login to Admin Panel**
   - Go to `/admin/login`
   - Login with admin credentials

2. **Navigate to Log Viewer**
   - Go to `/admin/cactus/logs`
   - You'll see a list of log files

3. **Select the Log File**
   - Click on `laravel.log` (or the current log file)
   - The log viewer will display the logs

4. **Filter by Date**
   - The log viewer interface allows you to:
     - Scroll through logs
     - Search for specific text
     - Filter by log level (ERROR, WARNING, INFO, etc.)

5. **Search for January 6, 2026**
   - Use the search/filter box in the log viewer
   - Search for: `2026-01-06`
   - Or search for specific order IDs, payment amounts, etc.

### **Method 2: Direct URL with Filters**

The log viewer package supports URL parameters for filtering:

```
/admin/cactus/logs?file=laravel.log&level=error
/admin/cactus/logs?file=laravel.log&search=2026-01-06
/admin/cactus/logs?file=laravel.log&level=error&search=order_id
```

---

## 🔍 **Search Tips for Fraud Investigation**

### **1. Search for Specific Date**
```
Search: 2026-01-06
```

### **2. Search for Order IDs**
```
Search: order_id.*2654
```

### **3. Search for Payment-Related**
```
Search: payment|Payment|PAYMENT
```

### **4. Search for Amount Discrepancies**
```
Search: amount_payable_after_discount|grand_payable_amount
```

### **5. Search for Woohoo Orders**
```
Search: woohoo_order_id|Woohoo.*order
```

### **6. Search for Errors Only**
- Use the log level filter: Select **ERROR** from dropdown
- Then search for: `2026-01-06`

### **7. Combined Search**
```
Search: 2026-01-06.*order_id.*payment
```

---

## 📊 **Features of the Log Viewer**

The `rap2hpoutre/laravel-log-viewer` package provides:

1. **Log File Selection**
   - View different log files
   - Switch between daily log files (if configured)

2. **Log Level Filtering**
   - Filter by: ALL, EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG

3. **Search Functionality**
   - Search within logs
   - Highlight matching text

4. **Pagination**
   - Navigate through large log files
   - Jump to specific pages

5. **Download Logs**
   - Download specific log files
   - Useful for evidence collection

---

## 🎯 **Step-by-Step: Finding Fraud Logs**

### **Step 1: Access Log Viewer**
```
1. Login to admin panel
2. Navigate to: /admin/cactus/logs
3. Select: laravel.log
```

### **Step 2: Filter by Date**
```
1. In search box, type: 2026-01-06
2. Press Enter or click Search
3. All logs for Jan 6 will be displayed
```

### **Step 3: Filter by Log Level**
```
1. Select "ERROR" or "WARNING" from log level dropdown
2. This reduces the number of logs shown
3. Focuses on important entries
```

### **Step 4: Search for Specific Patterns**
```
1. Search for: order_id
2. Then search for: payment
3. Then search for: amount
4. Combine searches to narrow down
```

### **Step 5: Export/Download**
```
1. Use browser's "Save Page" or "Print to PDF"
2. Or use the download feature if available
3. Save for evidence
```

---

## 💡 **Pro Tips**

### **1. Use Browser Search (Ctrl+F / Cmd+F)**
- After loading logs in the viewer, use browser's find function
- Faster than the built-in search for simple text

### **2. Filter by Log Level First**
- Select ERROR or WARNING first
- Then search for date
- Reduces irrelevant logs

### **3. Search for Multiple Terms**
- Search: `2026-01-06.*order_id.*payment`
- Use regex patterns if supported

### **4. Take Screenshots**
- Screenshot important log entries
- Useful for police complaint evidence

### **5. Download Complete Log File**
- If the viewer allows, download the entire log file
- Then use command-line tools for deeper analysis

---

## 🔧 **If Log Viewer Doesn't Show Old Logs**

The log viewer might only show recent logs. If you need logs from January 6, 2026:

### **Option 1: Check Log File Rotation**
- Laravel may rotate logs daily
- Look for: `laravel-2026-01-06.log`
- Or check: `storage/logs/` directory

### **Option 2: Use Command Line**
- If web viewer doesn't show old logs, use command line:
```bash
grep "2026-01-06" storage/logs/laravel-2026-01-06.log > fraud_jan6.log
```

### **Option 3: Check Log Configuration**
- Check `config/logging.php` for log rotation settings
- Daily logs might be stored separately

---

## 📝 **Exporting Logs for Police Complaint**

### **Method 1: From Web Interface**
1. Navigate to log viewer
2. Filter/search for fraud logs
3. Use browser's "Print to PDF" or "Save Page"
4. Save as evidence

### **Method 2: Download Log File**
1. If download option available, download `laravel-2026-01-06.log`
2. Then extract fraud-related entries using command line:
```bash
grep -E "order_id|payment|amount|woohoo" laravel-2026-01-06.log > fraud_evidence.log
```

### **Method 3: Screenshot Important Entries**
1. Search for specific order IDs
2. Take screenshots of relevant log entries
3. Include in police complaint evidence

---

## 🚨 **Security Note**

The log viewer is protected by admin middleware, but:
- **Don't share admin credentials**
- **Limit access to trusted admins only**
- **Logs may contain sensitive information**
- **Consider IP whitelisting for log viewer route**

---

## 🔗 **Package Documentation**

For more details, see:
- GitHub: https://github.com/rap2hpoutre/laravel-log-viewer
- Package version in your project: `^2.4`

---

## ✅ **Quick Checklist**

- [ ] Login to admin panel
- [ ] Navigate to `/admin/cactus/logs`
- [ ] Select `laravel.log` (or `laravel-2026-01-06.log`)
- [ ] Search for: `2026-01-06`
- [ ] Filter by ERROR/WARNING level
- [ ] Search for specific order IDs
- [ ] Search for payment-related entries
- [ ] Take screenshots or export logs
- [ ] Save evidence for police complaint

---

**The log viewer makes it easy to browse and search logs without needing command-line access!**
