<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_api_health_endpoint_returns_a_successful_response(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Backend API is ready',
                'data' => [
                    'service' => 'mini-hris-api',
                    'status' => 'ok',
                ],
            ]);
    }
}
