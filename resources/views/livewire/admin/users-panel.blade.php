<div class="container-fluid px-0">
    <h1 class="h4 mb-3">Uzytkownicy</h1>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <input type="text" class="form-control" placeholder="Szukaj po nazwie lub emailu..." wire:model.live.debounce.400ms="search">
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nazwa</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Ostatnia aktywnosc</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                            <td>
                                @if($user->is_blocked)
                                    <span class="badge text-bg-danger">Zablokowany</span>
                                @else
                                    <span class="badge text-bg-success">Aktywny</span>
                                @endif
                            </td>
                            <td>{{ $user->last_seen_at?->format('Y-m-d H:i') ?: '-' }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" wire:click="startEdit({{ $user->id }})">Edytuj</button>
                                    @if(!$user->hasRole('admin'))
                                        <button class="btn btn-outline-primary" wire:click="startImpersonation({{ $user->id }})">Impersonuj</button>
                                    @endif
                                    @if(auth()->id() !== $user->id)
                                        <button class="btn btn-outline-warning" wire:click="toggleBlock({{ $user->id }})">
                                            {{ $user->is_blocked ? 'Odblokuj' : 'Zablokuj' }}
                                        </button>
                                        <button class="btn btn-outline-danger" wire:click="confirmDelete({{ $user->id }})">Usun konto</button>
                                    @endif
                                    <button class="btn btn-outline-secondary" wire:click="showActivity({{ $user->id }})">Aktywnosc</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Brak uzytkownikow.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>

    @if($editingUserId)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Edycja uzytkownika #{{ $editingUserId }}</strong>
                <button class="btn btn-sm btn-outline-secondary" wire:click="cancelEdit">Zamknij</button>
            </div>
            <div class="card-body">
                <form wire:submit="saveEdit" class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="edit-name">Nazwa</label>
                        <input id="edit-name" type="text" class="form-control @error('editName') is-invalid @enderror" wire:model="editName">
                        @error('editName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="edit-email">Email</label>
                        <input id="edit-email" type="email" class="form-control @error('editEmail') is-invalid @enderror" wire:model="editEmail">
                        @error('editEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="cancelEdit">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($deleteUserId)
        <div class="alert alert-danger mt-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Czy na pewno chcesz usunac konto uzytkownika #{{ $deleteUserId }}? Tej operacji nie da sie cofnac.</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" wire:click="cancelDelete">Anuluj</button>
                <button class="btn btn-sm btn-danger" wire:click="deleteUser">Usun konto</button>
            </div>
        </div>
    @endif

    @if($activityUserId)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Aktywnosc uzytkownika #{{ $activityUserId }}</strong>
                <button class="btn btn-sm btn-outline-secondary" wire:click="clearActivity">Zamknij</button>
            </div>
            <div class="card-body">
                @forelse($activities as $activity)
                    <div class="border-bottom py-2">
                        <div class="small text-muted">{{ $activity->created_at?->format('Y-m-d H:i:s') }}</div>
                        <div>{{ $this->actionLabel($activity->action) }}</div>
                        <div class="small text-muted">
                            Kto: {{ $activity->causer?->name ?? 'system' }} |
                            Dotyczy: {{ $activity->actedAs?->name ?? '-' }}
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Brak zdarzen.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
