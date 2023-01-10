<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommonController extends Controller
{
    public function checkData(Request $request){

        $data=['status'=>true,'msg'=>$request->all()];
        return response()->json($data);
    }
}
