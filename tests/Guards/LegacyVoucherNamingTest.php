<?php

declare(strict_types=1);

namespace Tests\Guards;

use Tests\TestCase;

final class LegacyVoucherNamingTest extends TestCase
{
    public function test_legacy_provider_controllers_are_removed(): void
    {
        // Use autoload=false so Composer won't attempt to include deleted files.
        $this->assertFalse(class_exists(\App\Http\Controllers\VDPaymentController::class, false));
        $this->assertFalse(class_exists(\App\Http\Controllers\KGenPaymentController::class, false));
        $this->assertFalse(class_exists(\App\Http\Controllers\AthenaGiftCardController::class, false));
        $this->assertFalse(class_exists(\App\Http\Controllers\VDPageController::class, false));
        $this->assertFalse(class_exists(\App\Http\Controllers\VDHomeController::class, false));
        $this->assertFalse(class_exists(\App\Http\Controllers\VDWebController::class, false));
        $this->assertFalse(class_exists(\App\Http\Controllers\DeliveryPartnerController::class, false));
        $this->assertFalse(class_exists(\App\Http\Controllers\GetEvcRequestController::class, false));
    }

    public function test_legacy_provider_routes_are_gone(): void
    {
        // Old public payment endpoints
        // Phase 4: temporarily keep these as redirects for one release.
        $this->get('/vd-payment')->assertStatus(308);
        $this->get('/kgen-payment/initiate')->assertStatus(308);
        $this->get('/kgen-payment/success/1')->assertStatus(308);
        $this->get('/kgen-payment/failed/1')->assertStatus(308);

        // Old admin endpoints
        $this->get('/admin/lysto/giftcards')->assertStatus(404);
        $this->get('/admin/kgen/wallet')->assertStatus(404);
    }
}

