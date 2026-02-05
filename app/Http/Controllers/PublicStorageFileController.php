<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PublicStorageFileController extends Controller
{
    public function __invoke(string $path): Response
    {
        $normalizedPath = ltrim($path, '/');

        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($normalizedPath)) {
            abort(404);
        }

        $fullPath = $disk->path($normalizedPath);

        if (! is_file($fullPath)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($normalizedPath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
