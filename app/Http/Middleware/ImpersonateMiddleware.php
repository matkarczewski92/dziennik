<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Animal;
use App\Models\User;
use JsonSerializable;
use Stringable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinutes(2)))) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        $impersonatorId = $request->session()->get('impersonator_id');
        $isImpersonating = $impersonatorId !== null;
        $impersonator = null;
        $isAdmin = $user?->hasRole('admin') ?? false;
        $sidebarAnimals = collect();

        if ($isImpersonating) {
            $impersonator = User::query()->find($impersonatorId);

            if (! $impersonator) {
                $request->session()->forget('impersonator_id');
                $isImpersonating = false;
            }
        }

        if ($user) {
            $animals = Animal::query()
                ->ownedBy($user->id)
                ->select(['id', 'name', 'secret_tag'])
                ->orderBy('name')
                ->get();

            Log::debug('Sidebar UTF-8 diagnostics', [
                'user_id' => $user->id,
                'records' => $animals->take(5)->map(function (Animal $animal): array {
                    $name = (string) ($animal->name ?? '');

                    return [
                        'id' => (int) $animal->id,
                        'is_utf8_name' => $this->isValidUtf8($name),
                        'name_hex_prefix' => $this->hexPrefix($name, 16),
                    ];
                })->all(),
            ]);

            try {
                $rows = [];

                foreach ($animals as $animal) {
                    try {
                        $name = (string) $this->normalizeUtf8((string) ($animal->name ?? ''));
                        $secretTag = (string) $this->normalizeUtf8((string) ($animal->secret_tag ?? ''));

                        if (! $this->isValidUtf8($name)) {
                            throw new \RuntimeException('Normalized name is still not valid UTF-8');
                        }

                        $rows[] = [
                            'id' => (int) $animal->id,
                            'name' => $name,
                            'secret_tag' => $secretTag,
                        ];
                    } catch (\Throwable $recordException) {
                        Log::error('Sidebar record normalization failed', [
                            'user_id' => $user->id,
                            'record_id' => (int) $animal->id,
                            'field' => 'name',
                            'hex_prefix' => $this->hexPrefix((string) ($animal->name ?? ''), 16),
                            'error' => $recordException->getMessage(),
                        ]);
                    }
                }

                $sidebarAnimals = collect($rows)->map(
                    static fn (array $animal): object => (object) [
                        'id' => (int) ($animal['id'] ?? 0),
                        'name' => (string) ($animal['name'] ?? ''),
                        'secret_tag' => (string) ($animal['secret_tag'] ?? ''),
                    ]
                );
            } catch (\Throwable $exception) {
                Log::error('Sidebar animals build failed', [
                    'user_id' => $user->id,
                    'record_id' => null,
                    'field' => 'sidebar',
                    'hex_prefix' => '',
                    'error' => $exception->getMessage(),
                ]);

                $sidebarAnimals = collect();
            }
        }

        View::share('isImpersonating', $isImpersonating);
        View::share('impersonator', $impersonator);
        View::share('isAdmin', $isAdmin);
        View::share('sidebarAnimals', $sidebarAnimals);

        return $next($request);
    }

    protected function normalizeUtf8(mixed $value): mixed
    {
        if ($value instanceof Stringable) {
            return $this->normalizeUtf8String((string) $value);
        }

        if ($value instanceof JsonSerializable) {
            return $this->normalizeUtf8($value->jsonSerialize());
        }

        if ($value instanceof Model) {
            return $this->normalizeUtf8($value->attributesToArray());
        }

        if ($value instanceof Collection) {
            return collect($this->normalizeUtf8($value->all()));
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalizedKey = is_string($key) ? $this->normalizeUtf8String($key) : $key;
                $normalized[$normalizedKey] = $this->normalizeUtf8($item);
            }

            return $normalized;
        }

        if (is_object($value)) {
            $normalizedObject = new \stdClass();
            foreach (get_object_vars($value) as $key => $item) {
                $normalizedKey = $this->normalizeUtf8String((string) $key);
                $normalizedObject->{$normalizedKey} = $this->normalizeUtf8($item);
            }

            return $normalizedObject;
        }

        if (is_string($value)) {
            return $this->normalizeUtf8String($value);
        }

        return $value;
    }

    protected function normalizeUtf8String(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $normalized = $value;

        if (! $this->isValidUtf8($normalized) && function_exists('iconv')) {
            $iconvNormalized = @iconv('UTF-8', 'UTF-8//IGNORE', $normalized);
            if (is_string($iconvNormalized)) {
                $normalized = $iconvNormalized;
            }
        }

        if (($normalized === '' || ! $this->isValidUtf8($normalized)) && function_exists('mb_convert_encoding')) {
            $supportedEncodings = $this->supportedEncodings();

            try {
                $converted = $supportedEncodings !== []
                    ? mb_convert_encoding($value, 'UTF-8', implode(', ', $supportedEncodings))
                    : false;

                if (is_string($converted)) {
                    $normalized = $converted;
                }
            } catch (\Throwable) {
                // Keep fallback below.
            }
        }

        $clean = @preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{A0}-\x{10FFFF}]/u', '', $normalized);
        if (! is_string($clean)) {
            $clean = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $normalized);
            if (! is_string($clean)) {
                $clean = '';
            }
        }

        if (! $this->isValidUtf8($clean) && function_exists('iconv')) {
            $iconvNormalized = @iconv('UTF-8', 'UTF-8//IGNORE', $clean);
            if (is_string($iconvNormalized)) {
                $clean = $iconvNormalized;
            }
        }

        return is_string($clean) ? $clean : '';
    }

    protected function supportedEncodings(): array
    {
        $candidates = ['UTF-8', 'ISO-8859-2', 'Windows-1250', 'CP1250', 'ISO-8859-1'];

        if (! function_exists('mb_list_encodings')) {
            return $candidates;
        }

        $supported = array_map('strtoupper', mb_list_encodings());

        return array_values(array_filter(
            $candidates,
            static fn (string $encoding): bool => in_array(strtoupper($encoding), $supported, true)
        ));
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

    protected function hexPrefix(string $value, int $bytes = 16): string
    {
        $prefix = substr($value, 0, $bytes);

        return strtoupper(trim(implode(' ', str_split(bin2hex($prefix), 2))));
    }
}
