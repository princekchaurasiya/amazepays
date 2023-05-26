<html>

<head>
    <title> CCAvenue Payment Gateway Integration kit</title>
</head>

<body>
    <?php include 'crypto.php'; ?>
    <?php require_once 'config.php'; ?>
    <?php
    error_reporting(0);
    
    $merchant_data = '';
    $working_key = config('paymentconfig.working_key');
    $access_code = config('paymentconfig.access_code');
    
    foreach ($_POST as $key => $value) {
        $merchant_data .= $key . '=' . $value . '&';
    }
    $merchant_data .= 'order_id=' . $orderId;
    
    $encrypted_data = encrypt($merchant_data, $working_key);
    
    ?>
    <form method="post" name="redirect"
        action="https://test.ccavenue.com">
        <?php
        echo "<input type=hidden name=encRequest value=$encrypted_data>";
        echo "<input type=hidden name=access_code value=$access_code>";
        ?>
    </form>
    
    </center>
    <script language='javascript'>
        document.redirect.submit();
    </script>
</body>

</html>
