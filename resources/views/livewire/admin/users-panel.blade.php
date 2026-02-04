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
                                    @if(!$user->hasRole('admin'))
                                        <button class="btn btn-outline-primary" wire:click="startImpersonation({{ $user->id }})">Impersonuj</button>
                                    @endif
                                    @if(auth()->id() !== $user->id)
                                        <button class="btn btn-outline-warning" wire:click="toggleBlock({{ $user->id }})">
                                            {{ $user->is_blocked ? 'Odblokuj' : 'Zablokuj' }}
                                        </button>
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
                        <div><code>{{ $activity->action }}</code></div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Brak zdarzen.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
