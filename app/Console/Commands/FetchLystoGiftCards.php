<?php

namespace App\Console\Commands;

use App\Http\Services\AthenaGiftCardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchLystoGiftCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:lysto-gift-cards {--brand=} {--page=0} {--size=20}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Lysto (Athena) gift cards from API';

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
            $baseUrl = env('LYSTO_BASE_URL', 'https://stagedistapi.lysto.io/api/v1');

            if (empty($apiKey) || empty($partnerId)) {
                $this->error('❌ Lysto API credentials are not configured. Please check your .env file.');
                Log::error('FetchLystoGiftCards: Missing API credentials', [
                    'has_api_key' => ! empty($apiKey),
                    'has_partner_id' => ! empty($partnerId),
                ]);

                return Command::FAILURE;
            }

            $brand = $this->option('brand');
            $pageNumber = (int) $this->option('page');
            $pageSize = (int) $this->option('size');

            $this->info('🔄 Fetching Lysto gift cards...');

            if ($brand) {
                $this->line("Brand filter: {$brand}");
            }
            $this->line("Page: {$pageNumber}, Page Size: {$pageSize}");

            Log::info('FetchLystoGiftCards: API request', [
                'base_url' => $baseUrl,
                'brand' => $brand,
                'page_number' => $pageNumber,
                'page_size' => $pageSize,
            ]);

            $service = new AthenaGiftCardService;
            $response = $service->listGiftCards($brand, $pageNumber, $pageSize);

            if (! isset($response['status']) || $response['status'] !== 200) {
                $this->error('❌ Invalid response format or status.');
                Log::error('FetchLystoGiftCards: Invalid response', ['response' => $response]);

                return Command::FAILURE;
            }

            $giftCards = $response['giftcards'] ?? [];
            $totalCards = count($giftCards);

            $this->info("✅ Successfully fetched {$totalCards} gift card(s)");
            $this->line('');

            if ($totalCards > 0) {
                $this->line('Gift Cards:');
                $this->table(
                    ['ID', 'Name', 'Brand', 'Status'],
                    array_map(function ($card) {
                        return [
                            $card['id'] ?? 'N/A',
                            $card['name'] ?? 'N/A',
                            $card['brand'] ?? 'N/A',
                            $card['status'] ?? 'N/A',
                        ];
                    }, array_slice($giftCards, 0, 10)) // Show first 10
                );

                if ($totalCards > 10) {
                    $this->line('... and '.($totalCards - 10).' more gift cards');
                }
            } else {
                $this->warn('No gift cards found.');
            }

            Log::info('FetchLystoGiftCards: Gift cards fetched successfully', [
                'total_cards' => $totalCards,
                'page_number' => $pageNumber,
                'page_size' => $pageSize,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: '.$e->getMessage());
            Log::error('FetchLystoGiftCards: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
