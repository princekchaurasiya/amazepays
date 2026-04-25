<?php

namespace App\Services\Order;

final class InvoiceHtmlRenderer
{
    /**
     * Render invoice HTML without Blade.
     *
     * @param  array<string, mixed>  $d
     */
    public function render(array $d): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

        $companyName = $e(config('companyDefaultValues.company_official_name'));
        $companyAddress = $e(config('companyDefaultValues.company_address'));
        $companyEmail = $e(config('companyDefaultValues.company_email'));
        $cin = $e(config('companyDefaultValues.company_cin'));
        $pan = $e(config('companyDefaultValues.company_pan'));
        $gstin = $e(config('companyDefaultValues.gst_number'));

        $invoiceNumber = $e($d['invoice_number'] ?? '');
        $invoiceDate = $e($d['invoice_date'] ?? '');
        $bankRef = $e($d['bank_ref_no'] ?? '');
        $billingName = $e($d['billing_name'] ?? '');
        $billingTel = $e($d['billing_tel'] ?? '');
        $billingEmail = $e($d['billing_email'] ?? '');

        $billingAddress = $e($d['billing_address'] ?? '');
        $billingAddressTwo = trim((string) ($d['billing_address_two'] ?? ''));
        $billingCity = $e($d['billing_city'] ?? '');
        $billingState = $e($d['billing_state'] ?? '');
        $billingZip = $e($d['billing_zip'] ?? '');
        $billingCountry = $e($d['billing_country'] ?? '');
        $gstNumber = $e($d['gst_number'] ?? '');
        $billingAddressLine = $billingAddress.(($billingAddressTwo !== '') ? ', '.$e($billingAddressTwo) : '');

        $orderId = $e($d['order_id'] ?? '');
        $productName = $e($d['cardProductName'] ?? ($d['product_name'] ?? ''));
        $denomination = $e($d['denomination'] ?? '');
        $quantity = $e($d['quantity'] ?? '');
        $grandPayable = $e($d['grand_payable_amount'] ?? '');
        $discountPct = $e($d['discount_percentage'] ?? '');
        $discountAmt = $e($d['discount'] ?? '');
        $netPayable = $e($d['amount_payable_after_discount'] ?? '');

        $bankName = $e(config('companyDefaultValues.company_bank_name'));
        $bankBranch = $e(config('companyDefaultValues.company_bank_branch'));
        $bankAcc = $e(config('companyDefaultValues.company_bank_account_number'));
        $bankIfsc = $e(config('companyDefaultValues.company_bank_ifsc_code'));

        // Keep terms simple for now; Blade previously used HTML from InvoiceHelper.
        $terms = $e((string) config('companyDefaultValues.invoice_terms', ''));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Invoice</title>
  <style>
    body { font-size: 12px; }
    table, td, th { border: 1px solid black; border-collapse: collapse; }
    table { width: 100%; }
    td { height: 40px; text-align: center; vertical-align: middle; }
    .logo { text-align: left; padding-left: 2%; width: 40%; }
    .bill-of-supply { text-align: right; padding-right: 2%; }
    .company-details { padding: 4px; text-align: left; padding-left: 2%; margin-bottom: 0; margin-top: 0; }
  </style>
</head>
<body>
  <table>
    <tr>
      <th class="logo" colspan="3">
        <img src="https://amazepays.in/images/logo.png" alt="Company Logo" width="100px" style="border:0px solid black;">
      </th>
      <th class="bill-of-supply" colspan="5"><h2 style="text-align:center;">Bill of Supply</h2></th>
    </tr>
    <tr>
      <td colspan="3" style="vertical-align: top; border: 0px solid black;">
        <p class="company-details">{$companyName}</p>
        <p class="company-details">{$companyAddress}</p>
        <p class="company-details">Email: {$companyEmail}</p>
        <p class="company-details">CIN: {$cin}</p>
        <p class="company-details">PAN: {$pan}</p>
        <p class="company-details">GSTIN: {$gstin}</p>
      </td>
      <td colspan="5" style="vertical-align: top; padding: 0; border: 1px solid transparent;">
        <table style="width:100%; border-collapse: collapse;">
          <tbody>
            <tr><td style="height:35px; padding-left:16px; text-align:left; width:50%;">1. Invoice Number</td><td style="height:35px; padding-left:16px; text-align:left; width:50%;">{$invoiceNumber}</td></tr>
            <tr><td style="height:35px; padding-left:16px; text-align:left; width:50%;">2. Invoice Date</td><td style="height:35px; padding-left:16px; text-align:left; width:50%;">{$invoiceDate}</td></tr>
            <tr><td style="height:35px; padding-left:16px; text-align:left; width:50%;">3. Bank Ref. No.</td><td style="height:35px; padding-left:16px; text-align:left; width:50%;">{$bankRef}</td></tr>
            <tr><td style="height:35px; padding-left:16px; text-align:left; width:50%;">4. Requester Name</td><td style="height:35px; padding-left:16px; text-align:left; width:50%;">{$billingName}</td></tr>
            <tr><td style="height:35px; padding-left:16px; text-align:left; width:50%;">5. Customer Contact No.</td><td style="height:35px; padding-left:16px; text-align:left; width:50%;">{$billingTel}</td></tr>
            <tr><td style="height:35px; padding-left:16px; text-align:left; width:50%;">6. Customer Email Id.</td><td style="height:35px; padding-left:16px; text-align:left; width:50%;">{$billingEmail}</td></tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr><td style="font-weight:600; font-size:16px; text-align:left; padding-left:1%" colspan="8">BILL TO</td></tr>
    <tr>
      <td colspan="8">
        <p class="company-details">{$billingName}</p>
        <p class="company-details">{$billingAddressLine}</p>
        <p class="company-details">{$billingCity}, {$billingState} {$billingZip}</p>
        <p class="company-details">{$billingCountry}</p>
        <p class="company-details">Email: {$billingEmail}</p>
        <p class="company-details">Contact: {$billingTel}</p>
        <p class="company-details">GST No: {$gstNumber}</p>
      </td>
    </tr>
    <tr><td style="font-weight:600; font-size:16px;" colspan="8">Order Details</td></tr>
    <tr>
      <td style="text-align:left; padding-left:16px;">Order No.</td>
      <td style="text-align:left; padding-left:16px;">Product Name</td>
      <td style="text-align:left; padding-left:16px;">Denomination</td>
      <td style="text-align:left; padding-left:16px;">Quantity</td>
      <td style="text-align:left; padding-left:16px;">Total Amount</td>
      <td style="text-align:left; padding-left:16px;">Discount %</td>
      <td style="text-align:left; padding-left:16px;">Discount Amount</td>
      <td style="text-align:left; padding-left:16px;"><b>Net Payable Amount</b></td>
    </tr>
    <tr>
      <td style="text-align:left; padding-left:16px;">{$orderId}</td>
      <td style="text-align:left; padding-left:16px;">{$productName}</td>
      <td style="text-align:left; padding-left:16px;">{$denomination}</td>
      <td style="text-align:left; padding-left:16px;">{$quantity}</td>
      <td style="text-align:left; padding-left:16px;">{$grandPayable}</td>
      <td style="text-align:left; padding-left:16px;">{$discountPct}</td>
      <td style="text-align:left; padding-left:16px;">{$discountAmt}</td>
      <td style="text-align:left; padding-left:16px;"><b>{$netPayable}</b></td>
    </tr>
    <tr>
      <td colspan="2" style="text-align:left; padding-left:16px;">Total Amount: {$grandPayable}</td>
      <td colspan="2" style="text-align:left; padding-left:16px;">Total Discount Amount: {$discountAmt}</td>
      <td colspan="4" style="font-weight:600; text-align:left; padding-left:16px;">Net Amount after Discount: {$netPayable}</td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:left; padding-left:16px;">
        <p style="font-weight:600; margin:8px 0; font-size:16px">Remittance Detail</p>
        <p style="margin:2px 0;"><span style="font-weight:600">Beneficiary Name:</span> {$companyName}</p>
        <p style="margin:2px 0;"><span style="font-weight:600">Bank Name:</span> {$bankName}</p>
        <p style="margin:2px 0;"><span style="font-weight:600">Branch:</span> {$bankBranch}</p>
        <p style="margin:2px 0;"><span style="font-weight:600">Account No:</span> {$bankAcc}</p>
        <p style="margin:2px 0;"><span style="font-weight:600">IFSC Code:</span> {$bankIfsc}</p>
      </td>
      <td colspan="4" style="text-align:left; padding-left:16px;">
        <p style="font-weight:600; margin:8px 0; font-size:16px">Terms and Conditions</p>
        <p>{$terms}</p>
      </td>
    </tr>
    <tr>
      <td colspan="8" style="text-align:center; font-size:16px;">
        <p>This is a System-generated Invoice and Does not Require a Signature</p>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}

