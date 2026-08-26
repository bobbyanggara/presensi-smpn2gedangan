<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title><?php echo $title ?? 'Sistem Presensi'; ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:500,600,700,800|inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        /* ===== Brand palette — SMP Negeri 2 Gedangan (kuning-hijau + aksen hidup) ===== */
        :root{
            --font-display:'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            --font-body:'Inter', ui-sans-serif, system-ui, sans-serif;
            --brand-green:#0e7c3f;
            --brand-green-dark:#0b6633;
            --brand-green-darker:#063b1f;
            --brand-green-light:#e9f7ee;
            --brand-yellow:#ffcc00;
            --brand-yellow-dark:#e6b800;
            --brand-red:#e11d48;
            --brand-red-dark:#be123c;
            --brand-red-light:#fff1f2;
            --brand-blue:#0ea5e9;
            --brand-purple:#8b5cf6;
            --brand-orange:#f97316;
        }
        html{ font-family: var(--font-body); }
        body{ font-family: var(--font-body); }
        h1,h2,h3,.font-display{ font-family: var(--font-display); letter-spacing:-0.01em; }
        .brand-sidebar{ background-image: linear-gradient(180deg, var(--brand-green) 0%, var(--brand-green-darker) 100%); }
        .brand-topstripe{ background: linear-gradient(90deg, var(--brand-yellow) 0%, var(--brand-yellow) 45%, var(--brand-blue) 45%, var(--brand-blue) 72%, var(--brand-green) 72%, var(--brand-green) 100%); }
        .brand-bg{ background-color: var(--brand-green); }
        .brand-bg-hover:hover{ background-color: var(--brand-green-dark); }
        .brand-text{ color: var(--brand-green-dark); }
        .brand-text-hover:hover{ color: var(--brand-green-dark); }
        .brand-border{ border-color: var(--brand-green) !important; }
        .brand-ring:focus{ border-color: var(--brand-green) !important; box-shadow: 0 0 0 3px rgba(14,124,63,.12); }
        .brand-active-link{ background:#fff; color: var(--brand-green-dark); box-shadow: 0 1px 2px rgba(0,0,0,.06); }
        .brand-accent-bg{ background-color: var(--brand-yellow); }
        .brand-accent-text{ color:#5c4600; }
        .brand-logo-badge{ background: var(--brand-yellow); }
        .brand-bg-soft{ background-color: rgba(14,124,63,.1); }
        .brand-red-bg{ background-color: var(--brand-red); }
        .brand-red-bg-hover:hover{ background-color: var(--brand-red-dark); }
        .brand-red-text{ color: var(--brand-red-dark); }
        a.brand-text-hover:hover, a.brand-text-hover:hover *{ color: var(--brand-green-dark) !important; }
        .sidebar-sub-link{ display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; font-size:12.5px; font-weight:500; color:rgba(255,255,255,.55); transition:background-color .15s, color .15s; }
        .sidebar-sub-link:hover{ color:#fff; background-color:rgba(255,255,255,.1); }
        .sidebar-sub-link.active{ color:#fff; background-color:rgba(255,255,255,.15); }
        .sidebar-group-btn{ width:100%; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 12px; border-radius:12px; font-size:14px; font-weight:500; transition:background-color .15s, color .15s; background:none; border:none; cursor:pointer; font-family:inherit; text-align:left; }
        .sidebar-chevron{ width:14px; height:14px; flex-shrink:0; transition:transform .15s; }
        .sidebar-brand-name{ font-family: var(--font-display); font-size:14.5px; font-weight:800; line-height:1.2; color:#fff; }
        /* Kunci ukuran box logo secara eksplisit agar tidak bergantung pada class Tailwind
           yang mungkin belum ter-compile ulang (mencegah logo tampil raksasa/numpuk). */
        .sidebar-logo-box{ width:44px; height:44px; min-width:44px; min-height:44px; border-radius:12px; background:#fff; padding:6px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 1px 2px rgba(0,0,0,.06); overflow:hidden; }
        .sidebar-logo-box img{ width:100%; height:100%; object-fit:contain; display:block; }
    </style>
</head>
<body class="bg-slate-100 antialiased text-slate-800">
<div x-data="{ sidebarOpen: false }" class="flex min-h-screen">

    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 bg-slate-900/50 z-30 lg:hidden" style="display:none"></div>

    <!-- Sidebar -->
    <aside :class="{ 'translate-x-0': sidebarOpen }"
           class="brand-sidebar w-64 text-white flex flex-col fixed top-0 left-0 h-full z-40 transition-transform duration-200 ease-in-out -translate-x-full lg:translate-x-0">
        <div class="p-5 border-b border-white/10 flex items-center gap-3">
            <div class="sidebar-logo-box">
                <img src="{{ asset('images/logo-smp.webp') }}" alt="Logo SMP Negeri 2 Gedangan">
            </div>
            <div class="min-w-0">
                <h1 class="sidebar-brand-name">SMP Negeri 2<br>Gedangan</h1>
                <p class="text-[11px] text-white/60 mt-1">Manajemen Kehadiran Siswa</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
            <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-white/35">Menu</p>

            <a href="{{ route('dashboard') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'brand-active-link' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'brand-text' : 'text-white/60 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                Dashboard
            </a>
            <a href="{{ route('absensi.rekap') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('absensi.rekap') ? 'brand-active-link' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('absensi.rekap') ? 'brand-text' : 'text-white/60 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                Presensi Hari Ini
            </a>
            <a href="{{ route('absensi.laporan') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('absensi.laporan') ? 'brand-active-link' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('absensi.laporan') ? 'brand-text' : 'text-white/60 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Laporan Bulanan
            </a>
            <a href="{{ route('siswa.export-qr') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('siswa.export-qr') ? 'brand-active-link' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('siswa.export-qr') ? 'brand-text' : 'text-white/60 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h5.25v5.25H3.75V4.5zM15 4.5h5.25v5.25H15V4.5zM3.75 15h5.25v5.25H3.75V15zM15 15h2.25v2.25H15V15zM17.25 17.25h3v3h-3v-3zM19.5 15h.75v.75h-.75V15zM15 19.5h.75v.75H15v-.75z" /></svg>
                Buat QR Siswa
            </a>

            <div class="pt-4 mt-4 border-t border-white/10">
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-white/35">Data</p>
                <a href="{{ route('siswa.index') }}"
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ (request()->routeIs('siswa.*') && !request()->routeIs('siswa.export-qr')) ? 'brand-active-link' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 {{ (request()->routeIs('siswa.*') && !request()->routeIs('siswa.export-qr')) ? 'brand-text' : 'text-white/60 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></svg>
                    Data Siswa
                </a>
                <a href="{{ route('classes.index') }}"
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('classes.*') ? 'brand-active-link' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('classes.*') ? 'brand-text' : 'text-white/60 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 15.5c0 1.657-.895 3.156-2.32 4.147M12 14v7m0 0c-2.21 0-4-1.79-4-4v-3" /></svg>
                    Kelola Kelas
                </a>
            </div>
        </nav>

        <div class="p-3 border-t border-white/10">
            <div class="flex items-center gap-3 px-2 py-2 rounded-xl bg-white/5">
                <div class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center text-sm font-semibold shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" title="Keluar" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition text-xs font-medium">
                        <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3H21" /></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 lg:ml-64 min-w-0 flex flex-col">
        <header class="bg-white/80 backdrop-blur border-b border-slate-200 px-5 sm:px-8 py-4 sticky top-0 z-20">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:bg-slate-100 transition shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-semibold text-slate-800 truncate"><?php echo $title ?? 'Dashboard'; ?></h2>
                        <?php if(isset($subtitle)): ?>
                            <p class="text-sm text-slate-500 mt-0.5 truncate"><?php echo $subtitle; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="shrink-0">
                    @include('partials.jam-realtime', ['size' => 'small', 'inline' => true])
                </div>
            </div>
        </header>
        <div class="p-5 sm:p-8 flex-1">
            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    {{ session('error') }}
                </div>
            @endif