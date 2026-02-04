<?php

namespace App\Livewire\Admin;

use App\Models\SystemConfig;
use Livewire\Component;

class SystemConfigPanel extends Component
{
    public ?string $apiToken = null;

    public ?string $globalMessage = null;

    public function mount(): void
    {
        $this->apiToken = SystemConfig::getValue('apiDziennik');
        $this->globalMessage = SystemConfig::getValue('global_message');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'apiToken' => ['nullable', 'string', 'max:2000'],
            'globalMessage' => ['nullable', 'string', 'max:5000'],
        ]);

        SystemConfig::setValue('apiDziennik', $validated['apiToken'] ?? null);
        SystemConfig::setValue('global_message', $validated['globalMessage'] ?? null);

        session()->flash('success', 'Konfiguracja systemu zostala zapisana.');
    }

    public function render()
    {
        return view('livewire.admin.system-config-panel');
    }
}
