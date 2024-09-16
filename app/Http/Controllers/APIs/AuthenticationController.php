<?php
namespace App\Http\Controllers\APIs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Otp;
use App\Models\QsProduct;
use App\Http\Controllers\SmsController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Config;
use App\Models\User;
use Carbon\Carbon;
use DB;
class AuthenticationController extends Controller
{

    function allProduct(Request $request)
    {
        try {
            $getCategory = DB::table('qs_categories')->first();
            $allProducts = DB::table('qs_products')->select('qs_products.*', 'qs_categories.name as category_name')->leftjoin('qs_categories', 'qs_products.qs_category_id', '=', 'qs_categories.id')->get();
            $allProducts->map(function ($item, $key) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images); });
            return response()->json(['status' => 'success', 'status_code' => 200, 'data' => $allProducts]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'status_code' => 500, 'message' => $e->getMessage()]);
        }
    }
    function singleProductDetails(Request $request)
    {
        $getprdtDetails = QsProduct::where('sku', '=', $request->sku)->first()->toArray();
        $getprdtDetails['price'] = json_decode($getprdtDetails['price']);
        $getprdtDetails['images'] = json_decode($getprdtDetails['images']);
        $getprdtDetails['tnc'] = json_decode($getprdtDetails['tnc']);
        return response()->json(['status' => 'success', 'status_code' => 200, 'data' => $getprdtDetails]);
    }

}
