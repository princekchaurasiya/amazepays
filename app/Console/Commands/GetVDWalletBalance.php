<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Services\VDWebApiService;

class GetVDWalletBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:vdWalletBalance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Value Design wallet balance';

    protected $vdWebApiService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(VDWebApiService $vdWebApiService)
    {
        parent::__construct();
        $this->vdWebApiService = $vdWebApiService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('🔄 Fetching Value Design token...');
            $token = $this->vdWebApiService->getToken();
            
            if (!$token) {
                $this->error('❌ Failed to get Value Design token');
                Log::error('GetVDWalletBalance: Failed to get token');
                return Command::FAILURE;
            }

            $this->info('✅ Token retrieved successfully');
            Log::info('GetVDWalletBalance: Token retrieved', ['token_length' => strlen($token)]);

            $this->info('🔄 Fetching wallet balance from Value Design API...');
            
            $response = Http::withHeaders([
                'token' => $token, 
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('http://cards.vdwebapi.com/distributor/getwalletbalance/', [
                'distributor_id' => env('DISTRIBUTOR_ID', 'VDIDAmazepay'),
            ]);

            if (!$response->successful()) {
                $this->error('❌ Failed to fetch wallet balance. Status Code: ' . $response->status());
                Log::error('GetVDWalletBalance: API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
                return Command::FAILURE;
            }

            $data = $response->json();
            
            $this->info('✅ Wallet balance retrieved successfully');
            $this->line('');
            $this->line('Wallet Balance Details:');
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            
            Log::info('GetVDWalletBalance: Wallet balance retrieved', ['data' => $data]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            Log::error('GetVDWalletBalance: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
