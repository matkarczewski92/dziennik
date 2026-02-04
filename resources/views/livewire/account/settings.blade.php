<div class="container-fluid px-0">
    <h1 class="h4 mb-3">Ustawienia konta</h1>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Zmiana nazwy</h2>

                    <form wire:submit="saveName" class="d-grid gap-3">
                        <div>
                            <label class="form-label" for="account-name">Nazwa</label>
                            <input
                                id="account-name"
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                wire:model.live.debounce.300ms="name"
                                maxlength="255"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveName">
                                Zapisz nazwe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Zmiana hasla</h2>

                    <form wire:submit="updatePassword" class="d-grid gap-3">
                        <div>
                            <label class="form-label" for="current-password">Aktualne haslo</label>
                            <input
                                id="current-password"
                                type="password"
                                class="form-control @error('passwordForm.current_password') is-invalid @enderror"
                                wire:model.defer="passwordForm.current_password"
                            >
                            @error('passwordForm.current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="new-password">Nowe haslo</label>
                            <input
                                id="new-password"
                                type="password"
                                class="form-control @error('passwordForm.password') is-invalid @enderror"
                                wire:model.defer="passwordForm.password"
                            >
                            @error('passwordForm.password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="new-password-confirmation">Powtorz nowe haslo</label>
                            <input
                                id="new-password-confirmation"
                                type="password"
                                class="form-control @error('passwordForm.password_confirmation') is-invalid @enderror"
                                wire:model.defer="passwordForm.password_confirmation"
                            >
                            @error('passwordForm.password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updatePassword">
                                Zmien haslo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-danger-subtle shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-danger">Usuniecie konta</h2>
                    <p class="text-muted mb-3">
                        Usuniecie konta jest nieodwracalne. Wszystkie wprowadzone dane (zwierzeta, karmienia,
                        wazenia, wylinki, notatki i zdjecia) zostana trwale usuniete.
                    </p>
                    <button type="button" class="btn btn-outline-danger" wire:click="openDeleteModal">
                        Usun konto
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($showDeleteModal)
        <div class="livewire-modal-backdrop">
            <div class="livewire-modal">
                <h2 class="h5 mb-2">Czy na pewno usunac konto?</h2>
                <p class="text-muted mb-3">
                    Tej operacji nie da sie cofnac. Po potwierdzeniu wszystkie Twoje dane zostana usuniete.
                </p>

                <form wire:submit="deleteAccount" class="vstack gap-2">
                    <div>
                        <label class="form-label" for="delete-account-password">Podaj aktualne haslo</label>
                        <input
                            id="delete-account-password"
                            type="password"
                            class="form-control @error('deleteAccountPassword') is-invalid @enderror"
                            wire:model.defer="deleteAccountPassword"
                        >
                        @error('deleteAccountPassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeDeleteModal">
                            Anuluj
                        </button>
                        <button type="submit" class="btn btn-danger" wire:loading.attr="disabled" wire:target="deleteAccount">
                            Tak, usun konto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
