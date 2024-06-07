<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CommonHelper;
use App\Models\QsProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon;
use App\Models\QsCategory;
use DB;

class FetchCategoryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:categoryData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Category Data From Woohoo Server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // $categoryId = 121;
            $requestBody = '';
            $requestHttpMethod = 'get';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/';
            // $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/' . $categoryId;
            // dd($absApiUrl);
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            Log::info('Category Request:', [
                'url' => $absApiUrl,
                'method' => $requestHttpMethod,
                'data' => [
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ],
            ]);

            $category_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);

            Log::info('Category Response:', [
                'status_code' => $category_resp->status(),
                'data' => $category_resp->json(),
            ]);

            // dd($category_resp->status());
            if ($category_resp->status() == 200) {
                // If the API response status is 200, save category data into the database
                $category_resp = $category_resp->json($key = null);
                $data = [
                    'id' => $category_resp['id'],
                    'name' => $category_resp['name'],
                    'url' => $category_resp['url'],
                    'description' => $category_resp['description'],
                    'images' => json_encode($category_resp['images']),
                    'subcategoriesCount' => $category_resp['subcategoriesCount'],
                    'subcategories' => json_encode($category_resp['subcategories']),
                ];

                // Update or insert the category data into the 'qs_categories' table based on the ID
                DB::table('qs_categories')->updateOrInsert(['id' => $category_resp['id']], $data);
                $updateTime = Carbon\Carbon::now('Asia/Kolkata')->format('d/m/y H:i:s');
                Log::info('Category data updated in the database at:', ['update_time' => $updateTime]);
                $this->info('Category stored successfully in the database.');
                return json_encode(['status' => 200, 'data' => 'Stored Successfully']);
            } else {
                Log::error('Something went wrong while fetching the category.', ['error' => $category_resp->body()]);
                $this->info($e->$category_resp->body());
                return json_encode(['status' => 400, 'data' => 'Something went wrong']);
            }
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            $this->error($e->getMessage());
            return $e->getMessage();
        }
    }
}
