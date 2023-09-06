<?php
use Illuminate\Support\Str;

// Generate a random OTP (6-digit number)
$otp = mt_rand(100000, 999999);

$sms_message  = "Dear User,  Your  one  time  password  ". $otp ."  and  its  valid  for  5  minutes  only.  Do  not  share  to  anyone.  Thanks  -  FRENETIC  INDIA";

// transaction message setup




return [
    'gst_number' => '27AAFCF2328E1ZX',
    'company_name' => 'Frenetic India Services Private Limited',
    'company_address' => '98-103, 4 Floor, Aditya Industrial Estate Co-op Premises Ltd Mindspace Behind Evershine 
      Mall Off Link Road Malad West Mumbai 400064.',
    'company_cin' => 'U72900MH2022PTC391272',
    'company_pan' => 'AAFCF2328E',
    'company_bank_account_number' => '8747187374',
    'company_bank_ifsc_code' => 'KKBK0001413',
    'company_bank_branch' => 'Goregaon West',
    'comapny_bank_name' => 'KOTAK BANK',
    'comapny_email' => 'support@amazepay.in',
    'default_subject' => 'Amazepays - Order Confirmation',
    'gift_subject' => 'Amazepays - You Received A Gift Card',
    'sms_api_url' => 'http://route.digimiles.in/bulksms/bulksms',
    'sms_user_name' => 'DG35-frenetic',
    'sms_user_password' => 'digimile',
    'sms_source' => 'FRNTIC',
    'sms_entity_id' => '1101633530000071318',
    'sms_temp_id' => '1107169019646710710',
    'generated_otp' => $otp,
    'sms_message' => $sms_message,
];
?>
