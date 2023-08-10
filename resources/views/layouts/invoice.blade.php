<!DOCTYPE html>
<html>

<head>
    <title>Invoice</title>
    <style>
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
            width: 50%;
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

        .details-input-table {}
    </style>
</head>

<body>
    <table>
        <tr>
            <th class="logo" colspan="3">
                <img src="{{ asset('images/logo.png') }}" alt="Company Logo" width="100px">
            </th>
            <th class="bill-of-supply" colspan="3">
                <h2>Bill of Supply</h2>
            </th>
        </tr>
        <tr class="company-details-row">
            <td class="company-details-column" colspan="3">
                <p class="company-details">{{ config('companyDefaultValues.company_name') }}</p>
                <p class="company-details">{{ config('companyDefaultValues.company_address') }}</p>
                <p class="company-details">Email: {{ config('companyDefaultValues.comapny_email') }}</p>
                <p class="company-details">CIN: {{ config('companyDefaultValues.company_cin') }}</p>
                <p class="company-details">PAN: {{ config('companyDefaultValues.company_pan') }}</p>
                <p class="company-details">GSTIN: {{ config('companyDefaultValues.gst_number') }}</p>
            </td>
            <td style="" colspan="3">
                <table>
                    <tr>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">1. Invoice Number</td>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">AMAZE-123987</td>
                    </tr>
                    <tr>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">2. Invoice Date</td>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">23/08/23</td>
                    </tr>
                    <tr>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">3. Bank Ref. No.</td>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">KOTAK123</td>
                    </tr>
                    <tr>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">4. Requester Name</td>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">Shamsher</td>
                    </tr>
                    <tr>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">5. Customer Contact No.</td>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">9988776655</td>
                    </tr>
                    <tr>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">6. Customer Email Id.</td>
                        <td style="height: 35px; padding-left: 16px; text-align: left;">test@test.com</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="font-weight: 600; font-size: 24px; text-align: left; padding-left: 1%" colspan="3">Receiver Details (Bill to)</td>
            <td style="font-weight: 600; font-size: 24px; text-align: left; padding-left: 1%" colspan="3">Receiver Details (Ship to)</td>
        </tr>
        <tr>
            <td class="company-details-column" colspan="3">
                <p class="company-details">{{ config('companyDefaultValues.company_name') }}</p>
                <p class="company-details">{{ config('companyDefaultValues.company_address') }}</p>
                <p class="company-details">Email: {{ config('companyDefaultValues.comapny_email') }}</p>
                <p class="company-details">Contact: 9892188777</p>
            </td>
            <td class="company-details-column" colspan="3">
                <p class="company-details">{{ config('companyDefaultValues.company_name') }}</p>
                <p class="company-details">{{ config('companyDefaultValues.company_address') }}</p>
                <p class="company-details">Email: {{ config('companyDefaultValues.comapny_email') }}</p>
                <p class="company-details">Contact: 9892188777</p>
            </td>
        </tr>
        <tr>
            <td style="font-weight: 600; font-size: 24px;" colspan="6">Order Details</td>
        </tr>
        <tr>
            <td style="text-align: left; padding-left: 16px;">Sr. No.</td>
            <td style="text-align: left; padding-left: 16px;">Product Name</td>
            <td style="text-align: left; padding-left: 16px;">Quantity</td>
            <td style="text-align: left; padding-left: 16px;">Price</td>
            <td style="text-align: left; padding-left: 16px;">Discount</td>
            <td style="text-align: left; padding-left: 16px;">Amount</td>
        </tr>
        <tr>
            <td style="text-align: left; padding-left: 16px;">1</td>
            <td style="text-align: left; padding-left: 16px;">Amazon Voucher</td>
            <td style="text-align: left; padding-left: 16px;">10</td>
            <td style="text-align: left; padding-left: 16px;">1000</td>
            <td style="text-align: left; padding-left: 16px;">10%</td>
            <td style="text-align: left; padding-left: 16px;">10000</td>
        </tr>
        <tr>
            <td style="text-align: left; padding-left: 16px;">2</td>
            <td style="text-align: left; padding-left: 16px;">Shopperstop Voucher</td>
            <td style="text-align: left; padding-left: 16px;">5</td>
            <td style="text-align: left; padding-left: 16px;">2000</td>
            <td style="text-align: left; padding-left: 16px;">20%</td>
            <td style="text-align: left; padding-left: 16px;">10000</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: left; padding-left: 16px;">Total Amount: 20,000</td>
            <td colspan="2" style="text-align: left; padding-left: 16px;">Total Discount: 3000</td>
            <td colspan="2" style="font-weight: 600; text-align: left; padding-left: 16px;">Gross Amount after Discount: 17000</td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: left; padding-left: 16px;">
                <p style="font-weight: 600; margin: 8px 0; font-size: 24px">Remittance Detail</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Beneficiary Name:</span> {{ config('companyDefaultValues.company_name') }}</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Bank Name:</span> KOTAK Bank</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Branch:</span> {{ config('companyDefaultValues.company_bank_branch') }}</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">Account No:</span> {{ config('companyDefaultValues.company_bank_account_number') }}</p>
                <p style="margin: 2px 0;"><span style="font-weight: 600">IFSC Code:</span> {{ config('companyDefaultValues.company_bank_ifsc_code') }}</p>
            </td>
            <td colspan="3" style="text-align: left; padding-left: 16px;">
                <p style="font-weight: 600; margin: 8px 0; font-size: 24px">Terms and Conditions</p>
                <p>We declare that this invoice shows the actual price of the goods described and that all particulars are treu and correct</p>
            </td>
        </tr>
        <tr>
            <td colspan="6" style="text-align: center; font-size: 24px;">
                <p>This is a System-generated Invoice and Does not Require a Signature</p>
            </td>
        </tr>
    </table>
</body>

</html>
