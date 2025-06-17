<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return User::select(
            'id',
            'role_id',
            'name',
            'email',
            'billing_address',
            'billing_address_two',
            'billing_city',
            'billing_zip',
            'billing_state',
            'billing_country',
            'billing_gst_number',
            'mobile'
        )->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Role ID',
            'Name',
            'Email',
            'Billing Address',
            'Billing Address Two',
            'Billing City',
            'Billing Zip',
            'Billing State',
            'Billing Country',
            'Billing GST Number',
            'Mobile',
        ];
    }
}
