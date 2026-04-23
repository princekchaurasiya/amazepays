<?php

namespace Database\Seeders;

use App\Models\GiftCardTheme;
use Illuminate\Database\Seeder;

class GiftCardThemeSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $themes */
        $themes = [
            [
                'name' => 'Congratulations',
                'slug' => 'congratulations',
                'gallery_images' => [
                    'gift-themes/default-congratulations.jpg',
                    'gift-themes/default-congratulations-thumb.jpg',
                ],
                'is_active' => true,
                'sort_order' => 10,
                'metadata' => ['category' => 'celebration'],
            ],
            [
                'name' => 'Thank You',
                'slug' => 'thank-you',
                'gallery_images' => [
                    'gift-themes/default-thank-you.jpg',
                    'gift-themes/default-thank-you-thumb.jpg',
                ],
                'is_active' => true,
                'sort_order' => 20,
                'metadata' => ['category' => 'gratitude'],
            ],
            [
                'name' => 'Anniversary',
                'slug' => 'anniversary',
                'gallery_images' => [
                    'gift-themes/default-anniversary.jpg',
                    'gift-themes/default-anniversary-thumb.jpg',
                ],
                'is_active' => true,
                'sort_order' => 30,
                'metadata' => ['category' => 'occasion'],
            ],
            [
                'name' => 'Birthday',
                'slug' => 'birthday',
                'gallery_images' => [
                    'gift-themes/default-birthday.jpg',
                    'gift-themes/default-birthday-thumb.jpg',
                ],
                'is_active' => true,
                'sort_order' => 40,
                'metadata' => ['category' => 'occasion'],
            ],
            [
                'name' => 'Superstar',
                'slug' => 'superstar',
                'gallery_images' => [
                    'gift-themes/default-superstar.jpg',
                    'gift-themes/default-superstar-thumb.jpg',
                ],
                'is_active' => true,
                'sort_order' => 50,
                'metadata' => ['category' => 'recognition'],
            ],
        ];

        foreach ($themes as $theme) {
            GiftCardTheme::updateOrCreate(
                ['slug' => $theme['slug']],
                $theme
            );
        }

        $this->command?->info('Gift card themes seeded successfully.');
    }
}
