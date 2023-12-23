<?php

return [
    'merchant_id' => env('CCAVENUE_MERCHANT_ID'),

    // amazpay.toutle.in credentials
    // 'access_code' => 'AVZL66KE76BL79LZLB',
    // 'working_key' => '04D20915288E822ADAC8877F4228D25B',

    // amazepay.test credentials
       'access_code' => env('CCAVENUE_ACCESS_CODE'),
        'working_key' => env('CCAVENUE_WORKING_KEY'),
        'ccavenue_api_endpoint' => env('CCAVENUE_LINK'),

    // secure.ccavenue.com
    // 'access_code' => 'AVPV04KE94CM34VPMC',
    // 'working_key' => 'C9C022C3BE6323EB26097B3899B8D8CF',
];
?>
