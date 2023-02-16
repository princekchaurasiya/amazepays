<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\User;
use App\QsProduct;

class UserPanelController extends Controller
{
   public function homePage(){
        try {
            $getCategory = DB::table('qs_categories')->first();
            $allProducts = DB::table('qs_products')->select('qs_products.*', 'qs_categories.name as category_name')->leftjoin('qs_categories','qs_products.qs_category_id', '=', 'qs_categories.id')->get();

            $allProducts->map(function ($item, $key) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images);
            });
            return view("userpanel/index",compact('allProducts','getCategory'));
        }
        catch(Exception $e){
           return $e->getMessage();
        }
        //return View::make("userpanel/index", compact('allProducts'));
        
   }

   public function userRegistration(Request $request){
        try {
            User::create([
                'name'=>$request->name,
                'email'=>$request->email,
                'password'=>bcrypt($request->password),
                'role_id'=>2,

            ]);

            if(\Auth::attempt($request->only('email','password'))){
                $data = [
                    'status'=>200,
                    'msg'=>'User Register Successfully'
                ];
            } else {
                $data = [
                    'status'=>400,
                    'msg'=>'Something went wrong'
                ];
            }

            return response()->json($data);
        } catch(Exception $e){
            return $e->getMessage();
        }
        

        // return redirect()
        // $userRegister = new User();
        // $userRegister->name = $request->name;
        // $userRegister->email = $request->email;
        // $userRegister->password = bcrypt($request->password);
        // $userRegister->role_id = 2;
        // $userRegister->save();


   }

   public function userLogin(Request $request){
        try {
            if(\Auth::attempt($request->only('email', 'password'))){
                $data = [
                    'status'=>200
                ];
            } else {
                $data = [
                    'status'=>400
                ];
            }

            return response()->json($data);
        }catch(Exception $e){
            return $e->getMessage();
        }
        
        
    }

    public function userLogOut(){
        \Session::flush();
        \Auth::logout();
        return redirect('/');

    }
    public function viewAllProduct(){
        $viewProds = QsProduct::all();
        return  $viewProds;
    }
    public function checkOut(Request $request,$sku){
        try {
            $qsProd = QsProduct::where('sku',$sku)->first();
            $qsProd['prodData'] = $request->all();
            //dd($qsProd);
            if(\Auth::user()){
                return view("userpanel/checkout",compact('qsProd'));
            } else {
                return redirect('/');
            }
        }catch(Exception $e){
            $e->getMessage();
        }
        
    }

    public function applyCoupan(Request $request){
        $grandTotal = $request->grand_total;
        $coupanVal = 10;
        $aftApplyCoupan = $request->grand_total - ($request->grand_total*($coupanVal/100));
        $data = [
            'coupan'=>$coupanVal,
            'aftApplyCoupan'=>$aftApplyCoupan,
        ];
        return response()->json($data);
    }

    public function removeApplyCoupan(Request $request){
        $grandTotal = $request->grand_total;
        $data = [
            'coupan'=>'',
            'grandTotal'=>$grandTotal,
        ];
        return response()->json($data);
    }
}
