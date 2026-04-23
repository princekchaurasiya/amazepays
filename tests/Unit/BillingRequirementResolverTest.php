<?php

namespace Tests\Unit;

use App\Support\BillingRequirementResolver;
use Tests\TestCase;

class BillingRequirementResolverTest extends TestCase
{
    public function test_returns_strict_fields_for_upi_woohoo(): void
    {
        $resolver = new BillingRequirementResolver;

        $fields = $resolver->requiredFields('upi', 'woohoo');

        $this->assertSame([
            'billing_name',
            'billing_email',
            'billing_address',
            'billing_city',
            'billing_state',
            'billing_zip',
            'billing_country',
        ], $fields);
    }

    public function test_returns_ccavenue_strict_profile_fields(): void
    {
        $resolver = new BillingRequirementResolver;

        $fields = $resolver->requiredFields('ccavenue', 'woohoo');

        $this->assertContains('billing_address_two', $fields);
        $this->assertContains('billing_tel', $fields);
    }

    public function test_returns_minimal_fields_for_wallet_flow(): void
    {
        $resolver = new BillingRequirementResolver;

        $fields = $resolver->requiredFields('wallet', 'vouchagram');

        $this->assertSame([
            'billing_name',
            'billing_email',
        ], $fields);
    }
}

