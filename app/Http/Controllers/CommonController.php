<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon;
use DB;
use App\Models\QsCategory;
use App\Models\QsOrder;
use App\Models\QsProduct;
use Illuminate\Support\Facades\Log;
use View;
use App\Helpers\CommonHelper;

class CommonController extends Controller
{
    public function checkData(Request $request)
    {
        $data = ['status' => true, 'msg' => $request->all()];
        return response()->json($data);
    }




}
