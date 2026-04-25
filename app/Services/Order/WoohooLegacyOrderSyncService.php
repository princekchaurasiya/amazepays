<?php

namespace App\Services\Order;

use App\Helpers\ProductImageHelper;
use App\Models\Billing;
use App\Models\Order;
use App\Models\OrderSummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Legacy Woohoo order sync + fulfillment side-effects.
 *
 * Extracted from WoohooOrderController to keep controller thin (Phase 2).
 * Behavior is intentionally preserved.
 */
final class WoohooLegacyOrderSyncService
{
    public function __construct(
        private WoohooApiService $woohooApi,
        private WoohooLegacyNotificationService $legacyNotifications,
        private ProviderOrderRecorder $providerOrderRecorder,
        private WoohooGiftCardPersister $giftCardPersister,
    ) {}

    public function handleSuccessFullOrder(mixed $orderCreatedResponse): void
    {
        Log::info('Woohoo order response received; starting sync');

        $orderId = $this->syncOrderFromWoohooResponse($orderCreatedResponse);

        Log::info('Woohoo order synced successfully', ['order_id' => $orderId]);

        $order = DB::table('orders')
            ->join('payments', function ($join) {
                $join->on('payments.order_id', '=', 'orders.id')
                    ->where('payments.gateway', 'unlimit');
            })
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->join('products', function ($join) {
                $join->on(
                    DB::raw('products.sku COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('order_items.sku_snapshot COLLATE utf8mb4_unicode_ci'),
                )->whereColumn('products.tenant_id', 'orders.tenant_id');
            })
            ->where('orders.id', $orderId)
            ->orderBy('order_items.id')
            ->select('orders.*', 'payments.*', 'products.*')
            ->first();

        $orderData = $order ? json_decode(json_encode($order), true) : [];
        $Order = Order::find($orderId);

        if (is_object($orderCreatedResponse)) {
            $orderCreatedResponse = json_decode(json_encode($orderCreatedResponse), true);
        }
        if (! is_array($orderCreatedResponse)) {
            $orderCreatedResponse = [];
        }

        if ($Order && ! empty($orderCreatedResponse['orderId'])) {
            $Order->woohoo_order_id = $orderCreatedResponse['orderId'] ?? null;
            $Order->save();
        }

        $imageUrl = ProductImageHelper::getProductImage($order);
        $imageUrl = str_replace('\\', '/', (string) $imageUrl);
        $imageUrl = ltrim($imageUrl, '/');
        $imageUrl = str_replace('storage/', '', $imageUrl);

        $liveUrl = rtrim(env('LIVE_URL', 'https://amazepays.in'), '/');
        $smallImageUrl = $liveUrl.'/storage/'.$imageUrl;

        try {
            $ch = curl_init($smallImageUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($responseCode !== 200) {
                if (! empty($orderData['images'])) {
                    $images = json_decode((string) ($orderData['images'] ?? ''), true);
                    if (is_array($images) && isset($images['small'])) {
                        $smallImageUrl = (string) $images['small'];
                    } else {
                        $smallImageUrl = null;
                    }
                } else {
                    $smallImageUrl = null;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error checking image accessibility: '.$e->getMessage());
            $smallImageUrl = null;
        }

        $financialYear = $this->getFinancialYear();
        $invoiceNumber = 'FRB2C-'.$financialYear.'-'.$orderId;
        $invoiceDate = date('d-m-Y');

        $cardsArray = [];
        if ($Order) {
            $cardsArray = $this->giftCardPersister->displayCardsForOrder($Order->fresh(['giftCards']));
        }

        if (empty($cardsArray)) {
            try {
                $woohooOrderId = $Order?->woohoo_order_id ?? null;
                if ($woohooOrderId) {
                    $combined = $this->woohooApi->callCardActivation(['orderId' => $woohooOrderId, 'status' => 'COMPLETE']);

                    if (is_object($combined)) {
                        $combined = json_decode(json_encode($combined), true);
                    }

                    if (is_array($combined)) {
                        if (isset($combined['cards']) && is_array($combined['cards'])) {
                            $cardsArray = $combined['cards'];
                        } elseif (isset($combined['cardnumber']) || isset($combined['cardpin'])) {
                            $singleCard = [
                                'cardNumber' => $combined['cardnumber'] ?? $combined['cardNumber'] ?? null,
                                'cardPin' => $combined['cardpin'] ?? $combined['cardPin'] ?? null,
                                'amount' => $combined['amount'] ?? null,
                                'activationCode' => $combined['activation_code'] ?? $combined['activationCode'] ?? null,
                                'activationUrl' => $combined['activation_url'] ?? $combined['activationUrl'] ?? null,
                                'validity' => $combined['validity'] ?? null,
                            ];
                            if (! empty($singleCard['cardNumber']) || ! empty($singleCard['cardPin'])) {
                                $cardsArray = [$singleCard];
                            }
                        }

                        if (! empty($cardsArray)) {
                            $persistOrder = Order::query()->find($orderId);
                            if ($persistOrder) {
                                $this->giftCardPersister->syncWoohooCards($persistOrder->fresh(), $cardsArray);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Failed to fetch cards after decrypt failure', [
                    'error' => $e->getMessage(),
                    'woohoo_order_id' => $orderData['woohoo_order_id'] ?? null,
                ]);
            }
        }

        // cards now live in normalized gift_cards; legacy orders.cards blob is retired.

        Order::where('id', $orderId)->update(['invoice_number' => $invoiceNumber]);

        $billingInfo = Billing::latest()->first();
        $billingdata = $billingInfo ? json_decode(json_encode($billingInfo), true) : [];

        $prepareMailDetails = [
            'name' => $orderData['sender_first_name'] ?? ($billingdata['billing_name'] ?? null),
            'order_id' => $orderData['woohoo_order_id'] ?? null,
            'reference_id' => $orderData['id'] ?? null,
            'order_date' => $orderData['created_at'] ?? null,
            'billing_name' => $billingdata['billing_name'] ?? ($orderData['sender_first_name'] ?? null),
            'billing_email' => $billingdata['billing_email'] ?? ($orderData['sender_email'] ?? null),
            'billing_tel' => $billingdata['billing_tel'] ?? ($orderData['sender_phone_no'] ?? null),
            'billing_address' => $billingdata['billing_address'] ?? ($orderData['sender_address_1'] ?? null),
            'billing_address_two' => $billingdata['billing_address_two'] ?? ($orderData['sender_address_2'] ?? null),
            'billing_city' => $billingdata['billing_city'] ?? ($orderData['sender_city'] ?? null),
            'billing_state' => $billingdata['billing_state'] ?? ($orderData['sender_state'] ?? null),
            'billing_country' => $billingdata['billing_country'] ?? 'India',
            'billing_zip' => $billingdata['billing_zip'] ?? ($orderData['sender_post_code'] ?? null),
            'payment_mode' => $orderData['payment_mode'] ?? null,
            'bank_ref_no' => $orderData['bank_ref_no'] ?? null,
            'grand_payable_amount' => $orderData['grand_payable_amount'] ?? null,
            'gst_number' => $billingdata['billing_gst_number'] ?? ($orderData['gst_number'] ?? 'Unregistered'),
            'discount' => $orderData['discounted_amount_value'] ?? null,
            'amount_payable_after_discount' => $orderData['amount_payable_after_discount'] ?? null,
            'contact_person' => $orderData['sender_first_name'] ?? null,
            'shipping_address' => ($orderData['sender_address_1'] ?? '').' '.($orderData['sender_address_2'] ?? '').', '.($orderData['sender_city'] ?? '').', '.($orderData['sender_state'] ?? '').' '.($orderData['sender_post_code'] ?? ''),
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'cardSku' => $orderData['sku'] ?? null,
            'cardProductName' => $orderData['name'] ?? null,
            'shipToName' => $orderData['receiver_name'] ?? ($orderData['sender_first_name'] ?? null),
            'shipToEmail' => $orderData['receiver_email'] ?? ($orderData['sender_email'] ?? null),
            'shipToContactNo' => $orderData['receiver_mobile'] ?? ($orderData['sender_phone_no'] ?? null),
            'quantity' => $orderData['quantity'] ?? 1,
            'smallImageUrl' => $smallImageUrl,
            'giftSendOption' => $orderData['gift_send_option'] ?? 'buy_for_self',
            'denomination' => $orderData['denomination'] ?? null,
            'discount_percentage' => $orderData['discount_percentage'] ?? null,
        ];

        $senderName = $orderData['sender_first_name'] ?? ($billingdata['billing_name'] ?? 'Customer');
        $orderIdForSms = $orderData['woohoo_order_id'] ?? ($orderData['id'] ?? null);
        $orderAmountForSms = $orderData['amount'] ?? ($orderData['grand_payable_amount'] ?? null);
        $productNameForSms = $orderData['name'] ?? ($orderData['sku'] ?? 'Gift Card');
        $billingTelForSms = $billingdata['billing_tel'] ?? ($orderData['sender_phone_no'] ?? null);
        $shipToNameForSms = $orderData['receiver_name'] ?? $senderName;
        $shipToContactForSms = $orderData['receiver_mobile'] ?? $billingTelForSms;

        $prepareSmsDetails = [
            'name' => $senderName,
            'order_id' => $orderIdForSms,
            'reference_id' => $orderData['id'] ?? null,
            'order_date' => $orderData['created_at'] ?? null,
            'billing_name' => $billingdata['billing_name'] ?? $senderName,
            'order_amount' => $orderAmountForSms,
            'cardSku' => $orderData['sku'] ?? null,
            'cardProductName' => $productNameForSms,
            'shipToName' => $shipToNameForSms,
            'shipToContactNo' => $shipToContactForSms,
            'grand_payable_amount' => $orderData['grand_payable_amount'] ?? null,
            'perOrderQuantity' => $orderData['quantity'] ?? 1,
            'giftSendOption' => $orderData['gift_send_option'] ?? 'buy_for_self',
            'billing_tel' => $billingTelForSms,
        ];

        $deliveryMode = $orderData['delivery_mode'] ?? 'both';
        if ($deliveryMode === 'both') {
            $this->legacyNotifications->sendTransactionMail($prepareMailDetails);
            $this->legacyNotifications->sendGiftMail($prepareMailDetails, $cardsArray);
            $this->legacyNotifications->sendTransactionalMessage($prepareSmsDetails);
            $this->legacyNotifications->sendGiftMessage($prepareSmsDetails, $cardsArray);
        } elseif ($deliveryMode === 'email') {
            $this->legacyNotifications->sendTransactionMail($prepareMailDetails);
            $this->legacyNotifications->sendTransactionalMessage($prepareSmsDetails);
            $this->legacyNotifications->sendGiftMail($prepareMailDetails, $cardsArray);
        } elseif ($deliveryMode === 'mobile') {
            $this->legacyNotifications->sendTransactionMail($prepareMailDetails);
            $this->legacyNotifications->sendTransactionalMessage($prepareSmsDetails);
            $this->legacyNotifications->sendGiftMessage($prepareSmsDetails, $cardsArray);
        }
    }

    public function syncOrderFromWoohooResponse(mixed $orderCreatedResponse): mixed
    {
        $orderId = session('checkout_order_id');
        $refno = session('checkout_refno');

        Log::info('Woohoo sync: reference number present', ['order_id' => $orderId, 'has_refno' => ! empty($refno)]);

        $isSuccessful = false;

        if (is_object($orderCreatedResponse)) {
            $orderCreatedResponse = json_decode(json_encode($orderCreatedResponse), true);
        }

        if (! is_array($orderCreatedResponse)) {
            Log::error('Invalid Woohoo order response format received', [
                'order_id' => $orderId,
                'refno' => $refno,
            ]);

            return null;
        }

        $order = Order::where('refno', $orderCreatedResponse['refno'])->first();
        if (! $order) {
            Log::error('Woohoo sync failed: order not found by refno', [
                'refno' => $orderCreatedResponse['refno'] ?? null,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $normalizedStatus = strtoupper($orderCreatedResponse['status'] ?? '');

        $order->update([
            'woohoo_order_id' => $orderCreatedResponse['orderId'] ?? null,
            'order_status' => $normalizedStatus,
            'order_cancel' => $orderCreatedResponse['cancel'] ?? [],
            'order_payment' => $orderCreatedResponse['payments'] ?? null,
            'woohoo_currency_snapshot' => $orderCreatedResponse['currency'] ?? null,
            'additional_txn_fields' => $orderCreatedResponse['additionalTxnFields'] ?? null,
        ]);

        $existingOrderSummary = OrderSummary::where('order_id', $order->id)->first();
        if ($existingOrderSummary) {
            $existingOrderSummary->fulfilment_status = $normalizedStatus;
            $existingOrderSummary->save();
        }

        $order->refresh();
        $this->providerOrderRecorder->recordWoohooFromApiResponse($order, $orderCreatedResponse);

        $cardsRaw = $orderCreatedResponse['cards'] ?? [];
        if (is_array($cardsRaw) && $cardsRaw !== []) {
            $this->giftCardPersister->syncWoohooCards($order->fresh(), $cardsRaw);
        }

        return $order->id;
    }

    private function getFinancialYear(): string
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        if ((int) $currentMonth >= 4) {
            $nextYear = (int) $currentYear + 1;

            return $currentYear.'/'.substr((string) $nextYear, -2);
        }

        $previousYear = (int) $currentYear - 1;

        return $previousYear.'/'.substr((string) $currentYear, -2);
    }
}
