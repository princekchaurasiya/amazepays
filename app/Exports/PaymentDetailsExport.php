<?php

namespace App\Exports;

use App\Models\OrderBillingSnapshot;
use App\Models\OrderShippingSnapshot;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentDetailsExport implements FromCollection, WithHeadings
{
    /**
     * @return Collection
     */
    public function collection()
    {
        $payments = Payment::query()
            ->where('gateway', 'unlimit')
            ->select(['id', 'order_id', 'gateway_payment_id', 'gateway_reference', 'amount_minor', 'currency', 'status', 'created_at'])
            ->latest('id')
            ->get();

        $orderIds = $payments->pluck('order_id')->unique()->values();
        $billingByOrder = OrderBillingSnapshot::query()
            ->whereIn('order_id', $orderIds)
            ->get()
            ->keyBy('order_id');
        $shippingByOrder = OrderShippingSnapshot::query()
            ->whereIn('order_id', $orderIds)
            ->get()
            ->keyBy('order_id');

        return $payments->map(function (Payment $payment) use ($billingByOrder, $shippingByOrder) {
            $billing = $billingByOrder->get($payment->order_id);
            $shipping = $shippingByOrder->get($payment->order_id);

            return [
                'order_id' => (int) $payment->order_id,
                'gateway_payment_id' => (string) ($payment->gateway_payment_id ?? ''),
                'gateway_reference' => (string) ($payment->gateway_reference ?? ''),
                'billing_name' => (string) ($billing?->full_name ?? ''),
                'billing_email' => (string) ($billing?->email ?? ''),
                'billing_phone' => (string) ($billing?->phone ?? ''),
                'delivery_name' => (string) ($shipping?->full_name ?? ''),
                'delivery_email' => '',
                'delivery_telephone' => (string) ($shipping?->phone ?? ''),
                'delivery_line1' => (string) ($shipping?->line1 ?? ''),
                'delivery_city' => (string) ($shipping?->city ?? ''),
                'delivery_region' => (string) ($shipping?->state ?? ''),
                'delivery_country' => (string) ($shipping?->country ?? ''),
                'delivery_postcode' => (string) ($shipping?->postal_code ?? ''),
                'amount' => $payment->amount_minor ? ((int) $payment->amount_minor) / 100 : 0,
                'currency' => (string) ($payment->currency ?? 'INR'),
                'status' => (string) ($payment->status ?? ''),
                'created_at' => (string) $payment->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Order No',
            'Gateway Payment ID',
            'Gateway Reference',
            'Billing Name',
            'Billing Email',
            'Billing Phone',
            'Delivery Name',
            'Delivery Email',
            'Delivery Telephone',
            'Delivery Line1',
            'Delivery City',
            'Delivery Region',
            'Delivery Country',
            'Delivery Postcode',
            'Amount',
            'Currency',
            'Payment Status',
            'Payment Created At',
        ];
    }
}
