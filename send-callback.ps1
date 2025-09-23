# Define variables
$callbackSecret = "CR42dWVu8il5"
$callbackUrl = "http://127.0.0.1:8000/api/unlimit/callback"

# JSON body (without secret)
$bodyJson = '{"callback_time": "2019-04-10T09:10:49Z","transaction_id":"123456","status":"success"}'

# String to sign (body + secret)
$stringToSign = $bodyJson + $callbackSecret

# Compute SHA-512
$bytes = [System.Text.Encoding]::UTF8.GetBytes($stringToSign)
$sha512 = [System.Security.Cryptography.SHA512]::Create()
$hash = $sha512.ComputeHash($bytes)
$signature = -join ($hash | ForEach-Object { $_.ToString("x2") })

Write-Host "Generated Signature: $signature"

# Prepare headers
$headers = @{
    "Content-Type" = "application/json"
    "Signature"    = $signature
}

# Send POST request
$response = Invoke-RestMethod -Uri $callbackUrl -Method Post -Headers $headers -Body $bodyJson

Write-Host "Response from server:"
$response | ConvertTo-Json -Depth 5