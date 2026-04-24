<?php

namespace Tests\Guards;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class KycGateContractTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function kyc_thresholds_table_exists(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('kyc_thresholds'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('order_kyc_requirements'));
    }
}

