<?php

namespace App\Console\Commands;

use App\Http\Services\VDWebApiService;
use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

            $tenantId = 1;
            if (Schema::hasTable('tenants')) {
                $tenantId = (int) (DB::table('tenants')->orderBy('id')->value('id') ?? 1);
            }

            foreach ($brandsArray as $brandData) {
                $sourceBrandId = (string) ($brandData['BrandCode'] ?? '');
                $brandName = (string) ($brandData['BrandName'] ?? '');
                if ($sourceBrandId === '' || $brandName === '') {
                    continue;
                }

                $brand = Brand::updateOrCreate(
                    ['source_provider' => 'value_design', 'source_brand_id' => $sourceBrandId],
                    [
                        'tenant_id' => $tenantId,
                        'name' => $brandName,
                        'slug' => Str::slug($brandName) ?: Str::slug($sourceBrandId),
                        'status' => 'active',
                        'is_featured' => false,
                        'display_order' => 0,
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
