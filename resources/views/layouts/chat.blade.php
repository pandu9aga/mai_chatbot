<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'MAI Chatbot') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            [x-cloak] { display: none !important; }
            .prose pre { margin: 0; }
            .prose code { font-size: 0.875em; }
            .prose pre code { font-size: 0.8em; }
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #4a5568; border-radius: 3px; }
            ::-webkit-scrollbar-thumb:hover { background: #718096; }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-900 text-gray-100 overflow-hidden">
        {{ $slot }}
        @stack('scripts')
        @livewireScripts
    </body>
</html>
