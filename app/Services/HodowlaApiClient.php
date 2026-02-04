<?php

namespace App\Services;

use App\Exceptions\HodowlaApiException;
use App\Models\SystemConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
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
            return $this->validatedPayload($response, $url, $secretTag);
        }

        $this->throwWithDiagnostics($response, $url, $secretTag);
    }

    protected function validatedPayload(Response $response, string $url, string $secretTag): array
    {
        try {
            $decoded = $this->decodeJsonBody((string) $response->body());
        } catch (HodowlaApiException $exception) {
            $this->throwInvalidSuccessPayload(
                $url,
                $secretTag,
                $response,
                'decode_json_failed',
                json_last_error_msg(),
                $exception->getMessage(),
            );
        }

        $decoded = $this->normalizePayloadKeys($decoded);
        $payload = Arr::get($decoded, 'data', $decoded);

        if (! is_array($payload)) {
            $this->throwInvalidSuccessPayload(
                $url,
                $secretTag,
                $response,
                'payload (decoded[data] ?? decoded) is not an array/object',
                json_last_error_msg(),
            );
        }

        $failedCondition = $this->validateMinimalPayloadStructure($payload);
        if ($failedCondition !== null) {
            $this->throwInvalidSuccessPayload($url, $secretTag, $response, $failedCondition, json_last_error_msg());
        }

        $payload = $this->sanitizeAnimalTextFields($payload);

        return $payload;
    }

    protected function decodeJsonBody(string $body): array
    {
        if (str_starts_with($body, "\xEF\xBB\xBF")) {
            $body = substr($body, 3);
        }

        $body = trim($body);
        $decoded = $this->decodePossiblyDoubleEncodedJson($body);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (function_exists('mb_convert_encoding')) {
            $fallbackEncodings = $this->resolveSupportedMbConvertEncodings([
                'UTF-8',
                'Windows-1250',
                'ISO-8859-2',
                'ISO-8859-1',
            ]);

            if ($fallbackEncodings !== []) {
                try {
                    $converted = mb_convert_encoding($body, 'UTF-8', $fallbackEncodings);
                } catch (\ValueError) {
                    $converted = null;
                }

                if (is_string($converted)) {
                    $decoded = $this->decodePossiblyDoubleEncodedJson(trim($converted));
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }
        }

        $candidate = $this->extractJsonCandidate($body);
        if ($candidate !== null) {
            $decoded = $this->decodePossiblyDoubleEncodedJson($candidate);
            if (is_array($decoded)) {
                return $decoded;
            }

            if (function_exists('mb_convert_encoding')) {
                $fallbackEncodings = $this->resolveSupportedMbConvertEncodings([
                    'UTF-8',
                    'Windows-1250',
                    'ISO-8859-2',
                    'ISO-8859-1',
                ]);

                if ($fallbackEncodings !== []) {
                    try {
                        $convertedCandidate = mb_convert_encoding($candidate, 'UTF-8', $fallbackEncodings);
                    } catch (\ValueError) {
                        $convertedCandidate = null;
                    }

                    if (is_string($convertedCandidate)) {
                        $decoded = $this->decodePossiblyDoubleEncodedJson(trim($convertedCandidate));
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    }
                }
            }
        }

        $errorMessage = json_last_error_msg();
        $bodyFragment = Str::limit($body, 800);

        throw new HodowlaApiException(
            "Nie udalo sie zdekodowac odpowiedzi API. json_error={$errorMessage}; body={$bodyFragment}"
        );
    }

    protected function decodePossiblyDoubleEncodedJson(string $body): mixed
    {
        $decoded = json_decode($body, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (json_last_error() === JSON_ERROR_NONE && is_string($decoded) && $this->looksLikeJson($decoded)) {
            $decoded = json_decode($decoded, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return $decoded;
    }

    protected function extractJsonCandidate(string $body): ?string
    {
        $firstObjectStart = strpos($body, '{');
        $firstArrayStart = strpos($body, '[');

        $startCandidates = array_filter([$firstObjectStart, $firstArrayStart], static fn (mixed $v): bool => $v !== false);
        if ($startCandidates === []) {
            return null;
        }

        $start = (int) min($startCandidates);
        $lastObjectEnd = strrpos($body, '}');
        $lastArrayEnd = strrpos($body, ']');
        $endCandidates = array_filter([$lastObjectEnd, $lastArrayEnd], static fn (mixed $v): bool => $v !== false);
        if ($endCandidates === []) {
            return null;
        }

        $end = (int) max($endCandidates);
        if ($end <= $start) {
            return null;
        }

        return trim((string) substr($body, $start, $end - $start + 1));
    }

    protected function looksLikeJson(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        return (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))
            || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'));
    }

    protected function normalizePayloadKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalizedKey = is_string($key)
                ? preg_replace('/\s+/', '_', trim($key))
                : $key;

            $normalized[$normalizedKey] = $this->normalizePayloadKeys($item);
        }

        return $normalized;
    }

    protected function validateMinimalPayloadStructure(array $payload): ?string
    {
        if (! is_array(Arr::get($payload, 'animal'))) {
            return 'payload.animal must be an object/array';
        }

        foreach (['genetics', 'feedings', 'weights', 'sheds', 'litters', 'gallery'] as $key) {
            if (! array_key_exists($key, $payload)) {
                return "missing payload.{$key}";
            }

            if (! is_array($payload[$key])) {
                return "payload.{$key} must be an array";
            }
        }

        return null;
    }

    protected function throwInvalidSuccessPayload(
        string $url,
        string $secretTag,
        Response $response,
        string $failedCondition,
        string $jsonError = '',
        string $details = '',
    ): never {
        $bodyFragment = Str::limit($response->body(), 800);
        $jsonError = $jsonError !== '' ? $jsonError : json_last_error_msg();

        Log::warning('Hodowla API invalid success payload', [
            'url' => $url,
            'secret_tag' => $secretTag,
            'status' => $response->status(),
            'failed_condition' => $failedCondition,
            'json_last_error' => $jsonError,
            'details' => $details,
            'body' => Str::limit($response->body(), 800),
        ]);

        throw new HodowlaApiException(
            "API zwrocilo nieprawidlowy format danych. warunek={$failedCondition}; url={$url}; secret_tag={$secretTag}; status={$response->status()}; json_error={$jsonError}; body={$bodyFragment}"
        );
    }

    protected function resolveSupportedMbConvertEncodings(array $preferredEncodings): array
    {
        if (! function_exists('mb_list_encodings')) {
            return $preferredEncodings;
        }

        $supportedMap = [];
        foreach (mb_list_encodings() as $encoding) {
            $supportedMap[$this->normalizeEncodingName($encoding)] = $encoding;
        }

        $resolved = [];
        foreach ($preferredEncodings as $encoding) {
            $normalized = $this->normalizeEncodingName($encoding);
            if (isset($supportedMap[$normalized])) {
                $resolved[] = $supportedMap[$normalized];
            }
        }

        return array_values(array_unique($resolved));
    }

    protected function sanitizeAnimalTextFields(array $payload): array
    {
        if (! isset($payload['animal']) || ! is_array($payload['animal'])) {
            return $payload;
        }

        foreach (['name', 'second_name'] as $field) {
            $value = Arr::get($payload, "animal.{$field}");
            if (is_string($value)) {
                Arr::set($payload, "animal.{$field}", trim(strip_tags($value)));
            }
        }

        return $payload;
    }

    protected function normalizeEncodingName(string $encoding): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $encoding));
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
