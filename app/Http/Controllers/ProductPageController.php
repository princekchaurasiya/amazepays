<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\QsOrder;
use App\Models\QsProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
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
        $checkoutData = session('checkout_data', []);
        Session::put('selected_product_slug', $slug);
        $validator = Validator::make($request->all(), ['quantity' => 'required|integer|min:1|max:10', 'gift_send_option' => 'required|string', 'delivery_mode' => 'required|string', 'denomination' => 'required',], ['quantity.min' => 'The quantity must be at least :min.', 'quantity.max' => 'The quantity cannot exceed :max.',]);
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        Log::info(session()->all());
        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::id();
        $qsOrder->denomination = $request->denomination;
        $qsOrder->quantity = $request->quantity;
        $qsOrder->grand_payable_amount = $request->quantity * $request->denomination;
        $qsOrder->gift_send_option = $request->gift_send_option;
        $qsOrder->delivery_mode = $request->delivery_mode;
        $qsOrder->receiver_name = $request->receiver_name;
        $qsOrder->receiver_email = $request->receiver_email;
        $qsOrder->receiver_mobile = $request->receiver_mobile;
        $qsOrder->receiver_msg = $request->receiver_msg;
        $qsOrder->save();
        $qsOrder->refno = $this->generateUniqueReferenceNumber($qsOrder->id);
        $qsOrder->save();
        session()->put('session_qs_order_id', $qsOrder->id);
        session()->put('session_refno', $qsOrder->refno);
        $qsProd = QsProduct::where('slug', $slug)->first();
        if (!$qsProd) {
            abort(404);
        }
        $qsProd['prodData'] = $request->all();
        $qsProd['currency'] = json_decode($qsProd['currency']);
        $qsProd['images'] = json_decode($qsProd->images);
        return view('userpanel.checkout', compact('qsProd', 'checkoutData'));
    }
    private function generateUniqueReferenceNumber($orderId)
    {
        do {
            $uniqueSuffix = substr(md5(uniqid(rand(), true)), 0, 8);
            $referenceNumber = 'Amz' . $orderId . $uniqueSuffix;
        } while (QsOrder::where('refno', $referenceNumber)->exists());
        return $referenceNumber;
    }
    public function updateSessionData(Request $request)
    {
        Log::info('updateSessionData called');
        Log::info('Request data: ', $request->all());
        $requestData = $request->all();
        session()->put('checkout_data', $requestData);
        Log::info('Session data stored');
        return response()->json(['message' => 'Session data updated successfully']);
    }
    public function showCheckoutForm()
    {
        $checkoutData = session('checkout', []);
        return view('checkout', compact('checkoutData'));
    }
}
