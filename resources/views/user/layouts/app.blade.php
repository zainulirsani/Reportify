<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        
        @include('user.layouts.partials.sidebar')

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">

            @include('user.layouts.partials.navbar')
            
            <main class="flex-1 p-6">
                
                {{ $slot }}

            </main>

            @include('user.layouts.partials.footer')
        </div>
    </div>
    @stack('scripts')
</body>
</html>