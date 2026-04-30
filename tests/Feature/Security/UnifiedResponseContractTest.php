<?php

namespace Tests\Feature\Security;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UnifiedResponseContractTest extends TestCase
{
    #[Test]
    public function api_health_uses_flat_contract(): void
    {
        $res = $this->getJson('/api/v1/health');

        $res->assertOk();
        $res->assertJsonStructure([
            'success',
            'code',
            'message_key',
            'message',
            'data',
            'details',
            'meta',
        ]);

        $payload = $res->json();

        $this->assertTrue($payload['success']);
        $this->assertSame('OK', $payload['code']);
        $this->assertIsString($payload['message_key']);
        $this->assertNotSame('', trim((string) $payload['message']));
        $this->assertIsArray($payload['data']);
        $this->assertIsArray($payload['details']);
        $this->assertIsArray($payload['meta']);
    }

    #[Test]
    public function validation_errors_use_flat_contract_and_details_object(): void
    {
        $res = $this->postJson('/api/v1/auth/otp/send', [
            'mobile' => '123',
        ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'code' => 'VALIDATION_FAILED',
            'message_key' => 'error.validation_failed',
        ]);

        $res->assertJsonStructure([
            'details' => ['mobile'],
        ]);
    }

    #[Test]
    public function unauthenticated_errors_use_flat_contract(): void
    {
        $res = $this->getJson('/api/v1/auth/me');

        $res->assertStatus(401);
        $res->assertJson([
            'success' => false,
            'code' => 'UNAUTHENTICATED',
            'message_key' => 'error.unauthenticated',
        ]);
    }
}

