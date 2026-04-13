<?php

namespace App\Console\Commands;

use Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateBearerToken extends Command
{
    protected $signature = 'generate:bearerToken';

    protected $description = 'Generate a bearer token using the authorization code';

    public function handle()
    {
        try {
            $woohooUrl = config('woohoo.host');
            $absApiUrl = 'https://'.$woohooUrl.'/oauth2/verify';

            // Log URL construction for verification
            Log::info('🔍 Verifying URLs:', [
                'woohoo_url_setting' => $woohooUrl,
                'constructed_verify_url' => $absApiUrl,
                'expected_url' => 'https://sandbox.woohoo.in/oauth2/verify',
                'urls_match' => $absApiUrl === 'https://sandbox.woohoo.in/oauth2/verify',
            ]);

            $clientId = config('woohoo.client_id');
            $username = config('woohoo.username');
            $password = config('woohoo.password');

            // Validate that all required settings are present
            if (empty($clientId) || empty($username) || empty($password) || empty($woohooUrl)) {
                $this->error('❌ Missing required API credentials. Set WOOHOO_* variables in .env.');
                Log::error('Missing API credentials:', [
                    'woohoo_url' => ! empty($woohooUrl) ? 'set' : 'missing',
                    'clientId' => ! empty($clientId) ? 'set' : 'missing',
                    'username' => ! empty($username) ? 'set' : 'missing',
                    'password' => ! empty($password) ? 'set' : 'missing',
                ]);

                return 1; // Return error exit code
            }

            $requestData = [
                'clientId' => $clientId,
                'username' => $username,
                'password' => $password,
            ];

            Log::info('Authorization Code Request:', [
                'url' => $absApiUrl,
                'data' => $requestData,
                'data_types' => [
                    'clientId' => gettype($clientId),
                    'username' => gettype($username),
                    'password' => gettype($password),
                ],
            ]);

            // Try JSON format with same headers as other Woohoo API calls
            // Use send() method to have more control over the request
            $requestBody = json_encode($requestData);

            Log::info('Authorization Code Request Details:', [
                'url' => $absApiUrl,
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ],
                'body' => $requestBody,
                'body_data' => $requestData,
            ]);

            $authorizationCodeResp = Http::acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->send('POST', $absApiUrl, ['body' => $requestBody]);

            Log::info('Authorization Code Response:', [
                'status_code' => $authorizationCodeResp->status(),
                'data' => $authorizationCodeResp->json(),
            ]);

            // Log::info('Authorization Code Request:', [
            //     'url' => $absApiUrl,
            //     'data' => $authorizationCodeResp->json(),
            //     'status_code' => $authorizationCodeResp->status(),
            // ]);

            if ($authorizationCodeResp->successful()) {
                $authorizationCode = $authorizationCodeResp->json();

                Log::info($authorizationCode);

                $tokenUrl = 'https://'.$woohooUrl.'/oauth2/token';

                // Log URL construction for verification (matching old format)
                Log::info($tokenUrl);

                Log::info('🔍 Verifying Token URL:', [
                    'constructed_token_url' => $tokenUrl,
                    'expected_url' => 'https://sandbox.woohoo.in/oauth2/token',
                    'urls_match' => $tokenUrl === 'https://sandbox.woohoo.in/oauth2/token',
                ]);

                $tokenRequestData = [
                    'clientId' => config('woohoo.client_id'),
                    'clientSecret' => config('woohoo.client_secret'),
                    'authorizationCode' => $authorizationCode['authorizationCode'],
                ];

                Log::info('Token Request Data:', [
                    'url' => $tokenUrl,
                    'data' => $tokenRequestData,
                ]);

                // Try JSON format with same headers as other Woohoo API calls
                $tokenRequestBody = json_encode($tokenRequestData);

                $tokenResp = Http::acceptJson()
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*',
                        'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                    ])
                    ->send('POST', $tokenUrl, ['body' => $tokenRequestBody]);

                Log::info('Token Request:', [
                    'url' => $tokenUrl,
                    'data' => $tokenResp->json(),
                    'status_code' => $tokenResp->status(),
                ]);

                if ($tokenResp->successful()) {
                    $token = $tokenResp->json()['token'];

                    DB::transaction(function () use ($token) {
                        $updateTime = now()->toDateTimeString();
                        $payload = [
                            'display_name' => 'Bearer Token',
                            'value' => $token,
                            'details' => json_encode(['update_time' => $updateTime]),
                            'updated_at' => now(),
                        ];
                        if (DB::table('settings')->where('key', 'woohoo.bearer_token')->doesntExist()) {
                            $payload['created_at'] = now();
                        }
                        DB::table('settings')->updateOrInsert(
                            ['key' => 'woohoo.bearer_token'],
                            $payload,
                        );
                    });
                    $updateTime = Carbon\Carbon::now('Asia/Kolkata')->format('d/m/y H:i:s');
                    Log::info('GenerateBearerToken command ran successfully at:', ['update_time' => $updateTime]);

                    $this->info('Bearer Token generated and stored successfully.');
                    $this->info('Bearer Token: '.$token);
                } else {
                    $statusCode = $tokenResp->status();
                    $responseBody = $tokenResp->body();
                    $responseData = $tokenResp->json();

                    Log::error('Failed to generate Bearer Token.', [
                        'status_code' => $statusCode,
                        'response_body' => $responseBody,
                        'response_data' => $responseData,
                        'url' => $tokenUrl,
                    ]);

                    $this->error('Failed to generate Bearer Token.');
                    $this->error("Status Code: {$statusCode}");
                    if ($responseData) {
                        $this->error('Response: '.json_encode($responseData, JSON_PRETTY_PRINT));
                    } else {
                        $this->error('Response Body: '.substr($responseBody, 0, 200));
                    }

                    return 1; // Return error exit code
                }
            } else {
                $statusCode = $authorizationCodeResp->status();
                $responseBody = $authorizationCodeResp->body();
                $responseData = $authorizationCodeResp->json();

                Log::error('Authorization code verification failed.', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'response_data' => $responseData,
                    'url' => $absApiUrl,
                ]);

                $this->error('Authorization code verification failed.');
                $this->error("Status Code: {$statusCode}");
                if ($responseData) {
                    $this->error('Response: '.json_encode($responseData, JSON_PRETTY_PRINT));
                } else {
                    $this->error('Response Body: '.substr($responseBody, 0, 200));
                }

                return 1; // Return error exit code
            }
        } catch (\Exception $e) {
            Log::error('An error occurred: '.$e->getMessage());
            $this->error('An error occurred: '.$e->getMessage());

            return 1; // Return error exit code
        }

        return 0; // Return success exit code
    }
}
