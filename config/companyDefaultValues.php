<?php

use Carbon\Carbon;

// Generate a random OTP (6-digit number)
$otp = mt_rand(100000, 999999);
$otpGeneratedTime = Carbon::now('Asia/Kolkata');
$otpExpiration = $otpGeneratedTime->copy()->addMinutes(5);
$company_name = 'Frenetic India';

$otp_duration_minutes = $otpExpiration->diffInMinutes($otpGeneratedTime);

$sms_message = 'Dear User,  Your  one  time  password is  ' . $otp . '  and  its  valid  for ' . $otp_duration_minutes . '  minutes  only.  Do  not  share  to  anyone.  Thanks - ' . $company_name;

// transaction message setup

return [
    'generated_otp' => $otp,
    'sms_message' => $sms_message,
    'otpExpiration' => $otpExpiration,
    'company_name' => $company_name,

    'gst_number' => env('GST_NUMBER', '27AAFCF2328E1ZX'),
    'company_address' => env('COMPANY_ADDRESS', '98-103, 4 Floor, Aditya Industrial Estate Co-op Premises Ltd Mindspace Behind Evershine Mall Off Link Road Malad West Mumbai 400064.'),
    'company_cin' => env('COMPANY_CIN', 'U72900MH2022PTC391272'),
    'company_pan' => env('COMPANY_PAN', 'AAFCF2328E'),
    'company_bank_account_number' => env('COMPANY_BANK_ACCOUNT_NUMBER', '8747187374'),
    'company_bank_ifsc_code' => env('COMPANY_BANK_IFSC_CODE', 'KKBK0001413'),
    'company_bank_branch' => env('COMPANY_BANK_BRANCH', 'Goregaon West'),
    'company_bank_name' => env('COMPANY_BANK_NAME', 'KOTAK BANK'),
    'company_email' => env('COMPANY_EMAIL', 'support@amazepay.in'),
    'default_subject' => env('DEFAULT_SUBJECT', 'Amazepays - Order Confirmation'),
    'gift_subject' => env('GIFT_SUBJECT', 'Amazepays - You Received A Gift Card'),
    'sms_api_url' => env('SMS_API_URL', 'http://route.digimiles.in/bulksms/bulksms'),
    'sms_user_name' => env('SMS_USER_NAME', 'DG35-frenetic'),
    'sms_user_password' => env('SMS_USER_PASSWORD', 'digimile'),
    'sms_source' => env('SMS_SOURCE', 'FRNTIC'),
    'sms_entity_id' => env('SMS_ENTITY_ID', '1101633530000071318'),
    'sms_temp_id' => env('SMS_TEMP_ID', '1107169019646710710'),
    'sendMailFrom' => env('SEND_MAIL_FROM', 'it@amazepays'),
    'company_website' => env('COMPANY_WEBSITE', 'https://amazepays.in/'),
    'company_contact_no' => env('COMPANY_CONTACT_NO', '8208893951'),
];

?>
