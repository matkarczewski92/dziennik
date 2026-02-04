<?php

namespace App\Services;

use App\Exceptions\HodowlaApiException;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Http;

class HodowlaApiClient
{
    public function fetchAnimalBySecretTag(string $secretTag): array
    {
        $apiToken = SystemConfig::getValue('apiDziennik');

        if (! $apiToken) {
            throw new HodowlaApiException('Brak skonfigurowanego tokenu API. Skontaktuj sie z administratorem.');
        }

        $response = Http::baseUrl(config('hodowla.base_url'))
            ->timeout((int) config('hodowla.timeout'))
            ->withHeader('X-API-KEY', $apiToken)
            ->get('/api/animals/'.urlencode($secretTag));

        return match ($response->status()) {
            200 => $this->validatedPayload($response->json()),
            401 => throw new HodowlaApiException('Integracja API zwrocila 401 (nieprawidlowy token API).'),
            403 => throw new HodowlaApiException('Integracja API zwrocila 403 (brak uprawnien).'),
            404 => throw new HodowlaApiException('Nie znaleziono zwierzecia dla podanego secret_tag (404).'),
            429 => throw new HodowlaApiException('API hodowli ma limit zapytan (429). Sprobuj ponownie za chwile.'),
            default => throw new HodowlaApiException('Nie udalo sie pobrac danych z API hodowli.'),
        };
    }

    protected function validatedPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw new HodowlaApiException('API zwrocilo nieprawidlowy format danych.');
        }

        return $payload;
    }
}

