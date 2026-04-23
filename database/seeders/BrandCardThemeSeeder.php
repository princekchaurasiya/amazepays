<?php

namespace Database\Seeders;

use App\Models\BrandCardTheme;
use Illuminate\Database\Seeder;

class BrandCardThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'brand_name' => 'Bigbasket',
                'logo_url' => 'https://images.woohoo.in/brands/bigbasket-logo.png',
                'bg_color' => '#a3cf3a',
                'text_color' => '#111827',
                'accent_color' => '#0f5132',
                'is_active' => true,
                'priority' => 10,
            ],
            [
                'brand_name' => 'Amazon',
                'logo_url' => 'https://images.woohoo.in/brands/amazon-pay-logo.png',
                'bg_color' => '#f3f4f6',
                'text_color' => '#111827',
                'accent_color' => '#1f2937',
                'is_active' => true,
                'priority' => 20,
            ],
        ];

        foreach ($themes as $theme) {
            BrandCardTheme::updateOrCreate(
                [
                    'brand_name' => $theme['brand_name'],
                    'priority' => $theme['priority'],
                ],
                $theme
            );
        }

        $this->command?->info('Brand card themes seeded successfully.');
    }
}
