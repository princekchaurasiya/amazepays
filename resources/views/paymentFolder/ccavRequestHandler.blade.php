<html>
<head>
<title> Custom Form Kit </title>
</head>
<body>
    <center>

        @include('paymentFolder.crypto');
        <?php 
            error_reporting(0);
            
            $merchant_data='';
            $working_key=config('auth.working_key');//Shared by CCAVENUES
            $access_code=config('auth.access_code');//Shared by CCAVENUES
            foreach ($data as $key => $value){
                $merchant_data.=$key.'='.urlencode($value).'&';
            }
            $encrypted_data=encryptCCAvenue($merchant_data,$working_key); // Method for encrypting the data.

        ?>
        <form method="post" name="redirect" action="https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction"> 
        <?php
        echo "<input type=hidden name=encRequest value=$encrypted_data>";
        echo "<input type=hidden name=access_code value=$access_code>";
        ?>
        </form>
    </center>
<script language='javascript'>document.redirect.submit();</script>
</body>
</html>
