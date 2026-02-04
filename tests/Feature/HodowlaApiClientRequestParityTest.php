<?php

namespace Tests\Feature;

use App\Exceptions\HodowlaApiException;
use App\Models\SystemConfig;
use App\Services\HodowlaApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HodowlaApiClientRequestParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_request_matches_reference_powershell_request(): void
    {
        config([
            'hodowla.base_url' => 'https://makssnake.pl/api',
            'hodowla.timeout' => 10,
            'hodowla.token' => 'token-from-env',
        ]);
        SystemConfig::setValue('apiDziennik', 'token-from-system-config');

        Http::fake([
            'https://makssnake.pl/api/animals/*' => Http::response(['id' => 123], 200),
        ]);

        app(HodowlaApiClient::class)->fetchAnimalBySecretTag('  ABC123  ');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://makssnake.pl/api/animals/ABC123'
                && $request->hasHeader('X-API-KEY', 'token-from-system-config')
                && $request->hasHeader('Accept', 'application/json')
                && ! $request->hasHeader('Authorization');
        });
    }

    public function test_http_error_contains_diagnostics_url_tag_status_and_body(): void
    {
        config([
            'hodowla.base_url' => 'https://makssnake.pl/api',
            'hodowla.timeout' => 10,
            'hodowla.token' => 'token-from-env',
        ]);
        SystemConfig::setValue('apiDziennik', 'token-from-system-config');

        Http::fake([
            'https://makssnake.pl/api/animals/*' => Http::response(['message' => 'Not found'], 404),
        ]);

        try {
            app(HodowlaApiClient::class)->fetchAnimalBySecretTag('ABC123');
            $this->fail('Expected HodowlaApiException was not thrown.');
        } catch (HodowlaApiException $exception) {
            $this->assertStringContainsString('url=https://makssnake.pl/api/animals/ABC123', $exception->getMessage());
            $this->assertStringContainsString('secret_tag=ABC123', $exception->getMessage());
            $this->assertStringContainsString('status=404', $exception->getMessage());
            $this->assertStringContainsString('Not found', $exception->getMessage());
        }
    }
}

