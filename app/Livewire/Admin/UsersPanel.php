<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserActivity;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class UsersPanel extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $activityUserId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    public function toggleBlock(int $id, ActivityLogger $activityLogger): void
    {
        $admin = auth()->user();
        abort_unless($admin && $admin->hasRole('admin'), 403);

        $user = User::query()->findOrFail($id);
        if ($user->id === $admin->id) {
            return;
        }

        $user->forceFill([
            'is_blocked' => ! $user->is_blocked,
            'blocked_at' => $user->is_blocked ? null : now(),
            'blocked_reason' => $user->is_blocked ? null : 'Zablokowano przez administratora.',
        ])->save();

        $activityLogger->log(
            $user->is_blocked ? 'admin.user.block' : 'admin.user.unblock',
            $admin,
            $admin,
            $user,
        );

        session()->flash('success', $user->is_blocked ? 'Uzytkownik zostal zablokowany.' : 'Uzytkownik zostal odblokowany.');
    }

    public function startImpersonation(int $id, ActivityLogger $activityLogger)
    {
        $admin = auth()->user();
        abort_unless($admin && $admin->hasRole('admin'), 403);

        $target = User::query()->findOrFail($id);
        if ($target->hasRole('admin')) {
            session()->flash('error', 'Nie mozna impersonowac konta administratora.');
            return;
        }

        session()->put('impersonator_id', $admin->id);
        Auth::login($target);
        session()->regenerate();

        $activityLogger->log('admin.impersonation.start', $admin, $target, $target);

        return $this->redirectRoute('dashboard', navigate: true);
    }

    public function showActivity(int $id): void
    {
        $this->activityUserId = $id;
    }

    public function clearActivity(): void
    {
        $this->activityUserId = null;
    }

    public function render()
    {
        $activities = collect();
        if ($this->activityUserId) {
            $activities = UserActivity::query()
                ->where('acted_as_id', $this->activityUserId)
                ->orWhere('causer_id', $this->activityUserId)
                ->latest()
                ->limit(25)
                ->get();
        }

        return view('livewire.admin.users-panel', [
            'users' => $this->users,
            'activities' => $activities,
        ]);
    }
}
