<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class NoteService
{
    public function create(User $user, Animal $animal, array $data): Note
    {
        $this->ensureOwnership($user, $animal);

        return Note::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'body' => $data['body'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
        ]);
    }

    public function delete(User $user, Note $note): void
    {
        if ((int) $note->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $note->delete();
    }

    public function update(User $user, Note $note, array $data): Note
    {
        if ((int) $note->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $note->update([
            'body' => $data['body'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
        ]);

        return $note->refresh();
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}
