<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    {{-- Works with `npm run dev` (hot) or `npm run build` (manifest). Plain <link> is more reliable than @vite for CSS-only Blade pages. --}}
    <style>
        .auth-sr-only { display: none !important; }
    </style>
    <link rel="stylesheet" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/css/app.css') }}">
</head>
<body class="h-full font-sans">
    @yield('content')
    @stack('scripts')
</body>
</html>
