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
    protected $signature = 'fetch:categoryData';
    protected $description = 'Fetch Category Data From Woohoo Server';
    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        try {
            $requestBody = '';
            $requestHttpMethod = 'get';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/';
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();
            Log::info('Category Request:', ['url' => $absApiUrl, 'method' => $requestHttpMethod, 'data' => ['dateAtClient' => $dateAtClient, 'signature' => $signature,],]);
            
            // Use same headers as other Woohoo API calls to avoid CDN blocking
            $category_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);
            Log::info('Category Response:', ['status_code' => $category_resp->status(), 'data' => $category_resp->json(),]);
            if ($category_resp->status() == 200) {
                $category_resp = $category_resp->json($key = null);
                $data = ['id' => $category_resp['id'], 'name' => $category_resp['name'], 'url' => $category_resp['url'], 'description' => $category_resp['description'], 'images' => json_encode($category_resp['images']), 'subcategoriesCount' => $category_resp['subcategoriesCount'], 'subcategories' => json_encode($category_resp['subcategories']),];
                DB::table('qs_categories')->updateOrInsert(['id' => $category_resp['id']], $data);
                $updateTime = Carbon\Carbon::now('Asia/Kolkata')->format('d/m/y H:i:s');
                Log::info('Category data updated in the database at:', ['update_time' => $updateTime]);
                $this->info('Category stored successfully in the database.');
                return json_encode(['status' => 200, 'data' => 'Stored Successfully']);
            } else {
                Log::error('Something went wrong while fetching the category.', ['error' => $category_resp->body()]);
                $this->info($category_resp->body());
                return json_encode(['status' => 400, 'data' => 'Something went wrong']);
            }
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            $this->error($e->getMessage());
            return $e->getMessage();
        }
    }
}
