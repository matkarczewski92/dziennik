<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserActivity;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class UsersPanel extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $activityUserId = null;

    public ?int $editingUserId = null;

    public string $editName = '';

    public string $editEmail = '';

    public ?int $deleteUserId = null;

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

    public function startEdit(int $id): void
    {
        $admin = auth()->user();
        abort_unless($admin && $admin->hasRole('admin'), 403);

        $user = User::query()->findOrFail($id);
        $this->editingUserId = $user->id;
        $this->editName = (string) $user->name;
        $this->editEmail = (string) $user->email;
    }

    public function cancelEdit(): void
    {
        $this->editingUserId = null;
        $this->editName = '';
        $this->editEmail = '';
    }

    public function saveEdit(ActivityLogger $activityLogger): void
    {
        $admin = auth()->user();
        abort_unless($admin && $admin->hasRole('admin'), 403);
        abort_if(! $this->editingUserId, 404);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
        ], [
            'editName.required' => 'Podaj nazwe uzytkownika.',
            'editName.max' => 'Nazwa uzytkownika moze miec maksymalnie :max znakow.',
            'editEmail.required' => 'Podaj adres e-mail.',
            'editEmail.email' => 'Podaj poprawny adres e-mail.',
            'editEmail.max' => 'Adres e-mail moze miec maksymalnie :max znakow.',
            'editEmail.unique' => 'Ten adres e-mail jest juz zajety.',
        ]);

        $user = User::query()->findOrFail($this->editingUserId);
        $oldName = $user->name;
        $oldEmail = $user->email;

        $user->forceFill([
            'name' => trim((string) $validated['editName']),
            'email' => trim((string) $validated['editEmail']),
        ])->save();

        $activityLogger->log('admin.user.update', $admin, $admin, $user, [
            'old_name' => $oldName,
            'new_name' => $user->name,
            'old_email' => $oldEmail,
            'new_email' => $user->email,
        ]);

        $this->cancelEdit();
        session()->flash('success', 'Dane uzytkownika zostaly zaktualizowane.');
    }

    public function confirmDelete(int $id): void
    {
        $admin = auth()->user();
        abort_unless($admin && $admin->hasRole('admin'), 403);

        $this->deleteUserId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deleteUserId = null;
    }

    public function deleteUser(ActivityLogger $activityLogger): void
    {
        $admin = auth()->user();
        abort_unless($admin && $admin->hasRole('admin'), 403);
        abort_if(! $this->deleteUserId, 404);

        $user = User::query()->findOrFail($this->deleteUserId);

        if ($user->id === $admin->id) {
            session()->flash('error', 'Nie mozesz usunac wlasnego konta z panelu administratora.');
            return;
        }

        $activityLogger->log('admin.user.delete', $admin, $admin, $user, [
            'deleted_user_name' => $user->name,
            'deleted_user_email' => $user->email,
        ]);

        $user->delete();

        if ($this->activityUserId === $this->deleteUserId) {
            $this->activityUserId = null;
        }

        $this->cancelEdit();
        $this->cancelDelete();
        session()->flash('success', 'Konto uzytkownika zostalo usuniete.');
    }

    public function showActivity(int $id): void
    {
        $this->activityUserId = $id;
    }

    public function clearActivity(): void
    {
        $this->activityUserId = null;
    }

    public function actionLabel(string $action): string
    {
        return match ($action) {
            'auth.register' => 'Rejestracja konta',
            'auth.login' => 'Logowanie',
            'auth.logout' => 'Wylogowanie',
            'account.name.update' => 'Zmiana nazwy konta',
            'account.password.update' => 'Zmiana hasla konta',
            'account.delete' => 'Usuniecie konta',
            'admin.user.block' => 'Administrator zablokowal konto',
            'admin.user.unblock' => 'Administrator odblokowal konto',
            'admin.user.update' => 'Administrator zaktualizowal dane konta',
            'admin.user.delete' => 'Administrator usunal konto',
            'admin.impersonation.start' => 'Administrator rozpoczal impersonacje',
            'admin.impersonation.stop' => 'Administrator zakonczyl impersonacje',
            default => 'Zdarzenie systemowe',
        };
    }

    public function render()
    {
        $activities = collect();
        if ($this->activityUserId) {
            $activities = UserActivity::query()
                ->where('acted_as_id', $this->activityUserId)
                ->orWhere('causer_id', $this->activityUserId)
                ->with(['causer:id,name', 'actedAs:id,name'])
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
