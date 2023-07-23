<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;


class GenerateBearerToken extends Command
{
    protected $signature = 'generate:bearerToken';
    protected $description = 'Generate a bearer token using the authorization code';

    public function handle()
    {
        $authorizationCode_resp = Http::post('https://sandbox.woohoo.in/oauth2/verify', [
            'clientId' => setting('api.clientId'), //coming from database
            'username' => setting('api.qs_username'), //coming from database
            'password' => setting('api.qs_password'), //coming from database
        ]);

        if ($authorizationCode_resp->status() == 200) {
            $authorizationCode = $authorizationCode_resp->json();

            $token_resp = Http::post('https://sandbox.woohoo.in/oauth2/token', [
                'clientId' => setting('api.clientId'), //coming from database
                'clientSecret' => setting('api.qs_clientSecret'), //coming from database
                'authorizationCode' => $authorizationCode['authorizationCode'],
            ]);

            if ($token_resp->status() == 200) {
                $token = $token_resp->json()['token'];

                // Save token and update time in the settings table
                $updateTime = now()->toDateTimeString();
                DB::table('settings')->updateOrInsert(
                    ['display_name' => 'Bearer Token'],
                    [
                        'value' => $token,
                        'details' => json_encode(['update_time' => $updateTime]),
                    ]
                );

                // Display success message with token and update time
                $this->info('Bearer Token generated and stored successfully.');
                $this->info('Bearer Token: ' . $token);
                $this->info('Update Time: ' . $updateTime);
            } else {
                $this->error('Failed to generate Bearer Token.');
            }
        } else {
            $this->error('Authorization code verification failed.');
        }
    }
}