-- =====================================================
-- SQL QUERIES TO EXTRACT FRAUD EVIDENCE
-- =====================================================
-- Use these queries to gather evidence for the police complaint
-- Export results as CSV/Excel for submission
-- =====================================================

-- =====================================================
-- QUERY 1: Find Orders with Payment Amount Mismatch
-- =====================================================
-- This query identifies orders where the paid amount is significantly
-- less than the expected order amount (indicating fraud)
-- =====================================================

SELECT 
    qo.id AS 'Order ID',
    qo.refno AS 'Reference Number',
    qo.user_id AS 'User ID',
    u.name AS 'User Name',
    u.email AS 'User Email',
    u.mobile AS 'User Phone',
    qo.denomination AS 'Denomination',
    qo.quantity AS 'Quantity',
    qo.grand_payable_amount AS 'Expected Total',
    qo.amount_payable_after_discount AS 'Expected After Discount',
    qo.order_status AS 'Order Status',
    qo.created_at AS 'Order Date',
    qo.woohoo_order_id AS 'Woohoo Order ID',
    -- Payment details
    up.id AS 'Payment ID',
    up.amount AS 'Amount Paid',
    up.payment_status AS 'Payment Status',
    up.merchant_order_id AS 'Merchant Order ID',
    up.tracking_id AS 'Tracking ID',
    up.bank_ref_no AS 'Bank Reference',
    up.created_at AS 'Payment Date',
    -- Calculate discrepancy
    (qo.amount_payable_after_discount - up.amount) AS 'Amount Difference',
    CASE 
        WHEN (qo.amount_payable_after_discount - up.amount) > 100 THEN 'SUSPICIOUS - Large Discrepancy'
        WHEN (qo.amount_payable_after_discount - up.amount) > 0 THEN 'SUSPICIOUS - Small Discrepancy'
        ELSE 'OK'
    END AS 'Fraud Indicator'
FROM 
    qs_orders qo
LEFT JOIN 
    unlimit_payments up ON qo.id = up.order_id
LEFT JOIN 
    users u ON qo.user_id = u.id
WHERE 
    -- Filter for suspicious transactions
    (qo.amount_payable_after_discount - up.amount) > 0
    AND up.payment_status IN ('success', 'paid', 'approved', 'confirmed')
    AND qo.order_status IN ('COMPLETE', 'PAID', 'PENDING')
    -- Optional: Filter by date range
    -- AND qo.created_at >= '2026-01-01'
    -- AND qo.created_at <= '2026-01-31'
ORDER BY 
    (qo.amount_payable_after_discount - up.amount) DESC,
    qo.created_at DESC;

-- =====================================================
-- QUERY 2: Detailed Fraud Transaction Report
-- =====================================================
-- Comprehensive report for a specific order or date range
-- =====================================================

SELECT 
    'ORDER DETAILS' AS 'Section',
    qo.id AS 'Order ID',
    qo.refno AS 'Reference Number',
    qo.merchant_order_id AS 'Merchant Order ID',
    qo.user_id AS 'User ID',
    u.name AS 'User Name',
    u.email AS 'User Email',
    u.mobile AS 'User Phone',
    qo.product_name AS 'Product Name',
    qo.sku AS 'SKU',
    qo.denomination AS 'Denomination (₹)',
    qo.quantity AS 'Quantity',
    qo.grand_payable_amount AS 'Grand Total (₹)',
    qo.discounted_amount_value AS 'Discount (₹)',
    qo.amount_payable_after_discount AS 'Expected Amount (₹)',
    qo.order_status AS 'Order Status',
    qo.woohoo_order_id AS 'Woohoo Order ID',
    qo.created_at AS 'Order Created',
    qo.updated_at AS 'Order Updated',
    -- Payment Information
    up.id AS 'Payment ID',
    up.amount AS 'Amount Paid (₹)',
    up.payment_status AS 'Payment Status',
    up.tracking_id AS 'Payment Tracking ID',
    up.bank_ref_no AS 'Bank Reference',
    up.payment_mode AS 'Payment Mode',
    up.created_at AS 'Payment Date',
    -- Calculate fraud metrics
    (qo.amount_payable_after_discount - up.amount) AS 'Fraud Amount (₹)',
    ROUND(((qo.amount_payable_after_discount - up.amount) / qo.amount_payable_after_discount) * 100, 2) AS 'Fraud Percentage (%)',
    -- Gift Card Information
    CASE 
        WHEN qo.woohoo_order_id IS NOT NULL AND qo.woohoo_order_id != '' THEN 'YES'
        ELSE 'NO'
    END AS 'Gift Card Issued',
    CASE 
        WHEN qo.cards IS NOT NULL AND qo.cards != '' THEN 'YES'
        ELSE 'NO'
    END AS 'Card Details Stored'
FROM 
    qs_orders qo
LEFT JOIN 
    unlimit_payments up ON qo.id = up.order_id
LEFT JOIN 
    users u ON qo.user_id = u.id
WHERE 
    -- SPECIFY ORDER ID OR DATE RANGE HERE
    qo.id = [ORDER_ID]  -- Replace [ORDER_ID] with actual order ID
    -- OR use date range:
    -- qo.created_at >= '2026-01-01' AND qo.created_at <= '2026-01-31'
ORDER BY 
    qo.created_at DESC;

-- =====================================================
-- QUERY 3: User Fraud Pattern Analysis
-- =====================================================
-- Identify users with multiple suspicious transactions
-- =====================================================

SELECT 
    u.id AS 'User ID',
    u.name AS 'User Name',
    u.email AS 'User Email',
    u.mobile AS 'User Phone',
    COUNT(qo.id) AS 'Total Orders',
    COUNT(CASE WHEN (qo.amount_payable_after_discount - up.amount) > 0 THEN 1 END) AS 'Suspicious Orders',
    SUM(qo.amount_payable_after_discount) AS 'Total Expected Amount (₹)',
    SUM(up.amount) AS 'Total Paid Amount (₹)',
    SUM(qo.amount_payable_after_discount - up.amount) AS 'Total Fraud Amount (₹)',
    MIN(qo.created_at) AS 'First Order Date',
    MAX(qo.created_at) AS 'Last Order Date'
FROM 
    qs_orders qo
LEFT JOIN 
    unlimit_payments up ON qo.id = up.order_id
LEFT JOIN 
    users u ON qo.user_id = u.id
WHERE 
    (qo.amount_payable_after_discount - up.amount) > 0
    AND up.payment_status IN ('success', 'paid', 'approved', 'confirmed')
GROUP BY 
    u.id, u.name, u.email, u.mobile
HAVING 
    COUNT(CASE WHEN (qo.amount_payable_after_discount - up.amount) > 0 THEN 1 END) > 0
ORDER BY 
    SUM(qo.amount_payable_after_discount - up.amount) DESC;

-- =====================================================
-- QUERY 4: Payment Gateway Transaction Logs
-- =====================================================
-- Extract payment gateway callback/response data
-- =====================================================

SELECT 
    up.id AS 'Payment ID',
    up.order_id AS 'Order ID',
    qo.refno AS 'Reference Number',
    up.merchant_order_id AS 'Merchant Order ID',
    up.tracking_id AS 'Tracking ID',
    up.bank_ref_no AS 'Bank Reference',
    up.amount AS 'Amount Paid (₹)',
    up.payment_status AS 'Payment Status',
    up.payment_mode AS 'Payment Mode',
    up.currency AS 'Currency',
    up.status_code AS 'Status Code',
    up.status_message AS 'Status Message',
    up.created_at AS 'Payment Created',
    up.updated_at AS 'Payment Updated',
    -- Raw response data (may contain additional evidence)
    LEFT(up.unlimit_response, 500) AS 'Gateway Response (First 500 chars)',
    LEFT(up.raw_callback, 500) AS 'Callback Data (First 500 chars)'
FROM 
    unlimit_payments up
LEFT JOIN 
    qs_orders qo ON up.order_id = qo.id
WHERE 
    -- Filter for suspicious payments
    up.order_id IN (
        SELECT id FROM qs_orders 
        WHERE amount_payable_after_discount > (
            SELECT amount FROM unlimit_payments 
            WHERE order_id = qs_orders.id
        )
    )
    -- Optional: Filter by date
    -- AND up.created_at >= '2026-01-01'
ORDER BY 
    up.created_at DESC;

-- =====================================================
-- QUERY 5: Gift Card Issuance vs Payment Mismatch
-- =====================================================
-- Orders where gift cards were issued but payment was insufficient
-- =====================================================

SELECT 
    qo.id AS 'Order ID',
    qo.refno AS 'Reference Number',
    qo.woohoo_order_id AS 'Woohoo Order ID',
    qo.denomination AS 'Card Denomination (₹)',
    qo.quantity AS 'Card Quantity',
    (qo.denomination * qo.quantity) AS 'Total Card Value (₹)',
    qo.amount_payable_after_discount AS 'Expected Payment (₹)',
    up.amount AS 'Actual Payment (₹)',
    (qo.denomination * qo.quantity - up.amount) AS 'Loss Amount (₹)',
    qo.order_status AS 'Order Status',
    CASE 
        WHEN qo.cards IS NOT NULL AND qo.cards != '' THEN 'YES - Cards in DB'
        ELSE 'NO - Check Woohoo API'
    END AS 'Card Data Available',
    qo.created_at AS 'Order Date'
FROM 
    qs_orders qo
LEFT JOIN 
    unlimit_payments up ON qo.id = up.order_id
WHERE 
    qo.woohoo_order_id IS NOT NULL
    AND qo.woohoo_order_id != ''
    AND qo.order_status IN ('COMPLETE', 'PAID')
    AND up.amount < qo.amount_payable_after_discount
    AND up.payment_status IN ('success', 'paid', 'approved', 'confirmed')
ORDER BY 
    (qo.denomination * qo.quantity - up.amount) DESC;

-- =====================================================
-- QUERY 6: IP Address and User Agent Logs
-- =====================================================
-- Extract IP addresses and browser details for investigation
-- Note: This requires access to application logs or user_ip table
-- =====================================================

-- If you have a user_ip table:
SELECT 
    ui.user_id AS 'User ID',
    u.email AS 'User Email',
    ui.ip_address AS 'IP Address',
    ui.user_agent AS 'Browser/Device',
    ui.created_at AS 'Access Date',
    COUNT(*) AS 'Access Count'
FROM 
    user_ips ui
LEFT JOIN 
    users u ON ui.user_id = u.id
WHERE 
    ui.user_id IN (
        SELECT DISTINCT user_id FROM qs_orders 
        WHERE id IN (
            SELECT order_id FROM unlimit_payments 
            WHERE amount < (
                SELECT amount_payable_after_discount FROM qs_orders 
                WHERE id = unlimit_payments.order_id
            )
        )
    )
GROUP BY 
    ui.user_id, ui.ip_address, ui.user_agent
ORDER BY 
    ui.created_at DESC;

-- =====================================================
-- QUERY 7: Summary Report for Police Complaint
-- =====================================================
-- High-level summary of all fraud cases
-- =====================================================

SELECT 
    COUNT(DISTINCT qo.id) AS 'Total Fraudulent Orders',
    COUNT(DISTINCT qo.user_id) AS 'Unique Fraudsters',
    SUM(qo.amount_payable_after_discount) AS 'Total Expected Amount (₹)',
    SUM(up.amount) AS 'Total Paid Amount (₹)',
    SUM(qo.amount_payable_after_discount - up.amount) AS 'Total Financial Loss (₹)',
    MIN(qo.created_at) AS 'First Fraud Date',
    MAX(qo.created_at) AS 'Last Fraud Date',
    AVG(qo.amount_payable_after_discount - up.amount) AS 'Average Fraud per Transaction (₹)',
    MAX(qo.amount_payable_after_discount - up.amount) AS 'Largest Single Fraud (₹)'
FROM 
    qs_orders qo
LEFT JOIN 
    unlimit_payments up ON qo.id = up.order_id
WHERE 
    (qo.amount_payable_after_discount - up.amount) > 0
    AND up.payment_status IN ('success', 'paid', 'approved', 'confirmed')
    AND qo.order_status IN ('COMPLETE', 'PAID', 'PENDING');

-- =====================================================
-- INSTRUCTIONS FOR USING THESE QUERIES
-- =====================================================

/*
1. REPLACE PLACEHOLDERS:
   - Replace [ORDER_ID] with actual order IDs
   - Adjust date ranges as needed
   - Modify table names if your schema differs

2. EXPORT RESULTS:
   - Run each query in your database client (phpMyAdmin, MySQL Workbench, etc.)
   - Export results as CSV or Excel
   - Save with descriptive filenames (e.g., "Fraud_Transactions_Query1.csv")

3. VERIFY DATA:
   - Cross-check amounts with payment gateway records
   - Verify user details are correct
   - Ensure all suspicious transactions are captured

4. SECURITY:
   - Run queries on a database backup or read-only connection
   - Do not modify any data
   - Keep exported files secure

5. FOR POLICE SUBMISSION:
   - Include Query 1, 2, and 7 in your evidence package
   - Query 3 helps identify repeat offenders
   - Query 5 shows the actual financial impact
*/

-- =====================================================
-- END OF QUERIES
-- =====================================================
