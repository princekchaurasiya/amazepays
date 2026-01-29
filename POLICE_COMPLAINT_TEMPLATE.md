# Police Complaint (FIR) - Cyber Fraud Case

## **FIRST INFORMATION REPORT (FIR)**

**To,**  
The Station House Officer  
[Police Station Name]  
[Police Station Address]  
[City, State, PIN Code]

**Date:** [Date of Filing]  
**Time:** [Time of Filing]

---

## **COMPLAINANT DETAILS**

**Name:** [Your Company Name / Your Name]  
**Designation:** [Your Designation]  
**Address:** [Company Address / Your Address]  
**City:** [City]  
**State:** [State]  
**PIN Code:** [PIN Code]  
**Phone Number:** [Contact Number]  
**Email:** [Email Address]  
**GST Number (if applicable):** [GST Number]

---

## **SUBJECT: COMPLAINT REGARDING CYBER FRAUD - UNAUTHORIZED MANIPULATION OF PAYMENT SYSTEM RESULTING IN FINANCIAL LOSS**

---

## **1. BACKGROUND OF THE BUSINESS**

We are [Company Name], operating an e-commerce platform for gift card sales at [Website URL]. Our platform allows customers to purchase gift cards online through payment gateways (Unlimit, CCAvenue, UPI). The platform integrates with Woohoo API for gift card issuance.

---

## **2. DETAILS OF THE FRAUD**

### **2.1 Nature of the Crime**

On [Date], we discovered that a user(s) exploited a security vulnerability in our payment system to commit fraud. The fraud involved:

1. **Unauthorized manipulation of payment amounts** using browser developer tools
2. **Payment of a minimal amount (₹1)** while receiving gift cards worth **₹10,000 or higher**
3. **Intentional exploitation of a technical vulnerability** in the checkout process

### **2.2 Technical Explanation of the Fraud**

**Vulnerability Exploited:**
- Our checkout page contained a **hidden HTML input field** named `payable_amount` that displayed the order total
- The backend payment system **trusted this client-side value** when processing payments
- An attacker could:
  - Open browser developer tools (F12)
  - Modify the hidden `payable_amount` field from ₹10,000 to ₹1
  - Submit the payment form
  - The payment gateway would process only ₹1
  - However, the gift card system would issue a card worth ₹10,000 based on the original order data

**Evidence of Manipulation:**
- Payment gateway records show payment of **₹1** (or minimal amount)
- Gift card issuance records show cards worth **₹10,000** (or higher) were issued
- Database logs show the discrepancy between paid amount and card value

---

## **3. FRAUD TRANSACTION DETAILS**

### **Transaction 1:**

| Field | Details |
|-------|---------|
| **Order ID** | [Order ID from database] |
| **Reference Number** | [Refno from qs_orders table] |
| **User ID** | [User ID if available] |
| **User Email** | [Email address] |
| **User Phone** | [Phone number] |
| **Date & Time** | [Transaction date/time] |
| **Expected Amount** | ₹[Amount] |
| **Amount Actually Paid** | ₹[Amount] |
| **Gift Card Value Issued** | ₹[Amount] |
| **Financial Loss** | ₹[Difference] |
| **Payment Gateway** | [Unlimit/CCAvenue/UPI] |
| **Payment Transaction ID** | [Transaction ID from gateway] |
| **Woohoo Order ID** | [Woohoo order ID if available] |
| **Gift Card Number(s)** | [Card numbers issued] |
| **IP Address** | [IP address from logs] |

### **Transaction 2:**

[Repeat the same table for each fraudulent transaction]

---

## **4. EVIDENCE AVAILABLE**

### **4.1 Database Records**

1. **Order Records** (`qs_orders` table):
   - Order ID: [ID]
   - Denomination: ₹[Amount]
   - Quantity: [Number]
   - Expected Total: ₹[Amount]
   - Actual Payment Status: [Status]
   - Gift Card Issued: Yes/No

2. **Payment Records** (`unlimit_payments` / `cc_avenue_payments` table):
   - Payment ID: [ID]
   - Amount Charged: ₹[Amount]
   - Payment Status: [Status]
   - Gateway Transaction ID: [ID]
   - Payment Date: [Date]

3. **Gift Card Records** (`qs_orders.cards` field or Woohoo API):
   - Card Number(s): [Numbers]
   - Card Value: ₹[Amount]
   - Issuance Date: [Date]

### **4.2 Server Logs**

- **Application Logs** (`storage/logs/laravel.log`):
  - Timestamp: [Date/Time]
  - Log Entry: [Relevant log lines showing the fraud]
  - IP Address: [IP]
  - User Agent: [Browser details]

- **Payment Gateway Logs:**
  - Transaction ID: [ID]
  - Amount Processed: ₹[Amount]
  - Status: [Status]

### **4.3 Technical Evidence**

- **Code Vulnerability Documentation:**
  - File: `resources/views/userpanel/checkout.blade.php`
  - Vulnerable Code Pattern: Hidden `payable_amount` input field
  - Fix Implemented: [Date] - Removed hidden amount fields, enforced server-side validation

- **Database Query Results:**
  - SQL queries showing discrepancy between paid amount and card value
  - [Attach SQL query results if available]

### **4.4 Digital Evidence**

- **Screenshots:**
  - [Screenshot 1: Order details showing expected amount]
  - [Screenshot 2: Payment gateway record showing actual paid amount]
  - [Screenshot 3: Gift card issued with higher value]
  - [Screenshot 4: Database records showing discrepancy]

- **Email Records:**
  - Transaction confirmation emails sent to user
  - Gift card delivery emails

---

## **5. LEGAL PROVISIONS VIOLATED**

The accused has committed offenses under the following sections:

1. **Section 66 of the Information Technology Act, 2000:**
   - Computer-related offenses - unauthorized access, modification, or manipulation of computer systems

2. **Section 66C of the Information Technology Act, 2000:**
   - Identity theft - using another person's identity or credentials

3. **Section 66D of the Information Technology Act, 2000:**
   - Cheating by personation using computer resource

4. **Section 420 of the Indian Penal Code, 1860:**
   - Cheating and dishonestly inducing delivery of property

5. **Section 468 of the Indian Penal Code, 1860:**
   - Forgery for the purpose of cheating

6. **Section 471 of the Indian Penal Code, 1860:**
   - Using as genuine a forged document or electronic record

---

## **6. FINANCIAL IMPACT**

### **Total Financial Loss:**

| Transaction | Expected Amount | Paid Amount | Loss |
|------------|----------------|-------------|------|
| Transaction 1 | ₹[Amount] | ₹[Amount] | ₹[Amount] |
| Transaction 2 | ₹[Amount] | ₹[Amount] | ₹[Amount] |
| **TOTAL** | **₹[Total]** | **₹[Total]** | **₹[Total Loss]** |

**Note:** The financial loss represents the difference between the gift card value issued and the amount actually paid by the fraudster.

---

## **7. ACTIONS TAKEN BY US**

1. **Immediate Security Fix:**
   - Removed vulnerable hidden amount fields from checkout forms
   - Implemented server-side amount validation from database
   - All payment amounts are now calculated from trusted database records only

2. **Investigation:**
   - Reviewed all transactions for similar patterns
   - Identified all fraudulent transactions
   - Preserved all logs and database records as evidence

3. **Documentation:**
   - Created technical documentation of the vulnerability
   - Documented all fraudulent transactions
   - Prepared this complaint with supporting evidence

---

## **8. REQUEST FOR ACTION**

We request the police to:

1. **Register an FIR** under the relevant sections of the IT Act, 2000 and IPC, 1860
2. **Investigate the matter** and identify the accused person(s)
3. **Seize and preserve digital evidence** including:
   - Server logs
   - Database records
   - Payment gateway transaction records
   - IP address logs
   - Email records
4. **Take legal action** against the accused
5. **Recover the financial loss** incurred by our company
6. **Issue necessary directions** to prevent similar incidents

---

## **9. UNDERTAKING**

I, [Your Name], hereby declare that:

1. The information provided in this complaint is true and correct to the best of my knowledge
2. I have not concealed any material facts
3. I am ready to provide additional information, documents, or evidence as required
4. I will cooperate fully with the investigation

---

## **10. ATTACHMENTS**

1. ✅ Database records (SQL exports) - [File names]
2. ✅ Server logs - [File names]
3. ✅ Payment gateway transaction records - [File names]
4. ✅ Screenshots of fraudulent transactions - [File names]
5. ✅ Technical documentation of vulnerability - [File names]
6. ✅ Company registration documents - [File names]
7. ✅ Authorization letter (if filing on behalf of company) - [File name]

---

## **11. CONTACT FOR INVESTIGATION**

**Technical Contact Person:**  
Name: [Name]  
Designation: [Designation]  
Phone: [Phone]  
Email: [Email]

**Legal Contact Person:**  
Name: [Name]  
Designation: [Designation]  
Phone: [Phone]  
Email: [Email]

---

## **SIGNATURE**

**Complainant:**  
_________________________  
[Your Name]  
[Designation]  
[Company Name]  
Date: [Date]

---

## **VERIFICATION**

I verify that the contents of this complaint are true and correct. I am aware that providing false information is punishable under law.

**Signature:**  
_________________________  
[Your Name]  
Date: [Date]

---

## **POLICE STATION ACKNOWLEDGMENT**

**Received by:**  
[Police Officer Name]  
[Designation]  
[Police Station Name]  
Date: [Date]  
Time: [Time]  
FIR Number: [To be filled by police]

---

## **IMPORTANT NOTES FOR FILING:**

1. **Before Filing:**
   - Fill in all [bracketed] placeholders with actual data
   - Gather all evidence (database exports, logs, screenshots)
   - Get company authorization letter if filing on behalf of company
   - Consult with a lawyer if needed

2. **Evidence Preparation:**
   - Export relevant database records as CSV/Excel
   - Export server logs for the fraud period
   - Take screenshots of all fraudulent transactions
   - Prepare a summary document with transaction IDs and amounts

3. **Legal Consultation:**
   - Consider consulting a cyber law expert before filing
   - Ensure all legal provisions cited are applicable
   - Prepare for potential court proceedings

4. **Follow-up:**
   - Keep copies of all documents submitted
   - Note the FIR number for future reference
   - Follow up regularly on the investigation status

---

**END OF COMPLAINT**
