# Police Complaint Filing Checklist - Fraud Case

Use this checklist to ensure you have all necessary documents and information before filing the police complaint.

---

## ✅ **PRE-FILING PREPARATION**

### **1. Evidence Collection**

- [ ] **Database Records**
  - [ ] Run SQL queries from `FRAUD_EVIDENCE_QUERIES.sql`
  - [ ] Export all fraudulent transaction data as CSV/Excel
  - [ ] Export user details for all fraudsters
  - [ ] Export payment gateway transaction records
  - [ ] Export order and gift card issuance records
  - [ ] Verify all amounts and dates are accurate

- [ ] **Server Logs**
  - [ ] Export application logs (`storage/logs/laravel.log`) for fraud period
  - [ ] Extract relevant log entries showing:
    - [ ] Order creation timestamps
    - [ ] Payment processing entries
    - [ ] IP addresses
    - [ ] User agent strings
    - [ ] Any error messages or suspicious activity

- [ ] **Payment Gateway Records**
  - [ ] Download transaction reports from Unlimit dashboard
  - [ ] Download transaction reports from CCAvenue dashboard (if applicable)
  - [ ] Note all transaction IDs, tracking IDs, bank reference numbers
  - [ ] Verify payment amounts match database records

- [ ] **Screenshots**
  - [ ] Screenshot of order details showing expected amount
  - [ ] Screenshot of payment gateway record showing actual paid amount
  - [ ] Screenshot of gift card issued (if accessible)
  - [ ] Screenshot of database records showing discrepancy
  - [ ] Screenshot of user account details

- [ ] **Email Records**
  - [ ] Export transaction confirmation emails sent to fraudsters
  - [ ] Export gift card delivery emails
  - [ ] Note email timestamps and content

---

### **2. Documentation**

- [ ] **Fill Police Complaint Template**
  - [ ] Open `POLICE_COMPLAINT_TEMPLATE.md`
  - [ ] Fill in all [bracketed] placeholders:
    - [ ] Company details
    - [ ] Your personal/company contact information
    - [ ] All transaction details (Order IDs, amounts, dates)
    - [ ] User details (email, phone, name)
    - [ ] Financial loss calculations
    - [ ] Evidence file names
  - [ ] Review and verify all information is accurate
  - [ ] Print or save as PDF for filing

- [ ] **Technical Documentation**
  - [ ] Prepare a simple explanation of the vulnerability (for non-technical police)
  - [ ] Include screenshots showing the vulnerability
  - [ ] Document when the vulnerability was fixed
  - [ ] Include `PAYMENT_SECURITY_OVERVIEW.md` as supporting document

---

### **3. Legal Preparation**

- [ ] **Company Authorization** (if filing on behalf of company)
  - [ ] Get authorization letter from company director/owner
  - [ ] Letter should authorize you to file complaint
  - [ ] Include company letterhead and seal

- [ ] **Company Registration Documents**
  - [ ] Company registration certificate
  - [ ] GST certificate (if applicable)
  - [ ] PAN card copy
  - [ ] Address proof of company

- [ ] **Personal Documents** (if filing as individual)
  - [ ] Identity proof (Aadhaar/PAN/Passport)
  - [ ] Address proof
  - [ ] Photo copies (2-3 sets)

---

### **4. Financial Documentation**

- [ ] **Calculate Total Loss**
  - [ ] Sum all fraudulent transactions
  - [ ] Calculate: (Expected Amount - Paid Amount) for each transaction
  - [ ] Total financial loss = Sum of all discrepancies
  - [ ] Prepare a summary table showing:
    - [ ] Number of fraudulent transactions
    - [ ] Total expected amount
    - [ ] Total paid amount
    - [ ] Total loss

- [ ] **Bank/Payment Records**
  - [ ] Bank statements showing payment receipts
  - [ ] Payment gateway settlement reports
  - [ ] Any reconciliation documents

---

### **5. Technical Evidence**

- [ ] **Code Evidence**
  - [ ] Screenshot of vulnerable code (before fix)
  - [ ] Screenshot of fixed code (after fix)
  - [ ] Git commit history showing fix date (if available)
  - [ ] Code comparison showing what was changed

- [ ] **Database Schema**
  - [ ] Table structure documentation
  - [ ] Field descriptions for relevant tables
  - [ ] Relationship diagrams (if helpful)

---

## ✅ **BEFORE GOING TO POLICE STATION**

### **6. Final Review**

- [ ] **Review Complaint**
  - [ ] Read entire complaint one more time
  - [ ] Verify all dates, amounts, and transaction IDs
  - [ ] Ensure all legal sections are correctly cited
  - [ ] Check spelling and grammar

- [ ] **Prepare Evidence Package**
  - [ ] Organize all documents in a folder:
    - [ ] Original complaint (signed)
    - [ ] Database exports (CSV/Excel)
    - [ ] Server logs
    - [ ] Screenshots
    - [ ] Payment gateway records
    - [ ] Company documents
    - [ ] Authorization letter (if applicable)
  - [ ] Make 2-3 copies of everything
  - [ ] Number all pages
  - [ ] Create an index/table of contents

- [ ] **Prepare Yourself**
  - [ ] Know your complaint by heart
  - [ ] Be ready to explain the technical fraud in simple terms
  - [ ] Bring a pen and notebook
  - [ ] Bring your ID proof
  - [ ] Bring company authorization (if applicable)

---

## ✅ **AT THE POLICE STATION**

### **7. Filing Process**

- [ ] **Initial Meeting**
  - [ ] Meet with Station House Officer (SHO) or Duty Officer
  - [ ] Explain the nature of the complaint briefly
  - [ ] Submit the complaint letter
  - [ ] Submit all evidence documents

- [ ] **Verification**
  - [ ] Police will verify your identity
  - [ ] They may ask questions about:
    - [ ] The technical nature of fraud
    - [ ] How you discovered it
    - [ ] Total financial loss
    - [ ] Suspect details (if known)
  - [ ] Answer clearly and honestly

- [ ] **FIR Registration**
  - [ ] Ensure FIR is registered
  - [ ] Get FIR number
  - [ ] Get a copy of the FIR
  - [ ] Note the date and time of registration
  - [ ] Note the police officer's name and designation

- [ ] **Evidence Submission**
  - [ ] Submit all evidence documents
  - [ ] Get acknowledgment receipt for evidence
  - [ ] Note what evidence was submitted

---

### **8. Post-Filing Actions**

- [ ] **Document Everything**
  - [ ] Note FIR number, date, time
  - [ ] Note police station details
  - [ ] Note investigating officer's name and contact
  - [ ] Keep copies of all submitted documents

- [ ] **Follow-up Plan**
  - [ ] Set reminder to follow up after 1 week
  - [ ] Prepare to provide additional information if requested
  - [ ] Keep all evidence safe for future reference

- [ ] **Legal Consultation** (if needed)
  - [ ] Consult with cyber law lawyer
  - [ ] Understand next steps in investigation
  - [ ] Prepare for potential court proceedings

---

## ✅ **IMPORTANT REMINDERS**

### **Do's:**
- ✅ Be honest and accurate in all information
- ✅ Keep multiple copies of all documents
- ✅ Follow up regularly on investigation status
- ✅ Cooperate fully with police investigation
- ✅ Preserve all digital evidence (don't delete anything)
- ✅ Maintain a log of all interactions with police

### **Don'ts:**
- ❌ Don't exaggerate or provide false information
- ❌ Don't delete any evidence or logs
- ❌ Don't contact suspects directly
- ❌ Don't share complaint details publicly
- ❌ Don't lose any original documents

---

## ✅ **QUICK REFERENCE**

### **Key Documents Needed:**
1. Filled police complaint (`POLICE_COMPLAINT_TEMPLATE.md`)
2. Database exports (from `FRAUD_EVIDENCE_QUERIES.sql`)
3. Server logs
4. Payment gateway records
5. Screenshots
6. Company registration documents
7. Authorization letter (if applicable)
8. Identity proof

### **Key Information to Have Ready:**
- Total number of fraudulent transactions
- Total financial loss amount
- Date range of fraud
- Suspect user details (email, phone, name)
- Transaction IDs and order IDs
- IP addresses (if available)

### **Legal Sections to Cite:**
- Section 66, IT Act 2000
- Section 66C, IT Act 2000
- Section 66D, IT Act 2000
- Section 420, IPC 1860
- Section 468, IPC 1860
- Section 471, IPC 1860

---

## ✅ **FINAL CHECK**

Before leaving for the police station, verify:

- [ ] All documents are organized and copied
- [ ] Complaint is filled completely and accurately
- [ ] All evidence is ready
- [ ] You have your ID proof
- [ ] You know your complaint details
- [ ] You have a pen and notebook
- [ ] You have contact numbers of key people

---

**Good luck with filing the complaint!**

**Remember:** The police are there to help. Be patient, cooperative, and provide all requested information clearly.

---

**Last Updated:** [Date]  
**Prepared By:** [Your Name]
