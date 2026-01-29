<?php

namespace App\Exports;

use App\Models\User;
use App\Models\UnlimitPayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserPaymentDetailsExport implements FromCollection, WithHeadings
{
    /**
     * Join Users and Payment Details by email
     * 
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Get all users
        $users = User::select(
            'id',
            'name',
            'email',
            'mobile',
            'billing_address',
            'billing_address_two',
            'billing_city',
            'billing_zip',
            'billing_state',
            'billing_country',
            'billing_gst_number',
            'created_at as user_created_at'
        )->get();

        // Get all payments with billing details
        $payments = UnlimitPayment::select(
            'id',
            'user_id',
            'order_id',
            'tracking_id',
            'bank_ref_no',
            'billing_name',
            'billing_address',
            'billing_city',
            'billing_state',
            'billing_zip',
            'billing_country',
            'billing_tel',
            'billing_email',
            'delivery_name',
            'delivery_address',
            'delivery_city',
            'delivery_state',
            'delivery_zip',
            'delivery_country',
            'delivery_tel',
            'price',
            'qty',
            'payment_status',
            'order_status',
            'created_at as payment_created_at'
        )->get();

        // Create a lookup map for payments by user email
        $paymentsByEmail = [];
        foreach ($payments as $payment) {
            // Use billing_email from payment, or fallback to user email
            $email = strtolower(trim($payment->billing_email ?? ''));
            if (empty($email) && $payment->user_id) {
                // If no billing email, try to get from user
                $user = User::find($payment->user_id);
                if ($user) {
                    $email = strtolower(trim($user->email));
                }
            }
            
            if (!empty($email)) {
                if (!isset($paymentsByEmail[$email])) {
                    $paymentsByEmail[$email] = [];
                }
                $paymentsByEmail[$email][] = $payment;
            }
        }

        // Join users with their payments
        $mergedData = $users->map(function ($user) use ($paymentsByEmail) {
            $userEmail = strtolower(trim($user->email));
            $userPayments = $paymentsByEmail[$userEmail] ?? [];

            // If user has payments, create a row for each payment
            if (!empty($userPayments)) {
                return collect($userPayments)->map(function ($payment) use ($user) {
                    return [
                        // User Data
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'user_mobile' => $user->mobile,
                        'user_billing_address' => $user->billing_address,
                        'user_billing_address_two' => $user->billing_address_two,
                        'user_billing_city' => $user->billing_city,
                        'user_billing_zip' => $user->billing_zip,
                        'user_billing_state' => $user->billing_state,
                        'user_billing_country' => $user->billing_country,
                        'user_billing_gst_number' => $user->billing_gst_number,
                        'user_created_at' => $user->user_created_at,
                        
                        // Payment Data
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'tracking_id' => $payment->tracking_id,
                        'bank_ref_no' => $payment->bank_ref_no,
                        'price' => (float) ($payment->price ?? 0),
                        'qty' => (int) ($payment->qty ?? 0),
                        'payment_status' => $payment->payment_status ?? '',
                        'order_status' => $payment->order_status ?? '',
                        'payment_created_at' => $payment->payment_created_at,
                        
                        // Billing Details from Payment
                        'billing_name' => $payment->billing_name ?? '',
                        'billing_email' => $payment->billing_email ?? '',
                        'billing_phone' => $payment->billing_tel ?? '',
                        'billing_address' => $payment->billing_address ?? '',
                        'billing_city' => $payment->billing_city ?? '',
                        'billing_state' => $payment->billing_state ?? '',
                        'billing_zip' => $payment->billing_zip ?? '',
                        'billing_country' => $payment->billing_country ?? '',
                        
                        // Delivery Details
                        'delivery_name' => $payment->delivery_name ?? '',
                        'delivery_telephone' => $payment->delivery_tel ?? '',
                        'delivery_address' => $payment->delivery_address ?? '',
                        'delivery_city' => $payment->delivery_city ?? '',
                        'delivery_state' => $payment->delivery_state ?? '',
                        'delivery_zip' => $payment->delivery_zip ?? '',
                        'delivery_country' => $payment->delivery_country ?? '',
                    ];
                });
            } else {
                // User with no payments - still include user data
                return [[
                    // User Data
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_mobile' => $user->mobile,
                    'user_billing_address' => $user->billing_address,
                    'user_billing_address_two' => $user->billing_address_two,
                    'user_billing_city' => $user->billing_city,
                    'user_billing_zip' => $user->billing_zip,
                    'user_billing_state' => $user->billing_state,
                    'user_billing_country' => $user->billing_country,
                    'user_billing_gst_number' => $user->billing_gst_number,
                    'user_created_at' => $user->user_created_at,
                    
                    // Payment Data (empty)
                    'payment_id' => '',
                    'order_id' => '',
                    'tracking_id' => '',
                    'bank_ref_no' => '',
                    'price' => '',
                    'qty' => '',
                    'payment_status' => '',
                    'order_status' => '',
                    'payment_created_at' => '',
                    
                    // Billing Details from Payment (empty)
                    'billing_name' => '',
                    'billing_email' => '',
                    'billing_phone' => '',
                    'billing_address' => '',
                    'billing_city' => '',
                    'billing_state' => '',
                    'billing_zip' => '',
                    'billing_country' => '',
                    
                    // Delivery Details (empty)
                    'delivery_name' => '',
                    'delivery_telephone' => '',
                    'delivery_address' => '',
                    'delivery_city' => '',
                    'delivery_state' => '',
                    'delivery_zip' => '',
                    'delivery_country' => '',
                ]];
            }
        })->flatten(1);

        return new Collection($mergedData);
    }

    public function headings(): array
    {
        return [
            // User Data Headers
            'User ID',
            'User Name',
            'User Email',
            'User Mobile',
            'User Billing Address',
            'User Billing Address Two',
            'User Billing City',
            'User Billing Zip',
            'User Billing State',
            'User Billing Country',
            'User Billing GST Number',
            'User Created At',
            
            // Payment Data Headers
            'Payment ID',
            'Order ID',
            'Tracking ID',
            'Bank Ref No',
            'Price',
            'Quantity',
            'Payment Status',
            'Order Status',
            'Payment Created At',
            
            // Billing Details from Payment
            'Billing Name',
            'Billing Email',
            'Billing Phone',
            'Billing Address',
            'Billing City',
            'Billing State',
            'Billing Zip',
            'Billing Country',
            
            // Delivery Details
            'Delivery Name',
            'Delivery Telephone',
            'Delivery Address',
            'Delivery City',
            'Delivery State',
            'Delivery Zip',
            'Delivery Country',
        ];
    }
}
