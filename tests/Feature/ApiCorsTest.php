<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiCorsTest extends TestCase
{
    public function test_preflight_returns_the_exact_trusted_origin(): void
    {
        foreach ([
            'https://crm.uniquzsnapbox.com',
            'http://localhost:8082',
            'http://127.0.0.1:8082',
        ] as $origin) {
            $response = $this->withHeaders([
                'Origin' => $origin,
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'authorization,content-type',
            ])->options('/api/lead-contacts');

            $response->assertNoContent();
            $this->assertSame($origin, $response->headers->get('Access-Control-Allow-Origin'));
            $this->assertStringContainsString('POST', (string) $response->headers->get('Access-Control-Allow-Methods'));
            $this->assertStringContainsString('DELETE', (string) $response->headers->get('Access-Control-Allow-Methods'));
            $allowHeaders = strtolower((string) $response->headers->get('Access-Control-Allow-Headers'));
            $this->assertStringContainsString('authorization', $allowHeaders);
            $this->assertStringContainsString('content-type', $allowHeaders);
        }
    }

    public function test_preflight_does_not_allow_an_unknown_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://unknown.example',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('/api/lead-contacts');

        $response->assertNoContent();
        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }
}
