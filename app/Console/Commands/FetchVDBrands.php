<?php

namespace App\Console\Commands;

use App\Http\Services\VDWebApiService;
use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchVDBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:value-design-brands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store Value Design brands from API';

    protected $baseUrl = 'https://at.valuedesign.co.in/distributor/';

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

            if (! $token) {
                $this->error('❌ Failed to get Value Design token');
                Log::error('FetchVDBrands: Failed to get token');

                return Command::FAILURE;
            }

            $this->info('✅ Token retrieved successfully');
            Log::info('FetchVDBrands: Token retrieved', ['token_length' => strlen($token)]);

            $this->info('🔄 Fetching brands from Value Design API...');

            $response = Http::withHeaders([
                'token' => $token,
            ])->post($this->baseUrl.'api-getbrand/', [
                'BrandCode' => '', // Empty string to fetch all brands
            ]);

            if (! $response->successful()) {
                $this->error('❌ Failed to fetch brands. Status Code: '.$response->status());
                Log::error('FetchVDBrands: API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return Command::FAILURE;
            }

            $brands = $response->json();
            $encryptedBrandData = $brands['data'] ?? null;

            if (! $encryptedBrandData) {
                $this->error('❌ No data field in response');
                Log::error('FetchVDBrands: No data field in response', ['response' => $brands]);

                return Command::FAILURE;
            }

            $this->info('🔄 Decrypting brand data...');
            $decryptedBrandData = $this->vdWebApiService->decryptAES($encryptedBrandData);
            $brandsArray = json_decode($decryptedBrandData, true);

            if (! is_array($brandsArray)) {
                $this->error('❌ Invalid JSON data for brands');
                Log::error('FetchVDBrands: Invalid JSON data', ['decrypted_data' => $decryptedBrandData]);

                return Command::FAILURE;
            }

            $this->info('🔄 Storing brands in database...');
            $storedCount = 0;
            $updatedCount = 0;

            foreach ($brandsArray as $brandData) {
                $brand = Brand::updateOrCreate(
                    ['brand_code' => $brandData['BrandCode']],
                    [
                        'brand_name' => $brandData['BrandName'] ?? null,
                        'brand_type' => $brandData['Brandtype'] ?? null,
                        'discount' => $brandData['Discount'] ?? null,
                        'min_price' => $brandData['minPrice'] ?? null,
                        'max_price' => $brandData['maxPrice'] ?? null,
                        'denomination_list' => $brandData['DenominationList'] ?? null,
                        'stock_available' => ! empty($brandData['StockAvailable']) ? (int) $brandData['StockAvailable'] : 0,
                        'category' => $brandData['Category'] ?? null,
                        'description' => $brandData['Description'] ?? null,
                        'images' => json_decode($brandData['Images'], true) ?: null,
                        'tnc' => $brandData['TnC'] ?? null,
                        'important_instruction' => $brandData['ImportantInstruction'] ?? null,
                        'redeem_steps' => $brandData['RedeemSteps'] ?? null,
                    ]
                );

                if ($brand->wasRecentlyCreated) {
                    $storedCount++;
                } else {
                    $updatedCount++;
                }
            }

            $totalCount = count($brandsArray);
            $this->info("✅ Successfully processed {$totalCount} brands (New: {$storedCount}, Updated: {$updatedCount})");

            Log::info('FetchVDBrands: Brands stored successfully', [
                'total_count' => $totalCount,
                'new_count' => $storedCount,
                'updated_count' => $updatedCount,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: '.$e->getMessage());
            Log::error('FetchVDBrands: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
