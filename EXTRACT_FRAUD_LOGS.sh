#!/bin/bash

# =====================================================
# FRAUD LOG EXTRACTION SCRIPT
# =====================================================
# Use these commands to extract fraud-related logs
# from large Laravel log files in production
# =====================================================

# SET VARIABLES (Adjust these based on your needs)
LOG_FILE="/path/to/storage/logs/laravel.log"  # Change to your log file path
OUTPUT_FILE="fraud_logs_$(date +%Y%m%d_%H%M%S).log"
DATE_START="2026-01-01"  # Start date (YYYY-MM-DD)
DATE_END="2026-01-31"    # End date (YYYY-MM-DD)

# =====================================================
# METHOD 1: Extract Logs for Specific Order IDs
# =====================================================
# Replace ORDER_ID_1, ORDER_ID_2 with actual order IDs
# =====================================================

extract_by_order_ids() {
    echo "Extracting logs for specific order IDs..."
    grep -E "order_id.*(ORDER_ID_1|ORDER_ID_2|ORDER_ID_3)" "$LOG_FILE" > "fraud_orders_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_orders_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 2: Extract Logs with Amount Discrepancies
# =====================================================
# Searches for patterns indicating fraud
# =====================================================

extract_amount_mismatches() {
    echo "Extracting logs with amount discrepancies..."
    grep -E "(amount_payable_after_discount|grand_payable_amount|Amount.*difference|discrepancy|fraud|suspicious)" "$LOG_FILE" \
        | grep -v "DEBUG" \
        > "fraud_amounts_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_amounts_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 3: Extract Payment-Related Logs
# =====================================================
# Filters payment processing, callbacks, and status changes
# =====================================================

extract_payment_logs() {
    echo "Extracting payment-related logs..."
    grep -E "(payment|Payment|PAYMENT|upi|UPI|unlimit|Unlimit|callback|Callback|status.*success|status.*paid)" "$LOG_FILE" \
        | grep -E "($DATE_START|$DATE_END)" \
        > "fraud_payments_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_payments_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 4: Extract Logs by Date Range
# =====================================================
# Filters logs between specific dates
# =====================================================

extract_by_date_range() {
    echo "Extracting logs for date range: $DATE_START to $DATE_END..."
    awk -v start="$DATE_START" -v end="$DATE_END" '
        /^\[[0-9]{4}-[0-9]{2}-[0-9]{2}/ {
            log_date = substr($0, 2, 10)
            if (log_date >= start && log_date <= end) {
                print_flag = 1
            } else {
                print_flag = 0
            }
        }
        print_flag == 1 { print }
    ' "$LOG_FILE" > "fraud_daterange_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_daterange_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 5: Extract Logs for Specific User IDs
# =====================================================
# Replace USER_ID_1, USER_ID_2 with actual user IDs
# =====================================================

extract_by_user_ids() {
    echo "Extracting logs for specific user IDs..."
    grep -E "user_id.*(USER_ID_1|USER_ID_2|USER_ID_3)" "$LOG_FILE" > "fraud_users_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_users_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 6: Extract Error and Warning Logs
# =====================================================
# Focuses on ERROR and WARNING level logs
# =====================================================

extract_errors_warnings() {
    echo "Extracting ERROR and WARNING logs..."
    grep -E "\.(ERROR|WARNING)" "$LOG_FILE" \
        | grep -E "($DATE_START|$DATE_END)" \
        > "fraud_errors_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_errors_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 7: Extract Woohoo Order Creation Logs
# =====================================================
# Filters Woohoo API calls and order creation
# =====================================================

extract_woohoo_logs() {
    echo "Extracting Woohoo-related logs..."
    grep -E "(woohoo|Woohoo|WOOHOO|order.*created|card.*issued)" "$LOG_FILE" \
        | grep -E "($DATE_START|$DATE_END)" \
        > "fraud_woohoo_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_woohoo_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 8: Extract Complete Transaction Flow
# =====================================================
# Extracts logs for orders that show complete transaction flow
# =====================================================

extract_transaction_flow() {
    echo "Extracting complete transaction flow logs..."
    grep -E "(Order.*created|Payment.*initiated|Payment.*success|Woohoo.*order|Card.*issued)" "$LOG_FILE" \
        | grep -E "($DATE_START|$DATE_END)" \
        > "fraud_transactions_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_transactions_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 9: Extract Logs with IP Addresses
# =====================================================
# Useful for tracking fraudster's IP addresses
# =====================================================

extract_ip_addresses() {
    echo "Extracting logs with IP addresses..."
    grep -E "ip_address|IP.*address|client.*ip" "$LOG_FILE" \
        | grep -E "($DATE_START|$DATE_END)" \
        > "fraud_ips_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_ips_${OUTPUT_FILE}"
}

# =====================================================
# METHOD 10: Combined Fraud Pattern Search
# =====================================================
# Searches for multiple fraud indicators at once
# =====================================================

extract_combined_fraud() {
    echo "Extracting combined fraud pattern logs..."
    grep -E "(order_id|user_id|amount|payment|woohoo)" "$LOG_FILE" \
        | grep -E "($DATE_START|$DATE_END)" \
        | grep -v "DEBUG" \
        | grep -E "(ERROR|WARNING|INFO)" \
        > "fraud_combined_${OUTPUT_FILE}"
    echo "✅ Logs saved to: fraud_combined_${OUTPUT_FILE}"
}

# =====================================================
# QUICK COMMANDS (Run these directly in terminal)
# =====================================================

# 1. Find logs for specific order ID
# grep "order_id.*2654" storage/logs/laravel.log > fraud_order_2654.log

# 2. Find logs with amount discrepancies
# grep -E "amount_payable_after_discount|grand_payable_amount" storage/logs/laravel.log | grep -v DEBUG > fraud_amounts.log

# 3. Find payment-related logs for specific date
# grep "2026-01-12" storage/logs/laravel.log | grep -E "payment|Payment" > fraud_payment_2026-01-12.log

# 4. Find ERROR logs for fraud period
# grep "\.ERROR" storage/logs/laravel.log | grep "2026-01" > fraud_errors_jan.log

# 5. Find logs containing specific user email
# grep "user_email.*@gmail.com" storage/logs/laravel.log > fraud_user.log

# 6. Extract logs between two timestamps
# awk '/2026-01-12 00:00:00/,/2026-01-12 23:59:59/' storage/logs/laravel.log > fraud_day.log

# 7. Find Woohoo order creation logs
# grep -E "woohoo_order_id|Woohoo.*order" storage/logs/laravel.log | grep "2026-01" > fraud_woohoo.log

# 8. Find logs with IP addresses for specific date
# grep "2026-01-12" storage/logs/laravel.log | grep -E "ip_address|IP" > fraud_ips.log

# 9. Count suspicious transactions in logs
# grep -c "amount.*difference\|discrepancy\|fraud" storage/logs/laravel.log

# 10. Extract complete order flow for specific order
# grep -E "order_id.*2654|Order.*2654" storage/logs/laravel.log > fraud_order_2654_complete.log

# =====================================================
# ADVANCED: Extract Logs with Context
# =====================================================
# Shows 5 lines before and after each match
# =====================================================

extract_with_context() {
    ORDER_ID="2654"  # Change this
    echo "Extracting logs with context for order ID: $ORDER_ID..."
    grep -B 5 -A 5 "order_id.*$ORDER_ID" "$LOG_FILE" > "fraud_context_${ORDER_ID}.log"
    echo "✅ Logs with context saved to: fraud_context_${ORDER_ID}.log"
}

# =====================================================
# ADVANCED: Extract and Compress Large Logs
# =====================================================
# Useful when extracted logs are still large
# =====================================================

extract_and_compress() {
    echo "Extracting and compressing logs..."
    grep -E "(order_id|payment|amount|woohoo)" "$LOG_FILE" \
        | grep -E "($DATE_START|$DATE_END)" \
        | gzip > "fraud_logs_compressed_${OUTPUT_FILE}.gz"
    echo "✅ Compressed logs saved to: fraud_logs_compressed_${OUTPUT_FILE}.gz"
}

# =====================================================
# MAIN EXECUTION
# =====================================================
# Uncomment the method you want to use:
# =====================================================

# extract_by_order_ids
# extract_amount_mismatches
# extract_payment_logs
# extract_by_date_range
# extract_by_user_ids
# extract_errors_warnings
# extract_woohoo_logs
# extract_transaction_flow
# extract_ip_addresses
# extract_combined_fraud
# extract_with_context
# extract_and_compress

echo "Script ready. Uncomment the method you want to use and run: bash EXTRACT_FRAUD_LOGS.sh"
