<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>SMP Negeri 2 Gedangan — {{ config('app.name', 'Sistem Absensi') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:600,700,800|inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root{
                --font-display:'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
                --font-body:'Inter', ui-sans-serif, system-ui, sans-serif;
                --brand-green:#0e7c3f;
                --brand-green-dark:#0b6633;
                --brand-green-darker:#063b1f;
                --brand-yellow:#ffcc00;
                --brand-red:#e11d48;
                --brand-red-dark:#be123c;
                --brand-blue:#0ea5e9;
            }
            body{ font-family: var(--font-body); }
            h1,h2,h3{ font-family: var(--font-display); letter-spacing:-0.01em; }
            .brand-bg{ background-color: var(--brand-green); }
            .brand-bg-hover:hover{ background-color: var(--brand-green-dark); }
            .brand-text{ color: var(--brand-green-dark); }
            .brand-ring:focus{ border-color: var(--brand-green) !important; box-shadow: 0 0 0 3px rgba(14,124,63,.15); outline: none; }
            .brand-guest-bg{ background: linear-gradient(160deg, #eafaf0 0%, #f8f9fa 45%, #fffceb 100%); }
            .brand-topstripe{ background: linear-gradient(90deg, var(--brand-yellow) 0%, var(--brand-yellow) 45%, var(--brand-blue) 45%, var(--brand-blue) 72%, var(--brand-green) 72%, var(--brand-green) 100%); }
        </style>
    </head>
    <body class="text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 brand-guest-bg">
            <div class="flex flex-col items-center px-6 text-center">
                <div class="w-20 h-20 rounded-2xl bg-white shadow-sm border border-slate-100 p-2 flex items-center justify-center" style="overflow:hidden">
                    <img src="{{ asset('images/logo-smp.webp') }}" alt="Logo SMP Negeri 2 Gedangan" class="w-full h-full" style="object-fit:contain; display:block">
                </div>
                <h1 class="mt-4 text-lg font-extrabold text-slate-800 leading-tight">SMP Negeri 2 Gedangan</h1>
                <p class="text-sm text-slate-500">Sistem Absensi Siswa</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 bg-white shadow-md overflow-hidden sm:rounded-xl border border-slate-100">
                <div class="h-1.5 brand-topstripe"></div>
                <div class="px-6 py-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
