#!/bin/bash

# =====================================================
# FRAUD LOG EXTRACTION - JANUARY 6, 2026
# =====================================================
# Extract fraud-related logs for the specific fraud date
# =====================================================

LOG_FILE="storage/logs/laravel.log"  # Adjust path if needed
FRAUD_DATE="2026-01-06"
OUTPUT_DIR="fraud_logs_jan6_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$OUTPUT_DIR"

echo "🔍 Extracting fraud logs for: $FRAUD_DATE"
echo "📁 Output directory: $OUTPUT_DIR"
echo ""

# =====================================================
# METHOD 1: Extract ALL logs for January 6, 2026
# =====================================================
echo "1️⃣ Extracting all logs for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" > "$OUTPUT_DIR/all_logs_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/all_logs_jan6.log ($(wc -l < "$OUTPUT_DIR/all_logs_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 2: Extract ERROR and WARNING logs only
# =====================================================
echo "2️⃣ Extracting ERROR/WARNING logs for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" | grep -E "\.(ERROR|WARNING)" > "$OUTPUT_DIR/errors_warnings_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/errors_warnings_jan6.log ($(wc -l < "$OUTPUT_DIR/errors_warnings_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 3: Extract Payment-Related Logs
# =====================================================
echo "3️⃣ Extracting payment-related logs for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" | grep -E "(payment|Payment|PAYMENT|upi|UPI|unlimit|Unlimit|callback|Callback)" > "$OUTPUT_DIR/payments_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/payments_jan6.log ($(wc -l < "$OUTPUT_DIR/payments_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 4: Extract Order-Related Logs
# =====================================================
echo "4️⃣ Extracting order-related logs for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" | grep -E "(order_id|Order.*created|order.*status)" > "$OUTPUT_DIR/orders_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/orders_jan6.log ($(wc -l < "$OUTPUT_DIR/orders_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 5: Extract Amount-Related Logs
# =====================================================
echo "5️⃣ Extracting amount-related logs for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" | grep -E "(amount|Amount|AMOUNT|payable|denomination|discount)" > "$OUTPUT_DIR/amounts_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/amounts_jan6.log ($(wc -l < "$OUTPUT_DIR/amounts_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 6: Extract Woohoo-Related Logs
# =====================================================
echo "6️⃣ Extracting Woohoo-related logs for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" | grep -E "(woohoo|Woohoo|WOOHOO|card.*issued|order.*created.*successfully)" > "$OUTPUT_DIR/woohoo_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/woohoo_jan6.log ($(wc -l < "$OUTPUT_DIR/woohoo_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 7: Extract IP Address Logs
# =====================================================
echo "7️⃣ Extracting IP address logs for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" | grep -E "(ip_address|IP.*address|client.*ip)" > "$OUTPUT_DIR/ip_addresses_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/ip_addresses_jan6.log ($(wc -l < "$OUTPUT_DIR/ip_addresses_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 8: Combined Fraud Pattern Search
# =====================================================
echo "8️⃣ Extracting combined fraud patterns for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" \
  | grep -E "(order_id|user_id|amount|payment|woohoo)" \
  | grep -v "DEBUG" \
  | grep -E "(ERROR|WARNING|INFO)" > "$OUTPUT_DIR/fraud_combined_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/fraud_combined_jan6.log ($(wc -l < "$OUTPUT_DIR/fraud_combined_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 9: Extract Complete Transaction Flow
# =====================================================
echo "9️⃣ Extracting complete transaction flow for $FRAUD_DATE..."
grep "$FRAUD_DATE" "$LOG_FILE" \
  | grep -E "(Order.*created|Payment.*initiated|Payment.*success|Woohoo.*order|Card.*issued)" > "$OUTPUT_DIR/transactions_jan6.log"
echo "   ✅ Saved: $OUTPUT_DIR/transactions_jan6.log ($(wc -l < "$OUTPUT_DIR/transactions_jan6.log") lines)"
echo ""

# =====================================================
# METHOD 10: Create Compressed Archive
# =====================================================
echo "🔟 Creating compressed archive..."
cd "$OUTPUT_DIR"
tar -czf "../fraud_logs_jan6_compressed.tar.gz" *.log
cd ..
echo "   ✅ Compressed archive: fraud_logs_jan6_compressed.tar.gz"
echo ""

# =====================================================
# SUMMARY
# =====================================================
echo "=========================================="
echo "✅ EXTRACTION COMPLETE!"
echo "=========================================="
echo "📁 All files saved in: $OUTPUT_DIR"
echo "📦 Compressed archive: fraud_logs_jan6_compressed.tar.gz"
echo ""
echo "📊 File sizes:"
ls -lh "$OUTPUT_DIR"/*.log | awk '{print "   " $9 ": " $5}'
echo ""
echo "💡 To view a file:"
echo "   less $OUTPUT_DIR/all_logs_jan6.log"
echo ""
echo "💡 To extract compressed archive:"
echo "   tar -xzf fraud_logs_jan6_compressed.tar.gz"
