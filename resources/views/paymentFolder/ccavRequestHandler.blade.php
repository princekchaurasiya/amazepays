<!DOCTYPE html>
<html>
<head>
    <title>Custom Form Kit</title>
</head>
<body>
    <center>
        <form method="post" name="redirect" action="{{ $ccavenueApiEndpoint }}">
            <input type="hidden" name="encRequest" value="{{ $encryptedData }}">
            <input type="hidden" name="access_code" value="{{ $accessCode }}">
        </form>
    </center>

    <script language="javascript">
        document.redirect.submit();
    </script>
</body>
</html>
