<div class="container-fluid px-0">
    <h1 class="h4 mb-3">Konfiguracja systemu</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form wire:submit="save" class="vstack gap-3">
                <div>
                    <label class="form-label">Token API hodowli (system_config: apiDziennik)</label>
                    <textarea class="form-control @error('apiToken') is-invalid @enderror" rows="3" wire:model="apiToken"></textarea>
                    @error('apiToken') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="form-label">Komunikat od hodowcy (globalny)</label>
                    <textarea class="form-control @error('globalMessage') is-invalid @enderror" rows="5" wire:model="globalMessage"></textarea>
                    @error('globalMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Zapisz konfiguracje</button>
                </div>
            </form>
        </div>
    </div>
</div>
