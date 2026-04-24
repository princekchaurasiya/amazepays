<?php

namespace App\Exports;

use App\Models\OrderBillingSnapshot;
use App\Models\OrderShippingSnapshot;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserPaymentDetailsExport implements FromCollection, WithHeadings
{
    /**
     * Join Users and Payment Details by email
     *
     * @return Collection
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

        $payments = Payment::query()
            ->where('gateway', 'unlimit')
            ->select(['id', 'user_id', 'order_id', 'gateway_payment_id', 'gateway_reference', 'amount_minor', 'currency', 'status', 'created_at'])
            ->get();

        $orderIds = $payments->pluck('order_id')->unique()->values();
        $billingByOrder = OrderBillingSnapshot::query()->whereIn('order_id', $orderIds)->get()->keyBy('order_id');
        $shippingByOrder = OrderShippingSnapshot::query()->whereIn('order_id', $orderIds)->get()->keyBy('order_id');

        // Create a lookup map for payments by user email
        $paymentsByEmail = [];
        foreach ($payments as $payment) {
            $billing = $billingByOrder->get($payment->order_id);
            $email = strtolower(trim((string) ($billing?->email ?? '')));
            if ($email === '' && $payment->user_id) {
                $user = User::find($payment->user_id);
                $email = $user ? strtolower(trim((string) $user->email)) : '';
            }

            if (! empty($email)) {
                if (! isset($paymentsByEmail[$email])) {
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
            if (! empty($userPayments)) {
                return collect($userPayments)->map(function ($payment) use ($user) {
                    $billing = OrderBillingSnapshot::query()->where('order_id', $payment->order_id)->first();
                    $shipping = OrderShippingSnapshot::query()->where('order_id', $payment->order_id)->first();
                    $amount = $payment->amount_minor ? ((int) $payment->amount_minor) / 100 : 0;

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
                        'tracking_id' => $payment->gateway_payment_id ?? '',
                        'bank_ref_no' => $payment->gateway_reference ?? '',
                        'price' => $amount,
                        'qty' => '',
                        'payment_status' => $payment->status ?? '',
                        'order_status' => '',
                        'payment_created_at' => $payment->created_at,

                        // Billing Details from Payment
                        'billing_name' => $billing?->full_name ?? '',
                        'billing_email' => $billing?->email ?? '',
                        'billing_phone' => $billing?->phone ?? '',
                        'billing_address' => $billing?->line1 ?? '',
                        'billing_city' => $billing?->city ?? '',
                        'billing_state' => $billing?->state ?? '',
                        'billing_zip' => $billing?->postal_code ?? '',
                        'billing_country' => $billing?->country ?? '',

                        // Delivery Details
                        'delivery_name' => $shipping?->full_name ?? '',
                        'delivery_telephone' => $shipping?->phone ?? '',
                        'delivery_address' => $shipping?->line1 ?? '',
                        'delivery_city' => $shipping?->city ?? '',
                        'delivery_state' => $shipping?->state ?? '',
                        'delivery_zip' => $shipping?->postal_code ?? '',
                        'delivery_country' => $shipping?->country ?? '',
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
