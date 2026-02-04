<?php

namespace App\Livewire\Offers;

use App\Services\CurrentOffersApiService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CurrentOfferPage extends Component
{
    public bool $readyToLoad = false;

    public ?string $errorMessage = null;

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $offersByType = [];

    public function loadOffers(CurrentOffersApiService $offersApiService): void
    {
        $this->readyToLoad = true;
        $this->errorMessage = null;
        $this->offersByType = [];

        try {
            $offers = collect($offersApiService->fetchCurrentOffers())
                ->map(fn (array $offer): array => $this->sanitizeForLivewire($offer))
                ->values()
                ->all();

            $grouped = collect($offers)
                ->groupBy(static fn (array $offer): string => (string) ($offer['type_name'] ?? 'Pozostale'))
                ->sortKeys()
                ->map(static fn ($group): array => $group->values()->all())
                ->all();

            $grouped = $this->sanitizeForLivewire($grouped);

            if (! $this->isJsonSafe($grouped)) {
                Log::error('Current offers payload is not JSON-safe for Livewire response', [
                    'first_bad_path' => $this->firstInvalidUtf8Path($grouped),
                ]);

                $this->errorMessage = 'Dane oferty zawieraja nieprawidlowe znaki UTF-8.';
                $this->offersByType = [];

                return;
            }

            $this->offersByType = $grouped;
        } catch (\Throwable $exception) {
            $this->errorMessage = $this->normalizeText($exception->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.offers.current-offer-page');
    }

    protected function normalizeText(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($value, 'UTF-8') && function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        $clean = @preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{A0}-\x{10FFFF}]/u', '', $value);

        return is_string($clean) ? $clean : $value;
    }

    protected function sanitizeForLivewire(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalizedKey = is_string($key) ? $this->normalizeText($key) : $key;
                $normalized[$normalizedKey] = $this->sanitizeForLivewire($item);
            }

            return $normalized;
        }

        if (! is_string($value)) {
            return $value;
        }

        if (function_exists('iconv')) {
            $value = (string) (@iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value);
        }

        return $this->normalizeText($value);
    }

    protected function isJsonSafe(mixed $value): bool
    {
        json_encode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function firstInvalidUtf8Path(mixed $value, string $path = '$'): ?string
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $childPath = $path . '[' . (is_int($key) ? $key : "'" . $key . "'") . ']';
                $bad = $this->firstInvalidUtf8Path($item, $childPath);
                if ($bad !== null) {
                    return $bad;
                }
            }

            return null;
        }

        if (is_string($value) && function_exists('mb_check_encoding') && ! mb_check_encoding($value, 'UTF-8')) {
            return $path;
        }

        return null;
    }
}
