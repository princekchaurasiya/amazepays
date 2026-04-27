<?php

namespace App\Http\Controllers;

use App\Helpers\ProductImageHelper;
use App\Models\Order;
use App\Models\Product;
use App\Services\Order\WoohooApiService;
use App\Services\Order\WoohooGiftCardPersister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ViewCardDetailsController extends Controller
{
    public function index(Request $request, $orderId = null)
    {
        try {
            // Get orderId from route parameter if not provided in request
            $orderId = $orderId ?? $request->input('orderId');

            if (! $orderId) {
                return redirect()->back()->with('error', 'Invalid order ID');
            }

            // Get order with line items + catalog product for imagery; prefer normalized gift_cards for card data.
            $orderData = Order::query()
                ->with(['giftCards', 'items.product', 'providerOrders'])
                ->where('user_id', auth()->id())
                ->whereHas('providerOrders', function ($q) use ($orderId) {
                    $q->where('provider', 'woohoo')->where('provider_order_id', (string) $orderId);
                })
                ->firstOrFail();

            $cardsArray = app(WoohooGiftCardPersister::class)->displayCardsForOrder($orderData);

            // If cards are still empty, fetch from Woohoo using Phase-3 identifiers:
            // - refno is the Woohoo reference number we sent (orders.order_number)
            // - orderId is provider_orders.provider_order_id (route param)
            if (empty($cardsArray)) {
                $refno = (string) ($orderData->order_number ?? '');
                $providerOrderId = (string) ($orderId ?? '');

                Log::info('Cards not found in DB; attempting Woohoo fetch', [
                    'order_id' => $orderData->id,
                    'refno' => $refno,
                    'provider_order_id' => $providerOrderId,
                ]);

                try {
                    /** @var WoohooApiService $api */
                    $api = app(WoohooApiService::class);

                    // Check status by refno (idempotent, handles PROCESSING).
                    $status = $refno !== '' ? $api->getStatusByReferenceNumberLightweight($refno) : [];
                    $st = strtoupper((string) ($status['status'] ?? ''));

                    if ($st === 'COMPLETE' && $providerOrderId !== '') {
                        $combined = $api->callCardActivation(['orderId' => $providerOrderId, 'status' => 'COMPLETE']);
                        $cards = is_array($combined['cards'] ?? null) ? $combined['cards'] : [];

                        if ($cards !== []) {
                            app(WoohooGiftCardPersister::class)->syncWoohooCards($orderData->fresh(), $cards);
                            $cardsArray = app(WoohooGiftCardPersister::class)->displayCardsForOrder($orderData->fresh());
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Woohoo card fetch failed in view', [
                        'order_id' => $orderData->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (empty($cardsArray)) {
                Log::error('No card data available', [
                    'order_id' => $orderData->id,
                    'woohoo_order_id' => $orderData->woohoo_order_id,
                ]);

                return redirect()->back()->with('error', 'Card details are not available. Please contact support.');
            }

            // Get product image using ProductImageHelper (Phase 3: product via first order line).
            $productImage = null;
            $lineProduct = $orderData->items->first()?->product;
            if ($lineProduct instanceof Product) {
                $productImage = ProductImageHelper::getProductImage($lineProduct);
            }

            return Inertia::render('Storefront/OrderDetail', [
                'cardArray' => $cardsArray,
                'productImage' => $productImage,
                'order' => $orderData->only([
                    'id', 'order_number', 'status',
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('View card details error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Unable to retrieve order details');
        }
    }
}
