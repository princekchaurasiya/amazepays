<?php

namespace App\Console\Commands;
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
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/oauth2/verify';

            $requestData = [
                'clientId' => setting('api.clientId'),
                'username' => setting('api.qs_username'),
                'password' => setting('api.qs_password'),
            ];

            Log::info('Authorization Code Request:', [
                'url' => $absApiUrl,
                'data' => $requestData,
            ]);

            $authorizationCodeResp = Http::post($absApiUrl, $requestData);

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

                $tokenUrl = 'https://' . setting('api.woohoo_url') . '/oauth2/token';

                Log::info($tokenUrl);

                $tokenResp = Http::post($tokenUrl, [
                    'clientId' => setting('api.clientId'),
                    'clientSecret' => setting('api.qs_clientSecret'),
                    'authorizationCode' => $authorizationCode['authorizationCode'],
                ]);

                Log::info('Token Request:', [
                    'url' => $tokenUrl,
                    'data' => $tokenResp->json(),
                    'status_code' => $tokenResp->status(),
                ]);

                if ($tokenResp->successful()) {
                    $token = $tokenResp->json()['token'];

                    DB::transaction(function () use ($token) {
                        $updateTime = now()->toDateTimeString();
                        DB::table('settings')->updateOrInsert(
                            ['display_name' => 'Bearer Token'],
                            [
                                'value' => $token,
                                'details' => json_encode(['update_time' => $updateTime]),
                            ],
                        );
                    });

                    Log::info('GenerateBearerToken command ran successfully.');

                    $this->info('Bearer Token generated and stored successfully.');
                    $this->info('Bearer Token: ' . $token);
                } else {
                    Log::error('Failed to generate Bearer Token.');
                    $this->error('Failed to generate Bearer Token.');
                }
            } else {
                Log::error('Authorization code verification failed.');
                $this->error('Authorization code verification failed.');
            }
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
