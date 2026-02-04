<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AnimalSidebarDetails extends Component
{
    use AuthorizesRequests;

    public int $animalId;

    public array $identity = [];

    public bool $showNoteEditModal = false;

    public string $noteText = '';

    public function sexLabel(): string
    {
        return match ((string) ($this->identity['sex'] ?? 'unknown')) {
            'male' => 'Samiec',
            'female' => 'Samica',
            default => 'Nieznana',
        };
    }

    public function openNoteEditModal(): void
    {
        $animal = $this->animal();
        $this->authorize('update', $animal);
        $this->noteText = $this->normalizeUtf8((string) ($animal->notes ?? ''));
        $this->showNoteEditModal = true;
    }

    public function closeNoteEditModal(): void
    {
        $this->showNoteEditModal = false;
        $this->resetValidation();
    }

    public function saveNote(): void
    {
        $animal = $this->animal();
        $this->authorize('update', $animal);

        $validated = $this->validate([
            'noteText' => ['nullable', 'string'],
        ]);

        $normalizedNote = $this->normalizeUtf8((string) ($validated['noteText'] ?? ''));

        $animal->update([
            'notes' => trim($normalizedNote) !== '' ? $normalizedNote : null,
        ]);

        $this->identity['notes'] = trim($normalizedNote) !== '' ? $normalizedNote : null;
        $this->noteText = trim($normalizedNote);
        $this->showNoteEditModal = false;
        session()->flash('success', 'Notatka ogolna zostala zaktualizowana.');
    }

    protected function animal(): Animal
    {
        return Animal::query()
            ->ownedBy(auth()->id())
            ->findOrFail($this->animalId);
    }

    public function render()
    {
        return view('livewire.animals.animal-sidebar-details');
    }

    protected function normalizeUtf8(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($converted)) {
                return trim($converted);
            }
        }

        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value) ?: '';
    }
}
