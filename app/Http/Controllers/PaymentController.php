<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ccavenuekit\ccavRequestHandler;
use App\Ccavenuekit\ccavResponseHandler;
use App\Ccavenuekit\crypto;

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
        return view('paymentFolder.payment-success');		
	}
	else if($order_status==="Aborted")
	{
		echo "<br>Thank you for shopping with us.We will keep you posted regarding the status of your order through e-mail";
	
	}
	else if($order_status==="Failure")
	{
		return view('paymentFolder.payment-failed');
	}
	else
	{
		echo "<br>Security Error. Illegal access detected";
	
	}
    for($i = 0; $i < $dataSize; $i++) 
	{
		$information=explode('=',$decryptValues[$i]);
	    	echo '<tr><td>'.$information[0].'</td><td>'.$information[1].'</td></tr>';
	}

    }
  
}


