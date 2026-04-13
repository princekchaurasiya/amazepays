<!DOCTYPE html>
<html>

<head>
    <title>Invoice</title>
    <style>
        body {
            font-size: 12px;
        }

        table,
        td,
        th {
            border: 1px solid black;
            border-collapse: collapse;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td {
            height: 40px;
            text-align: center;
            vertical-align: middle;
        }

        .logo {
            text-align: left;
            padding-left: 2%;
            width: 40%;
        }

        .bill-of-supply {
            text-align: right;
            padding-right: 2%;
        }

        .company-details {
            padding: 4px;
            text-align: left;
            padding-left: 2%;
            margin-bottom: 0;
            margin-top: 0;
        }

        .details-label {
            text-align: left;
            padding-left: 1%;
            border: 1px solid black;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <th class="logo" colspan="3">
                <img src="https://amazepays.in/images/logo.png" alt="Company Logo" width="100px"
                    style=" border: 0px solid black;">
            </th>
            <th class="bill-of-supply" colspan="5">
                <h2 style="text-align: center;">Bill of Supply</h2>
            </th>
        </tr>
        <tr class="company-details-row">
            <td class="company-details-column" colspan="3" style="vertical-align: top; border: 0px solid black;">
                <p class="company-details">{{ config('companyDefaultValues.company_official_name') }}</p>
                <p class="company-details">{{ config('companyDefaultValues.company_address') }}</p>
                <p class="company-details">Email: {{ config('companyDefaultValues.company_email') }}</p>
                <p class="company-details">CIN: {{ config('companyDefaultValues.company_cin') }}</p>
                <p class="company-details">PAN: {{ config('companyDefaultValues.company_pan') }}</p>
                <p class="company-details">GSTIN: {{ config('companyDefaultValues.gst_number') }}</p>
            </td>
            <td colspan="5" style="vertical-align: top; padding: 0;  border: 1px solid transparent;">
                <table style="width: 100%; border-collapse: collapse; ">
                    <tbody>
                        <tr>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                1. Invoice Number</td>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                {{ $invoice_number }}</td>
                        </tr>
                        <tr>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                2. Invoice Date</td>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                {{ $invoice_date }}</td>
                        </tr>
                        <tr>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                3. Bank Ref. No.</td>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                {{ $bank_ref_no }}</td>
                        </tr>
                        <tr>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                4. Requester Name</td>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                {{ $billing_name }}</td>
                        </tr>
                        <tr>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                5. Customer Contact No.</td>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                {{ $billing_tel }}</td>
                        </tr>
                        <tr>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                6. Customer Email Id.</td>
                            <td
                                style="height: 35px; padding-left: 16px; text-align: left;width: 50%; border: 1px solid #000;">
                                {{ $billing_email }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td style="font-weight: 600; font-size: 16px; text-align: left; padding-left: 1%" colspan="8"> BILL TO
            </td>
        </tr>
        <tr>
            <td class="company-details-column" colspan="8">
                <p class="company-details">{{ $billing_name }}</p>
                <p class="company-details">{{ $billing_address }}{{ !empty($billing_address_two) ? ', ' . $billing_address_two : '' }}</p>
                <p class="company-details">{{ $billing_city }}, {{ $billing_state }} {{ $billing_zip }}</p>
                <p class="company-details">{{ $billing_country }}</p>
                <p class="company-details">Email: {{ $billing_email }}</p>
                <p class="company-details">Contact: {{ $billing_tel }}</p>
                <p class="company-details">GST No: {{ $gst_number }}</p>
            </td>
        </tr>
        <tr>
            <td style="font-weight: 600; font-size: 16px;" colspan="8">Order Details</td>
        </tr>
        <tr>
            <td style="text-align: left; padding-left: 16px;">Order No.</td>
            <td style="text-align: left; padding-left: 16px;">Product Name</td>
            <td style="text-align: left; padding-left: 16px;">Denomination</td>
            <td style="text-align: left; padding-left: 16px;">Quantity</td>
            <td style="text-align: left; padding-left: 16px;">Total Amount</td>
            <td style="text-align: left; padding-left: 16px;">Discount %</td>
            <td style="text-align: left; padding-left: 16px;">Discount Amount</td>
            <td style="text-align: left; padding-left: 16px;"><b>Net Payable Amount</b></td>
        </tr>
        <tr>
            <td style="text-align: left; padding-left: 16px;">{{ $order_id }}</td>
            <td style="text-align: left; padding-left: 16px;">{{ $cardProductName }}</td>
            <td style="text-align: left; padding-left: 16px;">{{ $denomination }}</td>
            <td style="text-align: left; padding-left: 16px;">{{ $quantity }}</td>
            <td style="text-align: left; padding-left: 16px;">{{ $grand_payable_amount }}</td>
            <td style="text-align: left; padding-left: 16px;">{{ $discount_percentage }}</td>
            <td style="text-align: left; padding-left: 16px;">{{ $discount }}</td>
            <td style="text-align: left; padding-left: 16px;"><b>{{ $amount_payable_after_discount }}</b></td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: left; padding-left: 16px;">Total Amount: {{ $grand_payable_amount }}
            </td>
            <td colspan="2" style="text-align: left; padding-left: 16px;">Total Discount Amount: {{ $discount }}
            </td>
            <td colspan="4" style="font-weight: 600; text-align: left; padding-left: 16px;">Net Amount after
                Discount: {{ $amount_payable_after_discount }}</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: left; padding-left: 16px;">
                <p style="font-weight: 600; margin: 8px 0; font-size: 16px">Remittance Detail</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Beneficiary Name:</span>
                    {{ config('companyDefaultValues.company_official_name') }}</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Bank Name:</span>
                    {{ config('companyDefaultValues.company_bank_name') }}</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Branch:</span>
                    {{ config('companyDefaultValues.company_bank_branch') }}</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Account No:</span>
                    {{ config('companyDefaultValues.company_bank_account_number') }}</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">IFSC Code:</span>
                    {{ config('companyDefaultValues.company_bank_ifsc_code') }}</p>
            </td>
            <td colspan="4" style="text-align: left; padding-left: 16px;">
                <p style="font-weight: 600; margin: 8px 0; font-size: 16px">Terms and Conditions</p>
                <p>
                    {!! \App\Helpers\InvoiceHelper::getFormattedInvoiceTermsAndConditions() !!}
                </p>
            </td>
        </tr>
        <tr>
            <td colspan="8" style="text-align: center; font-size: 16px;">
                <p>This is a System-generated Invoice and Does not Require a Signature</p>
            </td>
        </tr>
    </table>
</body>

</html>
