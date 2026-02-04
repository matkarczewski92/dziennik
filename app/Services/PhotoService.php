<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Photo;
use App\Models\User;
use App\Services\Animal\AnimalEventProjector;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class PhotoService
{
    public function __construct(
        protected AnimalEventProjector $eventProjector,
    ) {
    }

    public function store(User $user, Animal $animal, UploadedFile $file, array $data = []): Photo
    {
        $this->ensureOwnership($user, $animal);

        $image = Image::read($file->getRealPath());
        $image->scaleDown(width: 1920, height: 1080);

        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = 'image/jpeg';

        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            $encoded = $image->toJpeg(80);
            $extension = 'jpg';
            $mimeType = 'image/jpeg';
        } elseif ($extension === 'png') {
            $encoded = $image->toPng();
            $mimeType = 'image/png';
        } elseif ($extension === 'webp') {
            $encoded = $image->toWebp(80);
            $mimeType = 'image/webp';
        } else {
            $encoded = $image->toJpeg(80);
            $extension = 'jpg';
            $mimeType = 'image/jpeg';
        }

        $path = "user_storage/{$user->id}/".Str::uuid().".{$extension}";
        Storage::disk('public')->put($path, (string) $encoded);

        $photo = Photo::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'path' => $path,
            'mime_type' => $mimeType,
            'size_kb' => (int) ceil(strlen((string) $encoded) / 1024),
            'taken_at' => $data['taken_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->eventProjector->projectPhoto($photo);

        return $photo;
    }

    public function delete(User $user, Photo $photo): void
    {
        if ((int) $photo->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        if (! Str::startsWith($photo->path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($photo->path);
        }
        $photo->delete();
        $this->eventProjector->removePhoto($photo);
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}
