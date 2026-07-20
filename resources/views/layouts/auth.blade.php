<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'GiftCardBot') }} — Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-gray-900 antialiased flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm">
        {{ $slot }}
        <p class="text-center text-[10px] text-gray-400 tracking-widest uppercase mt-8">Powered by Dataynce</p>
    </div>
</body>
</html>
