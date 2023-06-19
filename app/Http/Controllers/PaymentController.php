<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ccavenuekit\ccavRequestHandler;
use App\Ccavenuekit\ccavResponseHandler;
use App\Ccavenuekit\crypto;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Http;
use Carbon;
use GuzzleHttp\Client;
use Session;
use App\Models\QsOrder;
use Illuminate\Support\Facades\Auth;
use App\Models\CcAvenuePayment;

class PaymentController extends Controller
{

 /* This is crypto.php code provided by cc   
/*
* @param1 : Plain String
* @param2 : Working key provided by CCAvenue
* @return : Decrypted String
*/
    function encrypt($plainText,$key)
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        $encryptedText = bin2hex($openMode);
        return $encryptedText;
    }

/*
* @param1 : Encrypted String
* @param2 : Working key provided by CCAvenue
* @return : Plain String
*/
function decrypt($encryptedText,$key)
{
	$key = $this->hextobin(md5($key));
	$initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
	$encryptedText = $this->hextobin($encryptedText);
	$decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
	return $decryptedText;
}

function hextobin($hexString) 
 { 
	$length = strlen($hexString); 
	$binString="";   
	$count=0; 
	while($count<$length) 
	{       
	    $subString =substr($hexString,$count,2);           
	    $packedString = pack("H*",$subString); 
	    if ($count==0)
	    {
			$binString=$packedString;
	    } 
	    
	    else 
	    {
			$binString.=$packedString;
	    } 
	    
	    $count+=2; 
	} 
        return $binString; 
  } 
    public function processPayment(Request $request)
    {
        // dd($request)
        // Generate a unique order ID or transaction ID
        $orderId = uniqid();

        // Get the form input values
        $billingName = $request->input('billing_name');
        $billingAddress = $request->input('billing_address');
        $billingState = $request->input('billing_state');
        $billingZipCode = $request->input('billing_zip');
        $billingCountry = $request->input('billing_country');
        $billingTelephone = $request->input('billing_tel');
        $billingEmail = $request->input('billing_email');

        // Prepare the data array for checksum calculation
        $data = [
            'merchant_id' => config('paymentconfig.merchant_id'),
            'order_id' => $orderId,
            'currency' => 'INR',
            'amount' => '1', // Change this to the actual payment amount
            'redirect_url' => route('payment-success'),
            'cancel_url' => route('payment-failed'),
            'language' => 'EN',
            'billing_name' => $billingName,
            'billing_address' => $billingAddress,
            'billing_state' => $billingState,
            'billing_zip' => $billingZipCode,
            'billing_country' => $billingCountry,
            'billing_tel' => $billingTelephone,
            'billing_email' => $billingEmail,
        ];
    
        return view('paymentFolder.ccavRequestHandler',compact('data'));
    }

    public function paymentSuccess()
    {
        return view('paymentFolder.payment-success');
    }

    public function paymentFailed()
    {
        return view('paymentFolder.payment-failed');
    }


    public function responseCcavenue(Request $request)
    {

        $workingKey=config('auth.working_key');	
        
        $encResponse=$request->encResp;	//This is the response sent by the CCAvenue Server
        $rcvdString=$this->decrypt($encResponse,$workingKey);	//Crypto Decryption used as per the specified working key.
        $order_status="";
        $decryptValues=explode('&', $rcvdString);
        $dataSize=sizeof($decryptValues);
        // dd($decryptValues);
        for($i = 0; $i < $dataSize; $i++) 
        {
            $information=explode('=',$decryptValues[$i]);
            if($i==3)	$order_status=$information[1];
        }
        if($order_status==="Success")
        {
            $response_status = $this->orderCard($decryptValues, $dataSize);
            Auth::loginUsingId($response_status['user_id'],true);
            $address = json_decode($response_status['billing_details']);
            $billing = json_decode($response_status['delivery_details']);
            $modfy_user_data = [
                'address' => [
                    "firstname"=>$address->firstname,
                    "lastname"=>'test',
                    "email"=>$address->email,
                    "telephone"=>$address->telephone,
                    "line1"=>$address->line1,
                    "line2"=>$address->line1,
                    "city"=>$address->city,
                    "region"=>$address->region,
                    "country"=>$address->country,
                    "postcode"=>$address->postcode,
                    "languages"=>"Hindi",
                    "billToThis"=>true
                ],
                "billing" =>[
                    "firstname"=>$billing->firstname,
                    "lastname"=>'test',
                    "email"=>$billing->email,
                    "telephone"=>$billing->telephone,
                    "line1"=>$billing->line1,
                    "line2"=>$billing->line1,
                    "city"=>$billing->city,
                    "region"=>$billing->region,
                    "country"=>$billing->country,
                    "postcode"=>$billing->postcode,
                    "languages"=>"Hindi",
                    "billToThis"=>true
                ],
                "payments"=>[
                    [
                        "code"=>"svc",
                        "amount"=>$response_status['amount'] //take from selected front end
                    ]
                ],
                "refno"=>"Amaz".mt_rand(1111,9999),
                "products" =>[
                    [
                        "sku"=>'CNPIN',
                        "price"=>$response_status['price'],
                        "qty"=>$response_status['qty'],
                        "currency"=>$response_status['currency_code']
                    ]
                ],
                "syncOnly"=>($response_status['qty'] > 10 ? false : true),
                "delivery_mode"=>"API"
            ];
// dd($modfy_user_data);
            $requestBody = json_encode($modfy_user_data);
            $requestHttpMethod = 'post';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = CommonController::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();
            $response = Http::acceptJson()
                // ->withToken($bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearerToken,
                    'Accept' => '*/*',
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->send('POST', 'https://sandbox.woohoo.in/rest/v3/orders', [
                    'body' => $requestBody,
            ]);

            $response->json();
            // dd($response->json());
            $userOrder = new QsOrder();
            $userOrder->user_id = $response_status['user_id'];
            $userOrder->product = json_encode($response['payments']);
            $userOrder->reference_id = $response['refno'];
            $userOrder->order_id = $response['orderId'];
            $userOrder->order_status = $response['status'];
            $userOrder->cards = json_encode($response['cards']);
            $userOrder->order_cancel =json_encode($response['cancel']);
            $userOrder->order_payment = json_encode($response['payments']);
            $userOrder->currency = json_encode($response['currency']);
            $userOrder->additionalTxnFields = json_encode($response['additionalTxnFields']);
            if($userOrder->save()){
                return redirect()->route('home');
            } else {
                echo "<br>Security Error. Illegal access detected";
            }
        }
        else if($order_status==="Aborted")
        {
            return view('userpanel.order_details');
        
        }
        else if($order_status==="Failure")
        {
            return view('userpanel.order_details');
        }
        else
        {
            return view('userpanel.order_details');
        
        }
        // for($i = 0; $i < $dataSize; $i++) 
        // {
        //     $information=explode('=',$decryptValues[$i]);
        //         echo '<tr><td>'.$information[0].'</td><td>'.$information[1].'</td></tr>';
        // }
        // $dataSize=sizeof($decryptValues);

       
    }

    public function orderCard($respData ,$datasize)
    {
        for($i = 0; $i < $datasize; $i++) 
        {
            $information=explode('=',$respData[$i]);
            $data[] = [
                    $information[0] => $information[1],
            ];
            // echo '<tr><td>'.$information[0].'</td><td>'.$information[1].'</td></tr>';
        };
        $new_data = [
            'tracking_id'        => $data[1]['tracking_id'],
            'bank_ref_no'        => $data[2]['bank_ref_no'],
            'order_status'       => $data[3]['order_status'],
            'failure_message'    => $data[4]['failure_message'],
            'payment_mode'       => $data[5]['payment_mode'],
            'card_name'          => $data[6]['card_name'],
            'status_code'        => $data[7]['status_code'],
            'status_message'        => $data[8]['status_message'],
            'currency'        => $data[9]['currency'],
            'amount'        => $data[10]['amount'],
            'billing_details' => [
                "firstname"=>$data[11]['billing_name'],
                "email"=>$data[18]['billing_email'],
                "telephone"=>"+91".$data[17]['billing_tel'],
                "line1"=>$data[12]['billing_address'],
                "city"=>$data[13]['billing_city'],
                "region"=>$data[14]['billing_state'],
                "country"=>"IN",
                "postcode"=>$data[15]['billing_zip'],
            ],

            'delivery_details' => [
                "firstname"=>$data[11]['billing_name'],
                "email"=>$data[18]['billing_email'],
                "telephone"=>"+91".$data[17]['billing_tel'],
                "line1"=>$data[12]['billing_address'],
                "city"=>$data[13]['billing_city'],
                "region"=>$data[14]['billing_state'],
                "country"=>"IN",
                "postcode"=>$data[15]['billing_zip'],
            ],

            'merchant_params' => [
                "merchant_param1"=>$data[26]['merchant_param1'],
                "merchant_param2"=>$data[27]['merchant_param2'],
                "merchant_param3"=>$data[28]['merchant_param3'],
                "merchant_param4"=>$data[29]['merchant_param4'],
                "merchant_param5"=>$data[30]['merchant_param5']
            ],
            'vault'        => $data[31]['vault'],
            'offer_type'        => $data[32]['offer_type'],
            'offer_code'        => $data[33]['offer_code'],
            'discount_value'        => $data[34]['discount_value'],
            'mer_amount'        => $data[35]['mer_amount'],
            'eci_value'        => $data[36]['eci_value'],
            'retry'        => $data[37]['retry'],
            'response_code'        => $data[38]['response_code'],
            'billing_notes'        => $data[39]['billing_notes'],
            'trans_date'        => $data[40]['trans_date'],
            'bin_country'        => $data[41]['bin_country'],
        ];
        // dd($new_data);

        $payment_update = CcAvenuePayment::where("order_id", "=", $data[0]['order_id'])->update($new_data);
        if($payment_update == true){
            $user_data = CcAvenuePayment::where('order_id',$data[0]['order_id'])->first();
        }
        return $user_data;
    }

  
}


