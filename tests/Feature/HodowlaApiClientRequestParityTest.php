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
            'https://makssnake.pl/api/animals/*' => Http::response([
                'data' => [
                    'animal' => ['id' => 123, 'name' => 'Mamba', 'sex' => 2, 'secret_tag' => 'ABC123'],
                    'genetics' => [],
                    'feedings' => [],
                    'weights' => [],
                    'sheds' => [],
                    'litters' => [],
                    'gallery' => [],
                ],
            ], 200),
        ]);

        $payload = app(HodowlaApiClient::class)->fetchAnimalBySecretTag('  ABC123  ');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://makssnake.pl/api/animals/ABC123'
                && $request->hasHeader('X-API-KEY', 'token-from-system-config')
                && $request->hasHeader('Accept', 'application/json')
                && ! $request->hasHeader('Authorization');
        });

        $this->assertSame('Mamba', $payload['animal']['name']);
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

    public function test_client_can_decode_cp1250_json_body_without_value_error(): void
    {
        config([
            'hodowla.base_url' => 'https://makssnake.pl/api',
            'hodowla.timeout' => 10,
            'hodowla.token' => 'token-from-env',
        ]);
        SystemConfig::setValue('apiDziennik', 'token-from-system-config');

        $utf8Json = '{"data":{"animal":{"id":1,"name":"W\u0105\u017c","secret_tag":"ABC12","sex":2},"genetics":[],"feedings":[],"weights":[],"sheds":[],"litters":[],"gallery":[]}}';
        $cp1250Body = function_exists('iconv') ? iconv('UTF-8', 'CP1250//TRANSLIT', $utf8Json) : false;
        if ($cp1250Body === false) {
            $this->markTestSkipped('iconv CP1250 conversion is not available in this environment.');
        }

        Http::fake([
            'https://makssnake.pl/api/animals/*' => Http::response($cp1250Body, 200, ['Content-Type' => 'application/json']),
        ]);

        $payload = app(HodowlaApiClient::class)->fetchAnimalBySecretTag('ABC12');

        $this->assertSame('W'.json_decode('"\u0105"').json_decode('"\u017c"'), $payload['animal']['name']);
    }

    public function test_client_normalizes_keys_with_spaces_and_unwraps_data(): void
    {
        config([
            'hodowla.base_url' => 'https://makssnake.pl/api',
            'hodowla.timeout' => 10,
            'hodowla.token' => 'token-from-env',
        ]);
        SystemConfig::setValue('apiDziennik', 'token-from-system-config');

        Http::fake([
            'https://makssnake.pl/api/animals/*' => Http::response([
                'data' => [
                    'animal' => [
                        'id' => 55,
                        'name' => '<b>Test</b>',
                        'second name' => 'Alias',
                        'animal type' => ['id' => 1, 'name' => 'Python regius'],
                        'sex' => 2,
                        'secret_tag' => 'QHPU4R',
                    ],
                    'genetics' => [],
                    'feedings' => [],
                    'weights' => [],
                    'sheds' => [],
                    'litters' => [],
                    'gallery' => [],
                ],
            ], 200),
        ]);

        $payload = app(HodowlaApiClient::class)->fetchAnimalBySecretTag('QHPU4R');

        $this->assertSame('Alias', $payload['animal']['second_name']);
        $this->assertSame(1, $payload['animal']['animal_type']['id']);
    }

    public function test_client_handles_bom_and_double_encoded_json_payload(): void
    {
        config([
            'hodowla.base_url' => 'https://makssnake.pl/api',
            'hodowla.timeout' => 10,
            'hodowla.token' => 'token-from-env',
        ]);
        SystemConfig::setValue('apiDziennik', 'token-from-system-config');

        $inner = json_encode([
            'data' => [
                'animal' => ['id' => 77, 'name' => '<b>Mamba</b>', 'second name' => 'Jedynaczka', 'sex' => 2, 'secret_tag' => 'QHPU4R'],
                'genetics' => [],
                'feedings' => [],
                'weights' => [],
                'sheds' => [],
                'litters' => [],
                'gallery' => [],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $body = "\xEF\xBB\xBF".json_encode($inner, JSON_UNESCAPED_UNICODE);

        Http::fake([
            'https://makssnake.pl/api/animals/*' => Http::response($body, 200, ['Content-Type' => 'application/json']),
        ]);

        $payload = app(HodowlaApiClient::class)->fetchAnimalBySecretTag('QHPU4R');

        $this->assertSame('Mamba', $payload['animal']['name']);
        $this->assertSame('Jedynaczka', $payload['animal']['second_name']);
    }

    public function test_client_can_decode_json_wrapped_in_extra_text_noise(): void
    {
        config([
            'hodowla.base_url' => 'https://makssnake.pl/api',
            'hodowla.timeout' => 10,
            'hodowla.token' => 'token-from-env',
        ]);
        SystemConfig::setValue('apiDziennik', 'token-from-system-config');

        $json = json_encode([
            'data' => [
                'animal' => ['id' => 1, 'name' => 'Mamba', 'second name' => 'Alias', 'sex' => 2, 'secret_tag' => 'QHPU4R'],
                'genetics' => [],
                'feedings' => [],
                'weights' => [],
                'sheds' => [],
                'litters' => [],
                'gallery' => [],
            ],
        ], JSON_UNESCAPED_UNICODE);
        $body = "NOTICE: test\n{$json}\nDEBUG_END";

        Http::fake([
            'https://makssnake.pl/api/animals/*' => Http::response($body, 200, ['Content-Type' => 'application/json']),
        ]);

        $payload = app(HodowlaApiClient::class)->fetchAnimalBySecretTag('QHPU4R');

        $this->assertSame('Mamba', $payload['animal']['name']);
        $this->assertSame('Alias', $payload['animal']['second_name']);
    }

    public function test_client_reports_missing_required_payload_key_in_error_message(): void
    {
        config([
            'hodowla.base_url' => 'https://makssnake.pl/api',
            'hodowla.timeout' => 10,
            'hodowla.token' => 'token-from-env',
        ]);
        SystemConfig::setValue('apiDziennik', 'token-from-system-config');

        Http::fake([
            'https://makssnake.pl/api/animals/*' => Http::response([
                'data' => [
                    'animal' => ['id' => 123, 'name' => 'Mamba', 'sex' => 2, 'secret_tag' => 'ABC123'],
                    'genetics' => [],
                    // feedings missing on purpose
                    'weights' => [],
                    'sheds' => [],
                    'litters' => [],
                    'gallery' => [],
                ],
            ], 200),
        ]);

        $this->expectException(HodowlaApiException::class);
        $this->expectExceptionMessage('missing payload.feedings');
        $this->expectExceptionMessage('json_error=');

        app(HodowlaApiClient::class)->fetchAnimalBySecretTag('ABC123');
    }
}
