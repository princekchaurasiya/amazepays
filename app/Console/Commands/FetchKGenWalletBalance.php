<?php

namespace App\Console\Commands;

use App\Models\KGenWalletBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchKGenWalletBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:kgen-distributor-wallet-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store KGen wallet balance from API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // Validate environment variables
            $clientId = env('EXLR8_USER_ID');
            $clientSecret = env('EXLR8_USER_SECRET');
            $baseUrl = env('EXLR8_BASE_URL');
            $dpID = env('dpID');

            if (empty($clientId) || empty($clientSecret) || empty($baseUrl) || empty($dpID)) {
                $this->error('❌ KGen API credentials are not configured. Please check your .env file.');
                Log::error('FetchKGenWalletBalance: Missing API credentials', [
                    'has_client_id' => ! empty($clientId),
                    'has_client_secret' => ! empty($clientSecret),
                    'has_base_url' => ! empty($baseUrl),
                    'has_dp_id' => ! empty($dpID),
                ]);

                return Command::FAILURE;
            }

            $this->info('🔄 Fetching KGen wallet balance...');

            $url = $baseUrl.'/delivery-partners/'.$dpID.'/wallet/balance';

            Log::info('FetchKGenWalletBalance: API request', [
                'url' => $url,
                'dp_id' => $dpID,
            ]);

            $response = Http::withHeaders([
                'x-client-id' => $clientId,
                'x-client-secret' => $clientSecret,
            ])->get($url);

            if (! $response->successful()) {
                $this->error('❌ Failed to fetch wallet balance. Status Code: '.$response->status());
                Log::error('FetchKGenWalletBalance: API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return Command::FAILURE;
            }

            $data = $response->json();

            if (! isset($data['balance'])) {
                $this->error('❌ Invalid response format. Balance field not found.');
                Log::error('FetchKGenWalletBalance: Invalid response format', ['response' => $data]);

                return Command::FAILURE;
            }

            $this->info('🔄 Storing wallet balance in database...');

            // Store the balance
            $walletBalance = KGenWalletBalance::create([
                'balance' => $data['balance'],
                'currency' => $data['currency'] ?? 'INR',
            ]);

            $this->info('✅ Wallet balance fetched and stored successfully');
            $this->line('');
            $this->line('Wallet Balance Details:');
            $this->line('Balance: '.number_format($walletBalance->balance, 2).' '.($walletBalance->currency ?? 'INR'));
            $this->line('Stored at: '.$walletBalance->created_at->format('Y-m-d H:i:s'));

            Log::info('FetchKGenWalletBalance: Wallet balance stored successfully', [
                'balance' => $walletBalance->balance,
                'currency' => $walletBalance->currency,
                'record_id' => $walletBalance->id,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: '.$e->getMessage());
            Log::error('FetchKGenWalletBalance: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
