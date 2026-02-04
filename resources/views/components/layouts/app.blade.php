<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-body-tertiary">
    <div class="app-shell">
        <aside class="app-sidebar">
            <div class="sidebar-brand">{{ config('app.name') }}</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="nav-link {{ request()->routeIs('animals.*') ? 'active' : '' }}" href="{{ route('animals.index') }}">Zwierzeta</a>
                @if($isAdmin ?? false)
                    <div class="nav-separator mt-3 mb-1">Administrator</div>
                    <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">Uzytkownicy</a>
                    <a class="nav-link {{ request()->routeIs('admin.system-config') ? 'active' : '' }}" href="{{ route('admin.system-config') }}">Konfiguracja</a>
                @endif
            </nav>
        </aside>

        <main class="app-main">
            <header class="app-topbar">
                <div class="d-flex align-items-center gap-2">
                    <strong>{{ auth()->user()?->name }}</strong>
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

    @livewireScripts
</body>
</html>
