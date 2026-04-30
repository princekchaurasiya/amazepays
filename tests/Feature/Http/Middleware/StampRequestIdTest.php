<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StampRequestIdTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function it_echoes_client_supplied_request_id_in_response_header(): void
    {
        $clientId = 'rn-test-'.fake()->uuid();

        $response = $this->withHeaders(['X-Request-Id' => $clientId])
            ->getJson('/api/v1/health');

        $response->assertOk();
        $this->assertSame($clientId, $response->headers->get('X-Request-Id'));
    }

    #[Test]
    public function it_generates_a_uuid_request_id_when_none_is_supplied(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();

        $id = $response->headers->get('X-Request-Id');
        $this->assertNotNull($id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id,
            'Expected a valid UUID v4 as the generated request ID.'
        );
    }

    #[Test]
    public function it_attaches_request_id_to_all_api_responses(): void
    {
        // Confirm the header appears even on non-auth endpoints.
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Request-Id'));
    }
}
