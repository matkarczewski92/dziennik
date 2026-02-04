<?php

namespace App\Services;

use App\Exceptions\HodowlaApiException;
use App\Models\SystemConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HodowlaApiClient
{
    public function fetchAnimalBySecretTag(string $secretTag): array
    {
        $secretTag = trim($secretTag);
        $this->assertSecretTagFormat($secretTag);

        $apiToken = $this->resolveApiToken();
        if ($apiToken === '') {
            throw new HodowlaApiException('Brak skonfigurowanego tokenu API. Skontaktuj sie z administratorem.');
        }

        $url = rtrim((string) config('hodowla.base_url'), '/').'/animals/'.rawurlencode($secretTag);

        $response = Http::withHeaders([
                'X-API-KEY' => $apiToken,
                'Accept' => 'application/json',
            ])
            ->timeout((int) config('hodowla.timeout'))
            ->get($url);

        if ($response->successful()) {
            return $this->validatedPayload($response->json());
        }

        $this->throwWithDiagnostics($response, $url, $secretTag);
    }

    protected function validatedPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw new HodowlaApiException('API zwrocilo nieprawidlowy format danych.');
        }

        return $payload;
    }

    protected function resolveApiToken(): string
    {
        $token = (string) SystemConfig::getValue('apiDziennik', (string) config('hodowla.token'));
        $token = trim($token);

        if ($token === '') {
            $token = trim((string) config('hodowla.token'));
        }

        return $token;
    }

    protected function assertSecretTagFormat(string $secretTag): void
    {
        if (! preg_match('/^[a-zA-Z0-9]{5,10}$/', $secretTag)) {
            throw new HodowlaApiException('secret_tag musi byc alfanumeryczny i miec dlugosc 5-10 znakow.');
        }
    }

    protected function throwWithDiagnostics(Response $response, string $url, string $secretTag): never
    {
        $status = $response->status();
        $body = Str::limit($response->body(), 3000);

        Log::warning('Hodowla API error', [
            'url' => $url,
            'secret_tag' => $secretTag,
            'status' => $status,
            'body' => $body,
        ]);

        $message = match ($status) {
            401 => 'Integracja API zwrocila 401 (nieprawidlowy token API).',
            403 => 'Integracja API zwrocila 403 (brak uprawnien).',
            404 => 'Nie znaleziono zwierzecia dla podanego secret_tag. (404)',
            429 => 'API hodowli ma limit zapytan (429). Sprobuj ponownie za chwile.',
            default => 'Nie udalo sie pobrac danych z API hodowli.',
        };

        throw new HodowlaApiException(
            $message." Diagnostyka: url={$url}; secret_tag={$secretTag}; status={$status}; body={$body}"
        );
    }
}
