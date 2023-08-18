<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\CcAvenuePayment;

class PaymentDetailsExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $payments = CcAvenuePayment::select("order_id", "tracking_id", "bank_ref_no", "billing_details", "price", "qty", "delivery_details")->get();

        
         // Transform the billing_details and delivery_details JSON data and create a new collection
         $transformedPayments = $payments->map(function ($payment) {
            $billingDetails = json_decode($payment->billing_details);
            $deliveryDetails = json_decode($payment->delivery_details);


        //  dd($billingDetails);

            return [
                'order_id' =>(int) $payment->order_id,
                'tracking_id' => (int)$payment->tracking_id,
                'bank_ref_no' => (int)$payment->bank_ref_no,
                'billing_name' => $billingDetails->firstname,
                'billing_email' => $billingDetails->email,
                'billing_phone' => $billingDetails->telephone,
                'delivery_firstname' => $deliveryDetails->firstname,
                'delivery_email' => $deliveryDetails->email,
                'delivery_telephone' => $deliveryDetails->telephone,
                'delivery_line1' => $deliveryDetails->line1,
                'delivery_city' => $deliveryDetails->city,
                'delivery_region' => $deliveryDetails->region,
                'delivery_country' => $deliveryDetails->country,
                'delivery_postcode' => $deliveryDetails->postcode,
                'price' => (float)$payment->price,
                'qty' => (int)$payment->qty,
                
            ];
        });

        
// dd($transformedPayments);
        return $transformedPayments;
    }

    public function headings(): array
    {
        return ["Order No", "Tracking ID", "Bank Ref No.", "Billing Name", "Billing Email", "Billing Phone",
            "Delivery Firstname", "Delivery Email", "Delivery Telephone", "Delivery Line1", "Delivery City",
            "Delivery Region", "Delivery Country", "Delivery Postcode", "Price", "Quantity"];
    }
}
