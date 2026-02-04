<?php

namespace App\Services;

use App\Exceptions\HodowlaApiException;
use Carbon\Carbon;
use JsonException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CurrentOffersApiService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchCurrentOffers(): array
    {
        $url = $this->resolveUrl();

        $response = Http::acceptJson()
            ->withOptions([
                'decode_content' => true,
                'curl' => $this->curlCompressionOptions(),
            ])
            ->timeout((int) config('hodowla.timeout', 10))
            ->get($url);

        $status = $response->status();
        $contentType = (string) ($response->header('Content-Type') ?? '');
        $rawBody = (string) $response->body();
        $this->logBodyDiagnostics($url, $status, $contentType, $rawBody);

        if (! $response->successful()) {
            $this->logApiError($url, $status, $contentType, $rawBody);
            throw new HodowlaApiException(
                "Nie udalo sie pobrac aktualnej oferty. status={$status}; content_type={$contentType}"
            );
        }

        try {
            $decoded = $this->decodeJsonBody($rawBody);
        } catch (JsonException $exception) {
            $this->logApiError($url, $status, $contentType, $rawBody);
            $hexPrefix = $this->hexPrefix($rawBody, 16);
            $preview = Str::limit($this->bodyPreview($rawBody), 300, '');
            throw new HodowlaApiException(
                "API aktualnej oferty zwrocilo nieprawidlowy JSON. json_error={$exception->getMessage()}; status={$status}; content_type={$contentType}; hex_prefix={$hexPrefix}; preview={$preview}"
            );
        }

        $payload = Arr::get($decoded, 'data', $decoded);
        if (! is_array($payload) || ! array_is_list($payload)) {
            $this->logApiError($url, $status, $contentType, $rawBody);
            throw new HodowlaApiException(
                "API aktualnej oferty zwrocilo nieprawidlowy format danych. Oczekiwano listy ofert; status={$status}; content_type={$contentType}"
            );
        }

        $offers = [];
        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $offers[] = $this->normalizeUtf8Deep($this->mapOffer($row));
        }

        try {
            $encoded = json_encode($offers, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
            $decoded = json_decode((string) $encoded, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable $exception) {
            Log::warning('Current offer normalization fallback triggered', [
                'url' => $url,
                'status' => $status,
                'content_type' => $contentType,
                'error' => $exception->getMessage(),
            ]);
        }

        return $offers;
    }

    /**
     * @return array<string, mixed>|array<int, mixed>
     * @throws JsonException
     */
    protected function decodeJsonBody(string $body): array
    {
        $body = $this->normalizeBody($body);

        $decoded = json_decode($body, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (is_array($decoded)) {
            return $decoded;
        }

        throw new JsonException(json_last_error_msg());
    }

    protected function logApiError(string $url, int $status, string $contentType, string $body): void
    {
        Log::warning('Current offer API decode error', [
            'url' => $url,
            'status' => $status,
            'content_type' => $contentType,
            'body' => Str::limit($body, 800, ''),
        ]);
    }

    protected function logBodyDiagnostics(string $url, int $status, string $contentType, string $body): void
    {
        Log::info('Current offer API raw body diagnostics', [
            'url' => $url,
            'status' => $status,
            'content_type' => $contentType,
            'length' => strlen($body),
            'hex_prefix' => $this->hexPrefix($body, 16),
            'preview' => Str::limit($this->bodyPreview($body), 300, ''),
        ]);
    }

    protected function curlCompressionOptions(): array
    {
        if (! defined('CURLOPT_ENCODING')) {
            return [];
        }

        return [CURLOPT_ENCODING => ''];
    }

    protected function normalizeBody(string $body): string
    {
        if (str_starts_with($body, "\x1F\x8B")) {
            $decoded = @gzdecode($body);
            if (is_string($decoded)) {
                $body = $decoded;
            }
        }

        while (str_starts_with($body, "\xEF\xBB\xBF")) {
            $body = substr($body, 3);
        }

        while (str_starts_with($body, "\xFF\xFE") || str_starts_with($body, "\xFE\xFF")) {
            $body = substr($body, 2);
        }

        $body = trim($body);

        if ($body !== '' && ! $this->isValidUtf8($body)) {
            $converted = $this->convertToUtf8($body);
            if (is_string($converted) && $converted !== '') {
                $body = $converted;
            }
        }

        $clean = @preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{A0}-\x{10FFFF}]/u', '', $body);
        if (is_string($clean) && $clean !== '') {
            $body = $clean;
        }

        return trim($body);
    }

    protected function hexPrefix(string $body, int $bytes): string
    {
        $prefix = substr($body, 0, $bytes);
        $hex = strtoupper(bin2hex($prefix));

        return trim((string) implode(' ', str_split($hex, 2)));
    }

    protected function bodyPreview(string $body): string
    {
        $preview = substr($body, 0, 300);
        $preview = str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], $preview);

        return trim($preview);
    }

    protected function resolveUrl(): string
    {
        $baseUrl = trim((string) config('hodowla.base_url', ''));
        if ($baseUrl === '') {
            $baseUrl = 'https://makssnake.pl/api';
        }

        return rtrim($baseUrl, '/').'/offers/current';
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function mapOffer(array $row): array
    {
        $typeId = $this->toInt(Arr::get($row, 'type_id'));
        $typeName = $this->sanitizeText((string) Arr::get($row, 'type_name', ''));

        if ($typeId === null) {
            $typeId = $this->toInt(Arr::get($row, 'type.id'));
        }

        if ($typeName === '') {
            $typeName = $this->sanitizeText((string) Arr::get($row, 'type.name', ''));
        }

        if ($typeName === '') {
            $typeName = 'Pozostale';
        }

        $mainPhotoUrl = trim((string) Arr::get($row, 'main_photo_url', ''));
        if ($mainPhotoUrl !== '' && ! filter_var($mainPhotoUrl, FILTER_VALIDATE_URL)) {
            $mainPhotoUrl = '';
        }

        $profileUrl = trim((string) Arr::get($row, 'public_profile_url', ''));
        if ($profileUrl !== '' && ! filter_var($profileUrl, FILTER_VALIDATE_URL)) {
            $profileUrl = '';
        }

        return [
            'offer_id' => $this->toInt(Arr::get($row, 'offer_id')),
            'animal_id' => $this->toInt(Arr::get($row, 'animal_id')),
            'name' => $this->sanitizeText((string) Arr::get($row, 'name', 'Bez nazwy')),
            'sex' => $this->toInt(Arr::get($row, 'sex')),
            'sex_label' => $this->resolveSexLabel(
                Arr::get($row, 'sex_label'),
                Arr::get($row, 'sex'),
            ),
            'price' => $this->toFloat(Arr::get($row, 'price')),
            'has_reservation' => (bool) Arr::get($row, 'has_reservation', false),
            'date_of_birth' => $this->normalizeDate((string) Arr::get($row, 'date_of_birth', '')),
            'main_photo_url' => $mainPhotoUrl !== '' ? $mainPhotoUrl : null,
            'public_profile_url' => $profileUrl !== '' ? $profileUrl : null,
            'type_id' => $typeId,
            'type_name' => $typeName,
        ];
    }

    protected function sanitizeText(string $value): string
    {
        $value = trim(strip_tags($value));

        if ($value === '') {
            return $value;
        }

        if (! $this->isValidUtf8($value)) {
            $converted = $this->convertToUtf8($value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        if (! $this->isValidUtf8($value) && function_exists('iconv')) {
            $iconvUtf8 = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($iconvUtf8) && $iconvUtf8 !== '') {
                $value = $iconvUtf8;
            }
        }

        $clean = @preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{A0}-\x{10FFFF}]/u', '', $value);
        if (! is_string($clean)) {
            $clean = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value);
        }

        return trim((string) ($clean ?? ''));
    }

    protected function normalizeUtf8Deep(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeUtf8Deep($item);
            }

            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        return $this->sanitizeText($value);
    }

    protected function normalizeDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveSexLabel(mixed $sexLabel, mixed $sex): string
    {
        $label = $this->sanitizeText((string) $sexLabel);
        if ($label !== '') {
            return Str::lower($label);
        }

        $sexValue = $this->toInt($sex);

        return match ($sexValue) {
            2 => 'samiec',
            3 => 'samica',
            default => 'nieznana',
        };
    }

    protected function toInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    protected function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    protected function isValidUtf8(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        if (function_exists('mb_check_encoding')) {
            return mb_check_encoding($value, 'UTF-8');
        }

        return preg_match('//u', $value) === 1;
    }

    protected function convertToUtf8(string $value): ?string
    {
        $candidates = ['UTF-8', 'Windows-1250', 'CP1250', 'ISO-8859-2', 'ISO-8859-1'];

        if (function_exists('mb_list_encodings')) {
            $supported = array_map('strtoupper', mb_list_encodings());
            $candidates = array_values(array_filter(
                $candidates,
                static fn (string $encoding): bool => in_array(strtoupper($encoding), $supported, true)
            ));
        }

        if ($candidates !== [] && function_exists('mb_convert_encoding')) {
            try {
                $converted = mb_convert_encoding($value, 'UTF-8', implode(', ', $candidates));
                if (is_string($converted) && $converted !== '') {
                    return $converted;
                }
            } catch (\Throwable) {
                // Fallback below.
            }
        }

        if (function_exists('iconv')) {
            foreach (['Windows-1250', 'CP1250', 'ISO-8859-2', 'ISO-8859-1'] as $encoding) {
                $converted = @iconv($encoding, 'UTF-8//IGNORE', $value);
                if (is_string($converted) && $converted !== '') {
                    return $converted;
                }
            }
        }

        return null;
    }
}
