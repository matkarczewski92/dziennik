<?php

namespace App\Livewire\Account;

use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Settings extends Component
{
    public string $name = '';

    public array $passwordForm = [];

    public bool $showDeleteModal = false;

    public string $deleteAccountPassword = '';

    public function mount(): void
    {
        $this->name = (string) auth()->user()?->name;
        $this->resetPasswordForm();
    }

    public function saveName(ActivityLogger $activityLogger): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = auth()->user();
        abort_unless($user, 403);

        if ($user->name === $data['name']) {
            session()->flash('success', 'Nazwa konta jest juz aktualna.');
            $this->redirectRoute('account.settings', navigate: false);
            return;
        }

        $user->forceFill(['name' => $data['name']])->save();
        $activityLogger->log('account.name.update', $user, $user, $user);

        session()->flash('success', 'Nazwa konta zostala zaktualizowana.');
        $this->redirectRoute('account.settings', navigate: false);
    }

    public function updatePassword(ActivityLogger $activityLogger): void
    {
        $data = $this->validate([
            'passwordForm.current_password' => ['required', 'current_password'],
            'passwordForm.password' => ['required', 'confirmed', Password::min(8)],
            'passwordForm.password_confirmation' => ['required'],
        ]);

        $user = auth()->user();
        abort_unless($user, 403);

        $newPassword = (string) $data['passwordForm']['password'];
        $user->forceFill(['password' => $newPassword])->save();

        Auth::logoutOtherDevices($newPassword);
        $activityLogger->log('account.password.update', $user, $user, $user);

        $this->resetPasswordForm();
        session()->flash('success', 'Haslo zostalo zmienione.');
        $this->redirectRoute('account.settings', navigate: false);
    }

    public function openDeleteModal(): void
    {
        $this->resetValidation();
        $this->deleteAccountPassword = '';
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->deleteAccountPassword = '';
        $this->showDeleteModal = false;
    }

    public function deleteAccount(ActivityLogger $activityLogger): void
    {
        $this->validate([
            'deleteAccountPassword' => ['required', 'current_password'],
        ]);

        $user = auth()->user();
        abort_unless($user, 403);

        $activityLogger->log('account.delete', $user, $user, $user);
        Auth::logout();
        $user->delete();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        $this->redirectRoute('login', navigate: true);
    }

    protected function resetPasswordForm(): void
    {
        $this->passwordForm = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function render()
    {
        return view('livewire.account.settings');
    }
}
