<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class Overview extends Component
{
    public array $feedingToday = [];

    public array $feedingTomorrow = [];

    public array $weightReminders = [];

    public ?string $globalMessage = null;

    public function mount(DashboardService $dashboardService): void
    {
        $this->refreshData($dashboardService);
    }

    public function refreshData(DashboardService $dashboardService): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $feeding = $dashboardService->feedingReminders($user);
        $this->feedingToday = $feeding['today']->all();
        $this->feedingTomorrow = $feeding['tomorrow']->all();
        $this->weightReminders = $dashboardService->weightReminders($user)->all();
        $this->globalMessage = $dashboardService->globalMessage();
    }

    public function render()
    {
        return view('livewire.dashboard.overview');
    }
}
