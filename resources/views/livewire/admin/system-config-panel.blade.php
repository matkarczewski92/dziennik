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
                    <textarea id="global-message-input" class="d-none @error('globalMessage') is-invalid @enderror" rows="5" wire:model="globalMessage">{{ $globalMessage }}</textarea>
                    <div class="border rounded p-2 mb-2 d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.globalMessageEditor?.exec('bold')"><strong>B</strong></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.globalMessageEditor?.exec('justifyCenter')">Wycentruj</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.globalMessageEditor?.createLink()">Link</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.globalMessageEditor?.insertImage()">Grafika</button>
                    </div>
                    <div
                        id="global-message-editor"
                        class="form-control bg-body min-vh-25"
                        style="min-height: 220px; overflow-y: auto;"
                        contenteditable="true"
                        wire:ignore
                    ></div>
                    <div class="form-text">Dostepne formatowanie: pogrubienie, wycentrowanie, linki, grafiki (URL).</div>
                    @error('globalMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Zapisz konfiguracje</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const boot = () => {
            const input = document.getElementById('global-message-input');
            const editor = document.getElementById('global-message-editor');
            if (!input || !editor || editor.dataset.bound === '1') {
                return;
            }

            editor.dataset.bound = '1';
            const pullFromInput = () => {
                editor.innerHTML = input.value || '';
            };
            pullFromInput();

            const sync = () => {
                input.value = editor.innerHTML;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            };

            editor.addEventListener('input', sync);
            editor.addEventListener('blur', sync);

            window.globalMessageEditor = {
                exec(command) {
                    editor.focus();
                    document.execCommand(command, false, null);
                    sync();
                },
                createLink() {
                    const href = prompt('Podaj adres URL linku (https://...)');
                    if (!href) return;
                    editor.focus();
                    document.execCommand('createLink', false, href);
                    sync();
                },
                insertImage() {
                    const src = prompt('Podaj adres URL grafiki (https://...)');
                    if (!src) return;
                    editor.focus();
                    document.execCommand('insertImage', false, src);
                    sync();
                },
            };

            document.querySelector('form[wire\\:submit="save"]')?.addEventListener('submit', sync);

            // Po hydracji Livewire input moze dostac wartosc z backendu chwilke pozniej.
            setTimeout(pullFromInput, 0);
            setTimeout(pullFromInput, 100);
        };

        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', boot);
        document.addEventListener('livewire:initialized', boot);
    })();
</script>
