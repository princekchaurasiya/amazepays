<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Payment::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Payment Method',
            'Merchant Order ID',
            'Merchant Order Description',
            'Payment ID',
            'Type',
            'Status',
            'Amount',
            'Currency',
            'Created At API',
            'Decline Reason',
            'Decline Code',
            'Is 3D',
            'ARN',
            'RRN',
            'Original Amount',
            'Masked PAN',
            'Holder',
            'Issuing Country Code',
            'Customer Email',
            'Customer IP',
            'Customer Locale',
            'Created At',
            'Updated At',
        ];
    }
}
