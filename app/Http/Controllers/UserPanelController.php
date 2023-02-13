<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\User;

class UserPanelController extends Controller
{
   public function homePage(){
    $getCategory = DB::table('qs_categories')->first();
    $allProducts = DB::table('qs_products')->select('qs_products.*', 'qs_categories.name as category_name')->leftjoin('qs_categories','qs_products.qs_category_id', '=', 'qs_categories.id')->get();

    $allProducts->map(function ($item, $key) {
        $item->currency = json_decode($item->currency);
        $item->price = json_decode($item->price);
        $item->images = json_decode($item->images);
    });
    //return View::make("userpanel/index", compact('allProducts'));
    return view("userpanel/index",compact('allProducts','getCategory'));
   }

   public function userRegistration(Request $request){

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

        // return redirect()
        // $userRegister = new User();
        // $userRegister->name = $request->name;
        // $userRegister->email = $request->email;
        // $userRegister->password = bcrypt($request->password);
        // $userRegister->role_id = 2;
        // $userRegister->save();


   }

   public function userLogin(Request $request){
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
    }

    public function userLogOut(){
        \Session::flush();
        \Auth::logout();
        return redirect('/');

    }

    public function checkOut(Request $request){
        if(\Auth::user()){
            return view("userpanel/checkout");
        } else {
            return redirect('/');
        }
    }
}
