<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QsOrder;
use App\Models\QsProduct;
use App\Models\OrderSummary;
use App\Models\UnlimitPayment;
use App\Models\Billing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Http\Controllers\UnlimitPaymentController;
use App\Helpers\ProductHelper;
use App\Helpers\CheckoutHelper;

class ProductPageController extends Controller
{
    public function saveGiftCardFormValues(Request $request)
    {

        $formData = $request->all();
        session(['giftCardFormValues' => $formData]);


        return response()->json(['status' => 'success']);
    }

    public function storePayNowData(Request $request, $slug)
    {
        // Handle GET request - show checkout form
        if ($request->isMethod('get')) {
            return $this->showCheckoutForm($request, $slug);
        }
        
        // Handle POST request - process order and payment

        // Fetch product by slug
        $product = QsProduct::where('url', $slug)->firstOrFail();

        // If sending as a gift, check if recipient is blocked
        if ($request->gift_send_option === 'send_as_gift' && $request->receiver_mobile) {
            $recipient = \App\Models\User::where('mobile', $request->receiver_mobile)->first();
            if ($recipient && !$recipient->can_receive_gifts) {
                Log::warning('Attempted to send gift to self', [
                    'sender_id' => Auth::id(),
                    'recipient_mobile' => $request->receiver_mobile
                ]);
                return response()->view('errors.self-gift', [
                    'blockType' => 'recipient',
                    'phone' => $request->receiver_mobile,
                    'reason' => $recipient->restriction_reason
                ], 403);
            }
        }

        // Retrieve the checkout session data if available
        $checkoutData = session('checkout_data', []);

        // Price is already decoded by model accessor (uses ProductHelper internally)

        // Validation rules for the form
        $rules = [
            'denomination' => [
                'required',
                function ($attribute, $value, $fail) use ($product) {

                    // If $product->price is an object, convert it to an array
                    $priceData = (array) $product->price;

                    // Check for valid price data
                    if (!is_array($priceData)) {
                        Log::warning('Invalid price format', ['price' => $product->price]);
                        $fail('Invalid product price configuration.');
                        return;
                    }

                    // Default to 'RANGE' if type is missing
                    $priceType = $priceData['type'] ?? 'RANGE';

                    // Now handle SLAB or RANGE validation
                    if ($priceType === 'SLAB') {
                        // Validate SLAB type denominations
                        $denominations = $priceData['denominations'] ?? [];
                        if (!in_array((string) $value, $denominations)) {
                            Log::warning('Invalid SLAB denomination', [
                                'denomination' => $value,
                                'allowed' => $denominations
                            ]);
                            $fail('Invalid denomination value. Allowed values are: ' . implode(', ', $denominations));
                        }
                    } elseif ($priceType === 'RANGE') {
                        // Validate RANGE type price range
                        $minPrice = $priceData['min'] ?? $product->minPrice;
                        $maxPrice = $priceData['max'] ?? $product->maxPrice;

                        // Default to the range if missing
                        if ($minPrice === null || $maxPrice === null) {
                            Log::warning('Missing min/max values for RANGE type, using default min/max', [
                                'minPrice' => $minPrice,
                                'maxPrice' => $maxPrice,
                                'product_id' => $product->id
                            ]);
                        }

                        if ($value < $minPrice || $value > $maxPrice) {
                            Log::warning('Denomination out of RANGE', [
                                'denomination' => $value,
                                'min' => $minPrice,
                                'max' => $maxPrice
                            ]);
                            $fail("The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.");
                        }
                    } else {
                        Log::warning('Unknown price type', ['priceType' => $priceType]);
                        $fail('Invalid price configuration.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:1|max:10',
            'gift_send_option' => 'required|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|string|max:500',
        ];



        $request->merge(['delivery_mode' => 'both']);

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()]);
            return back()->withErrors($validator)->withInput();
        }

        // Calculate total purchases for the current month
        $userId = Auth::id();
        $sku = $product->sku;

        // Safely handle case where qs_orders table doesn't exist yet (e.g. before migrations)
        $totalPurchasesThisMonth = 0;
        if (Schema::hasTable('qs_orders')) {
            $totalPurchasesThisMonth = QsOrder::where('user_id', $userId)
                ->where('created_at', '>=', now()->startOfMonth())
                ->where('created_at', '<=', now()->endOfMonth())
                ->where('sku', $sku)
                ->where('order_status', 'COMPLETE')
                ->sum('grand_payable_amount');
        }

        // Check if the SKU limit is set
        $monthlyPurchaseLimit = $product->sku_limits ?? null;

        // If no limit is set, allow the order to proceed without restriction
        if ($monthlyPurchaseLimit !== null && $monthlyPurchaseLimit != '') {
            $remainingLimit = $monthlyPurchaseLimit - $totalPurchasesThisMonth;

            if ($remainingLimit <= 0) {
                return back()->withErrors([
                    'message' => "You have exceeded your monthly purchase limit of ₹{$monthlyPurchaseLimit}."
                ])->withInput();
            }

            // Calculate the grand payable amount for the current order
            $grandPayableAmount = $request->quantity * $request->denomination;

            if ($grandPayableAmount > $remainingLimit) {
                return back()->withErrors([
                    'message' => "The remaining purchase limit for this product is ₹{$remainingLimit}, but your order total is ₹{$grandPayableAmount}. Please try placing an order within the available limit."
                ])->withInput();
            }
        } else {
            // No limit, proceed to calculate the grand payable amount
            $grandPayableAmount = $request->quantity * $request->denomination;
        }

        // SECURITY: Use database transaction with locking to prevent concurrent duplicate orders
        // Lock prevents multiple simultaneous requests from creating duplicate orders
        DB::beginTransaction();
        
        try {
            // SECURITY: Re-fetch product inside transaction with lock to ensure data consistency
            // This prevents race conditions if product data changes between validation and order creation
            $productLocked = QsProduct::where('sku', $sku)
                ->lockForUpdate() // Pessimistic locking - prevents concurrent modifications
                ->first();
            
            if (!$productLocked) {
                DB::rollBack();
                Log::error('❌ Product not found during order creation', [
                    'sku' => $sku,
                    'user_id' => $userId
                ]);
                return back()->withErrors(['message' => 'Product not found. Please refresh and try again.'])->withInput();
            }

            // SECURITY: Re-validate denomination against locked product (prevent frontend manipulation)
            // Price is already decoded by model accessor
            $priceData = (array) $productLocked->price;
            $priceType = $priceData['type'] ?? 'RANGE';
            
            $denomination = (float) $request->denomination;
            $quantity = (int) $request->quantity;
            
            // Re-validate denomination inside transaction
            if ($priceType === 'SLAB') {
                $denominations = $priceData['denominations'] ?? [];
                if (!in_array((string) $denomination, $denominations)) {
                    DB::rollBack();
                    return back()->withErrors([
                        'denomination' => 'Invalid denomination value. Allowed values are: ' . implode(', ', $denominations)
                    ])->withInput();
                }
            } elseif ($priceType === 'RANGE') {
                $minPrice = $priceData['min'] ?? $productLocked->minPrice ?? 0;
                $maxPrice = $priceData['max'] ?? $productLocked->maxPrice ?? 999999;
                if ($denomination < $minPrice || $denomination > $maxPrice) {
                    DB::rollBack();
                    return back()->withErrors([
                        'denomination' => "The denomination must be between ₹{$minPrice} and ₹{$maxPrice}."
                    ])->withInput();
                }
            }

            // SECURITY: Re-calculate amounts using locked product data (not request data)
            $grandPayableAmount = $quantity * $denomination;
            
            // SECURITY: Re-check monthly limit inside transaction with locking (prevents concurrent limit bypass)
            // CRITICAL: Lock rows first, then calculate sum to prevent race conditions
            if ($monthlyPurchaseLimit !== null && $monthlyPurchaseLimit != '') {
                // First, lock all user's orders for this SKU in current month to prevent concurrent limit bypass
                // We need to actually fetch and lock the rows, not just sum them
                $lockedOrders = QsOrder::where('user_id', $userId)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->where('created_at', '<=', now()->endOfMonth())
                    ->where('sku', $sku)
                    ->where('order_status', 'COMPLETE')
                    ->lockForUpdate() // CRITICAL: Lock rows to prevent concurrent requests from bypassing limit
                    ->get();
                
                // Calculate sum from locked rows
                $totalPurchasesThisMonthLocked = $lockedOrders->sum('grand_payable_amount');
                
                $remainingLimit = $monthlyPurchaseLimit - $totalPurchasesThisMonthLocked;
                
                if ($remainingLimit <= 0 || $grandPayableAmount > $remainingLimit) {
                    DB::rollBack();
                    Log::warning('❌ Monthly purchase limit exceeded (inside transaction)', [
                        'user_id' => $userId,
                        'sku' => $sku,
                        'total_purchases' => $totalPurchasesThisMonthLocked,
                        'limit' => $monthlyPurchaseLimit,
                        'remaining' => $remainingLimit,
                        'order_amount' => $grandPayableAmount,
                        'locked_orders_count' => $lockedOrders->count()
                    ]);
                    return back()->withErrors([
                        'message' => $remainingLimit <= 0 
                            ? "You have exceeded your monthly purchase limit of ₹{$monthlyPurchaseLimit}."
                            : "The remaining purchase limit is ₹{$remainingLimit}, but your order total is ₹{$grandPayableAmount}."
                    ])->withInput();
                }
            }

            // SECURITY: Get discount from locked product database, not request
            $discountPercentage = (float) ($productLocked->discount_percentage ?? 0);
            $discountAmount = $grandPayableAmount * ($discountPercentage / 100);
            $totalPayableAmountAfterDiscount = $grandPayableAmount - $discountAmount;

            // SECURITY: Check for duplicate orders within last 5 seconds (prevent double-click/rapid requests)
            $recentOrder = QsOrder::where('user_id', $userId)
                ->where('sku', $sku)
                ->where('denomination', $denomination)
                ->where('quantity', $quantity)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->where('order_status', 'Pending')
                ->lockForUpdate() // Pessimistic locking - blocks concurrent requests
                ->first();
            
            if ($recentOrder) {
                DB::rollBack();
                Log::warning('⚠️ Duplicate order attempt detected', [
                    'user_id' => $userId,
                    'existing_order_id' => $recentOrder->id,
                    'sku' => $sku,
                    'ip_address' => $request->ip()
                ]);
                return back()->withErrors(['message' => 'Order already being processed. Please check your orders.'])->withInput();
            }

            // SECURITY: Create order first (let DB assign auto-increment ID)
            // Then generate refno using actual ID to prevent race conditions
            $qsOrder = QsOrder::create([
                'user_id' => $userId,
                'sku' => $productLocked->sku,
                'product_name' => $productLocked->name,
                'denomination' => $denomination, // Use validated value
                'quantity' => $quantity, // Use validated value
                'grand_payable_amount' => $grandPayableAmount, // Calculated from DB values
                'discounted_amount_value' => $discountAmount, // Calculated from DB discount
                'amount_payable_after_discount' => $totalPayableAmountAfterDiscount, // Calculated from DB values
                'gift_send_option' => $request->gift_send_option,
                'delivery_mode' => 'both',
                'receiver_name' => $request->receiver_name,
                'receiver_email' => $request->receiver_email,
                'receiver_mobile' => $request->receiver_mobile,
                'receiver_msg' => $request->receiver_msg,
                'order_status' => 'Pending',
                // refno will be set after save using actual ID
            ]);
            
            // SECURITY: Generate refno AFTER save using actual auto-incremented ID
            // This prevents race conditions - each order gets unique ID from DB
            // Format: Amz + YYYYMMDD + 6-digit padded order ID
            $qsOrder->refno = 'Amz' . now()->format('Ymd') . str_pad($qsOrder->id, 6, '0', STR_PAD_LEFT);
            $qsOrder->save(); // Single update save for refno

            // SECURITY: Create payment record with values from order DB (not request)
            $payment = UnlimitPayment::create([
                'order_id' => $qsOrder->id,
                'user_id' => $userId,
                'mer_amount' => $qsOrder->grand_payable_amount, // From DB
                'price' => $qsOrder->denomination, // From DB
                'qty' => $qsOrder->quantity, // From DB
                'order_status' => 'UnPaid',
            ]);

            // Populate the OrderSummary
            $orderSummary = OrderSummary::create([
                'order_id' => $qsOrder->id,
                'payment_id' => $payment->id,
                'payment_gateway' => 'unlimit',
                'product_name' => $qsOrder->product_name,
                'payment_status' => $payment->order_status,
                'order_status' => $qsOrder->order_status,
            ]);

            DB::commit();
            
            Log::info('✅ Order created successfully', [
                'order_id' => $qsOrder->id,
                'user_id' => $userId,
                'refno' => $qsOrder->refno,
                'amount' => $totalPayableAmountAfterDiscount,
                'ip_address' => $request->ip()
            ]);
            
            // SECURITY: Store session data AFTER successful transaction (from DB, not request)
            // This ensures session always matches database state and prevents frontend manipulation
            session([
                'session_qs_order_id' => $qsOrder->id, // From DB
                'session_refno' => $qsOrder->refno, // From DB
                'selected_product' => [
                    'denomination' => $qsOrder->denomination, // From DB
                    'quantity' => $qsOrder->quantity, // From DB
                    'sku' => $qsOrder->sku, // From DB
                    'price' => $productLocked->price, // From DB (product)
                ]
            ]);
            
            // SECURITY: Regenerate session to prevent fixation attacks
            $request->session()->regenerate();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to create order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'sku' => $product->sku ?? null,
                'ip_address' => $request->ip()
            ]);
            return back()->withErrors(['message' => 'Failed to create order. Please try again.'])->withInput();
        }

        // SECURITY: If order exists in session, load it from database (handles page reload)
        $sessionOrderId = session('session_qs_order_id');
        if ($sessionOrderId) {
            $existingOrder = QsOrder::where('id', $sessionOrderId)
                ->where('user_id', Auth::id()) // SECURITY: Verify ownership
                ->first();
            
            if ($existingOrder) {
                // Use existing order from database (has all amounts calculated)
                $qsOrder = $existingOrder;
                Log::info('✅ Loaded existing order from session', [
                    'order_id' => $qsOrder->id,
                    'user_id' => Auth::id(),
                    'amount' => $qsOrder->amount_payable_after_discount
                ]);
            } else {
                Log::warning('⚠️ Order in session not found or unauthorized', [
                    'session_order_id' => $sessionOrderId,
                    'user_id' => Auth::id()
                ]);
                // Clear invalid session order_id
                session()->forget('session_qs_order_id');
            }
        }

        $qsProd = QsProduct::where('url', $slug)->firstOrFail();
        $qsProd['prodData'] = $request->all();
        // Currency and images are already decoded by model accessors
        $qsProd['currency'] = $qsProd->currency;
        $qsProd['images'] = $qsProd->images;

        // Remove payment gateway calls from GET request - these should only be called on form submission
        // Payment gateway initialization will be handled when user clicks submit button

        return view('userpanel.checkout', compact('qsProd', 'checkoutData', 'qsOrder'));
    }



    public function updateSessionData(Request $request)
    {
        $requestData = $request->all();
        session()->put('checkout_data', $requestData);
        
        $billing = new Billing();
        $billing->billing_name = $request->billing_name;
        $billing->billing_email = $request->billing_email;
        $billing->billing_tel = $request->billing_tel;
        $billing->billing_address = $request->billing_address;
        $billing->billing_address_two = $request->billing_address_two;
        $billing->billing_city = $request->billing_city;
        $billing->billing_state = $request->billing_state;
        $billing->billing_zip = $request->billing_zip;
        $billing->save();
        return response()->json(['message' => 'Session data updated successfully']);
    }

    public function showCheckoutForm(Request $request, $slug)
    {
        Log::info('showCheckoutForm called for slug: ' . $slug);
        
        // SECURITY: If order exists in session, load it from database (handles page reload)
        $sessionOrderId = session('session_qs_order_id');
        $qsOrder = null;
        
        if ($sessionOrderId) {
            $existingOrder = QsOrder::where('id', $sessionOrderId)
                ->where('user_id', Auth::id()) // SECURITY: Verify ownership
                ->first();
            
            if ($existingOrder) {
                // Use existing order from database (has all amounts calculated)
                $qsOrder = $existingOrder;
                Log::info('✅ Loaded existing order from session for checkout', [
                    'order_id' => $qsOrder->id,
                    'user_id' => Auth::id(),
                    'amount' => $qsOrder->amount_payable_after_discount,
                    'refno' => $qsOrder->refno
                ]);
            } else {
                Log::warning('⚠️ Order in session not found or unauthorized', [
                    'session_order_id' => $sessionOrderId,
                    'user_id' => Auth::id()
                ]);
                // Clear invalid session order_id
                session()->forget('session_qs_order_id');
            }
        }
        
        // If no existing order, create a new empty order instance for the form
        if (!$qsOrder) {
            $qsOrder = new QsOrder();
            $qsOrder->user_id = Auth::id();
        }
        
        // Fetch product by slug
        $product = QsProduct::where('url', $slug)->firstOrFail();
        $product['prodData'] = $request->all();
        // Currency and images are already decoded by model accessors
        $product['currency'] = $product->currency;
        $product['images'] = $product->images;
        
        // Get checkout data from session
        $checkoutData = session('checkout_data', []);
        Log::info('Checkout data retrieved from session:', $checkoutData);
        
        // Try to load cached billing data
        $cachedBilling = CheckoutHelper::getBillingData($qsOrder->id);
        
        // Remove payment gateway calls from GET request - these should only be called on form submission
        // Payment gateway initialization will be handled when user clicks submit button
        
        return view('userpanel.checkout', compact('product', 'checkoutData', 'qsOrder', 'cachedBilling'));
    }

    /**
     * API endpoint to update billing data in cache (AJAX)
     * This replaces session-based storage with cache-based storage
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBillingCache(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required',
                ], 400);
            }
            
            // Verify order exists and belongs to authenticated user
            $order = QsOrder::where('id', $orderId)
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or unauthorized',
                ], 404);
            }
            
            // Store billing data in cache
            $billingData = $request->only([
                'billing_name',
                'billing_email',
                'billing_tel',
                'billing_zip',
                'billing_address',
                'billing_address_two',
                'billing_city',
                'billing_state',
                'billing_country',
                'billing_gst_number',
            ]);
            
            $success = CheckoutHelper::storeBillingData($orderId, $billingData);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Billing data cached successfully',
                    'ttl' => CheckoutHelper::CACHE_TTL / 60 . ' minutes',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cache billing data',
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update billing cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * API endpoint to retrieve billing data from cache (AJAX)
     * 
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBillingCache(Request $request, $orderId)
    {
        try {
            // Verify order exists and belongs to authenticated user
            $order = QsOrder::where('id', $orderId)
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or unauthorized',
                ], 404);
            }
            
            // Retrieve billing data from cache
            $billingData = CheckoutHelper::getBillingData($orderId);
            
            if ($billingData) {
                return response()->json([
                    'success' => true,
                    'data' => $billingData,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No cached billing data found',
                ], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve billing cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store billing information to database (called from payment controller after successful payment)
     * This moves data from cache → database permanently
     * 
     * @param int $orderId
     * @param array|null $billingData Optional billing data, if null will try to load from cache
     * @return bool
     */
    public function storeBillingToDatabase($orderId, $billingData = null)
    {
        try {
            // If billing data not provided, try to load from cache
            if (!$billingData) {
                $billingData = CheckoutHelper::getBillingData($orderId);
            }
            
            if (!$billingData) {
                Log::warning('No billing data to store', ['order_id' => $orderId]);
                return false;
            }
            
            // Store to database
            $billing = Billing::updateOrCreate(
                ['order_id' => $orderId],
                $billingData
            );
            
            // Clear cache after successful DB storage
            CheckoutHelper::clearOrderCache($orderId);
            
            Log::info('Billing data moved from cache to database', [
                'order_id' => $orderId,
                'billing_id' => $billing->id,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to store billing to database', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
