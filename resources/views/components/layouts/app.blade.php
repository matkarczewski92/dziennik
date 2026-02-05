<!doctype html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.7">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-body-tertiary">
    @php
        $currentUserName = auth()->user()?->name ?? 'Uzytkowniku';
        $todayLabel = now()->locale('pl')->translatedFormat('j F Y');
    @endphp
    <div class="app-shell">
        <aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarNav" aria-labelledby="sidebarNavLabel">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title" id="sidebarNavLabel">Menu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarNav" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
                <div class="sidebar-content">
                    <div class="sidebar-brand-wrap">
                        <img
                            src="https://makssnake.pl/images/landing/logo_white.png"
                            alt="MaksSnake logo"
                            class="sidebar-logo"
                            width="100"
                            height="100"
                            loading="lazy"
                        >
                        <div class="sidebar-brand">Dziennik hodowlany</div>
                    </div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                        <a class="nav-link {{ request()->routeIs('animals.*') ? 'active' : '' }}" href="{{ route('animals.index') }}">Zwierzeta</a>
                        <div class="sidebar-shortcuts">
                            @forelse(($sidebarAnimals ?? collect()) as $sidebarAnimal)
                                <a
                                    class="nav-link sidebar-shortcut-link {{ request()->routeIs('animals.show') && (int) (request()->route('animal')?->id ?? 0) === $sidebarAnimal->id ? 'active' : '' }}"
                                    href="{{ route('animals.show', $sidebarAnimal->id) }}"
                                >
                                    {{ $sidebarAnimal->name }}
                                </a>
                            @empty
                                <div class="sidebar-shortcuts-empty">Brak zwierzat</div>
                            @endforelse
                        </div>
                        <a class="nav-link {{ request()->routeIs('account.settings') ? 'active' : '' }}" href="{{ route('account.settings') }}">Konto</a>
                        <a class="nav-link {{ request()->routeIs('instruction') ? 'active' : '' }}" href="{{ route('instruction') }}">Instrukcja</a>
                        <hr class="my-2 border-secondary-subtle">
                        <a class="nav-link {{ request()->routeIs('offers.current') ? 'active' : '' }}" href="{{ route('offers.current') }}">Aktualna oferta</a>
                        @if($isAdmin ?? false)
                            <div class="nav-separator mt-3 mb-1">Administrator</div>
                            <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">Uzytkownicy</a>
                            <a class="nav-link {{ request()->routeIs('admin.system-config') ? 'active' : '' }}" href="{{ route('admin.system-config') }}">Konfiguracja</a>
                        @endif
                    </nav>
                </div>
            </div>
        </aside>

        <main class="app-main">
            <header class="app-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button
                        class="btn btn-outline-secondary btn-sm d-lg-none"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#sidebarNav"
                        aria-controls="sidebarNav"
                    >
                        Menu
                    </button>
                    <strong>Witaj, {{ $currentUserName }}, dzis jest {{ $todayLabel }}.</strong>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Wyloguj</button>
                </form>
            </header>

            @if($isImpersonating ?? false)
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <span>Tryb impersonacji: dzialasz jako {{ auth()->user()?->name }}.</span>
                    <form method="POST" action="{{ route('impersonation.leave') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">Wroc do admina</button>
                    </form>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <x-cookie-consent-banner />

    @livewireScripts
</body>
</html>

