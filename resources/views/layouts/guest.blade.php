<!doctype html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-body">
    <div class="guest-card shadow-sm">
        <h1 class="h4 mb-4 text-center">{{ config('app.name') }}</h1>
        {{ $slot }}
    </div>
</body>
</html>
