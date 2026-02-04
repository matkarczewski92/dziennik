@if($paginator->hasPages())
    <nav class="inline-pager mt-3" role="navigation" aria-label="Paginacja">
        @if($paginator->onFirstPage())
            <button type="button" class="btn btn-outline-light btn-sm" disabled>Poprzednie</button>
        @else
            <button
                type="button"
                class="btn btn-outline-light btn-sm"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
            >
                Poprzednie
            </button>
        @endif

        <span class="inline-pager-status">Strona {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if($paginator->hasMorePages())
            <button
                type="button"
                class="btn btn-outline-light btn-sm"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
            >
                Nastepne
            </button>
        @else
            <button type="button" class="btn btn-outline-light btn-sm" disabled>Nastepne</button>
        @endif
    </nav>
@endif

