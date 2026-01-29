<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Services\AthenaGiftCardService;

class GetLystoWalletBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:lystoWalletBalance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Lysto (Athena) wallet balance from API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // Validate environment variables
            $apiKey = env('LYSTO_API_KEY');
            $partnerId = env('LYSTO_PARTNER_ID');

            if (empty($apiKey) || empty($partnerId)) {
                $this->error('❌ Lysto API credentials are not configured. Please check your .env file.');
                Log::error('GetLystoWalletBalance: Missing API credentials', [
                    'has_api_key' => !empty($apiKey),
                    'has_partner_id' => !empty($partnerId)
                ]);
                return Command::FAILURE;
            }

            $this->info('🔄 Fetching Lysto wallet balance...');
            
            Log::info('GetLystoWalletBalance: API request');

            $service = new AthenaGiftCardService();
            $response = $service->getWalletBalance();

            if (!isset($response['balance'])) {
                $this->error('❌ Invalid response format. Balance field not found.');
                Log::error('GetLystoWalletBalance: Invalid response format', ['response' => $response]);
                return Command::FAILURE;
            }

            $balance = $response['balance'];
            $currency = $response['currency'] ?? 'INR';

            $this->info('✅ Wallet balance retrieved successfully');
            $this->line('');
            $this->line('Wallet Balance Details:');
            $this->line('Balance: ' . number_format($balance, 2) . ' ' . $currency);
            
            if (isset($response['currency'])) {
                $this->line('Currency: ' . $response['currency']);
            }
            
            Log::info('GetLystoWalletBalance: Wallet balance retrieved', [
                'balance' => $balance,
                'currency' => $currency
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            Log::error('GetLystoWalletBalance: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
