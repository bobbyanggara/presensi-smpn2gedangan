<?php $title = 'Scan Presensi'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:500,600,700,800|inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{
            --font-display:'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            --font-body:'Inter', ui-sans-serif, system-ui, sans-serif;
            --brand-green:#0e7c3f;
            --brand-green-dark:#0b6633;
            --brand-green-darker:#063b1f;
            --brand-green-light:#e9f7ee;

            /* Aksen status monitoring, tetap dalam nuansa hijau/putih situs */
            --status-green:var(--brand-green);
            --status-red:#dc2626;
            --status-amber:#d97706;

            /* Skala warna netral & elevasi — dipakai konsisten di semua kartu
               supaya tidak ada campuran shadow/border/radius yang beda-beda sendiri. */
            --ink-900:#0f172a;
            --ink-700:#334155;
            --ink-500:#64748b;
            --ink-400:#94a3b8;
            --line:#e6eaef;
            --line-soft:#eef1f5;
            --surface:#ffffff;
            --canvas:#f1f5f9;

            --radius-lg:18px;
            --radius-md:12px;
            --radius-sm:9px;
            --shadow-card:0 1px 2px rgba(15,23,42,.04), 0 8px 20px -12px rgba(15,23,42,.10);
            --shadow-card-hover:0 1px 2px rgba(15,23,42,.05), 0 14px 28px -14px rgba(15,23,42,.16);
        }
        html,body{ height:100%; }
        html{ font-family: var(--font-body); }
        body{ font-family: var(--font-body); margin:0; background:var(--canvas); }
        h1,h2,h3,.font-display{ font-family: var(--font-display); letter-spacing:-0.01em; }
        .brand-bg{ background-color: var(--brand-green); }
        .brand-bg-hover:hover{ background-color: var(--brand-green-dark); }
        .brand-text{ color: var(--brand-green-dark); }
        .brand-text-hover:hover{ color: var(--brand-green-dark); }
        .brand-ring:focus{ border-color: var(--brand-green) !important; box-shadow: 0 0 0 3px rgba(14,124,63,.12); }
        .brand-bg-soft{ background-color: rgba(14,124,63,.1); }

        /* Kartu standar dipakai di seluruh halaman (kamera, NIS, riwayat) supaya
           radius/shadow/border-nya konsisten satu sama lain. */
        .panel-card{ background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); }

        .sidebar-logo-box{ width:44px; height:44px; min-width:44px; min-height:44px; border-radius:11px; background:#fff; padding:5px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 1px 2px rgba(0,0,0,.06); overflow:hidden; border:1px solid var(--line); }
        .sidebar-logo-box img{ width:100%; height:100%; object-fit:contain; display:block; }

        /* ===== Topbar monitoring ===== */
        .monitor-topbar{
            flex-shrink:0;
            background:var(--surface);
            border-bottom:1px solid var(--line);
            padding:0.7rem 1.5rem;
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            color:var(--ink-900);
        }
        .monitor-title{ font-family:var(--font-display); font-weight:700; font-size:15px; color:var(--ink-900); line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .monitor-subtitle-row{ margin-top:3px; }

        .status-pill{
            display:inline-flex; align-items:center; gap:6px;
            font-size:11px; font-weight:600; letter-spacing:.02em;
            padding:3px 9px 3px 7px; border-radius:999px;
            background:var(--brand-green-light); border:1px solid rgba(14,124,63,.15);
            color:var(--brand-green-dark); white-space:nowrap;
        }
        .status-dot{ width:6px; height:6px; border-radius:999px; background:var(--status-red); flex-shrink:0; }
        .status-dot.is-live{ background:var(--status-green); box-shadow:0 0 0 3px rgba(14,124,63,.18); animation: dot-pulse 1.8s ease-in-out infinite; }
        @keyframes dot-pulse{ 0%,100%{ opacity:1; } 50%{ opacity:.45; } }

        .topbar-btn{
            display:inline-flex; align-items:center; gap:6px;
            font-size:12.5px; font-weight:600; color:var(--ink-700);
            background:var(--canvas); border:1px solid var(--line);
            padding:8px 13px; border-radius:var(--radius-sm); cursor:pointer; transition:.15s;
        }
        .topbar-btn:hover{ background:var(--brand-green-light); border-color:rgba(14,124,63,.25); color:var(--brand-green-dark); }
        .topbar-btn:active{ transform:translateY(1px); }

        /* ===== Field umum (select lokasi, input NIS) ===== */
        .field-eyebrow{ display:block; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-400); margin-bottom:7px; }
        .field-hint{ font-size:12.5px; color:var(--ink-500); margin-top:9px; line-height:1.45; }
        .select-wrap{ position:relative; }
        .field-select{
            width:100%; font-size:14px; font-weight:600; color:var(--ink-900);
            border:1.5px solid var(--line); border-radius:var(--radius-md);
            padding:0.68rem 2.35rem 0.68rem 0.9rem;
            appearance:none; -webkit-appearance:none; background:var(--surface);
            outline:none; transition:.15s; cursor:pointer; box-sizing:border-box;
        }
        .field-select:hover{ border-color:#cbd5e1; }
        .field-select:focus{ border-color:var(--brand-green); box-shadow:0 0 0 3px rgba(14,124,63,.12); }
        .select-chevron{ position:absolute; right:0.85rem; top:50%; transform:translateY(-50%); width:16px; height:16px; color:var(--ink-400); pointer-events:none; }

        /* ===== Kartu kamera bergaya monitoring ===== */
        .camera-frame-wrap{ position:relative; background:#0b0d0c; width:100%; aspect-ratio: 4 / 3; overflow:hidden; }

        /* Vignette halus di pojok — memberi kedalaman ala kamera monitoring
           profesional TANPA pola grid yang bikin video terlihat kotak-kotak
           dan berantakan (terutama setelah video terkompresi/di-encode). */
        .camera-frame-wrap::before{
            content:''; position:absolute; inset:0; pointer-events:none; z-index:1;
            background:radial-gradient(ellipse at center, rgba(0,0,0,0) 45%, rgba(0,0,0,.5) 100%);
        }
        /* Garis rekaman tipis di tepi atas, aksen brand hijau — pengganti grid,
           kesan "kamera monitoring resmi" tanpa menutupi wajah/QR. */
        .camera-frame-wrap::after{
            content:''; position:absolute; left:0; right:0; top:0; height:3px; z-index:2;
            background:linear-gradient(90deg, transparent, var(--brand-green) 20%, #34d399 50%, var(--brand-green) 80%, transparent);
            opacity:.85;
        }

        .camera-status-chip{
            position:absolute; top:10px; right:10px; z-index:3;
            display:inline-flex; align-items:center; gap:5px;
            background:rgba(0,0,0,.55); backdrop-filter:blur(2px);
            padding:4px 9px; border-radius:999px;
            font-size:10.5px; font-weight:700; letter-spacing:.04em; color:#94a3b8;
        }
        .camera-status-chip .dot{ width:6px; height:6px; border-radius:999px; background:var(--status-red); flex-shrink:0; }
        .camera-status-chip.is-on{ color:#bbf7d0; }
        .camera-status-chip.is-on .dot{ background:var(--status-green); animation: dot-pulse 1.8s ease-in-out infinite; }

        /* ===== Kotak target scan (viewfinder) =====
           Satu box tegas dengan siku tebal + guide box tipis, dilapisi drop-shadow
           supaya tetap kelihatan jelas baik di video gelap maupun latar terang. */
        .scan-target-zone{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none; z-index:2; }
        .scan-target{
            position:relative; width:68%; max-width:280px; min-width:170px; aspect-ratio: 1 / 1;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,.6)) drop-shadow(0 0 10px rgba(0,0,0,.35));
        }
        .scan-target-guide{ position:absolute; inset:0; border:1px solid rgba(255,255,255,.16); border-radius:1rem; }
        .scan-target-corner{ position:absolute; width:2.5rem; height:2.5rem; border:0 solid #ffffff; }
        .scan-target-corner.tl{ top:-3px; left:-3px; border-top-width:5px; border-left-width:5px; border-radius:0.9rem 0 0 0; }
        .scan-target-corner.tr{ top:-3px; right:-3px; border-top-width:5px; border-right-width:5px; border-radius:0 0.9rem 0 0; }
        .scan-target-corner.bl{ bottom:-3px; left:-3px; border-bottom-width:5px; border-left-width:5px; border-radius:0 0 0 0.9rem; }
        .scan-target-corner.br{ bottom:-3px; right:-3px; border-bottom-width:5px; border-right-width:5px; border-radius:0 0 0.9rem 0; }
        .scan-target-hint{
            position:absolute; left:0; right:0; bottom:12px; z-index:3;
            text-align:center; font-size:11px; font-weight:600; letter-spacing:.01em; color:rgba(255,255,255,.85);
            text-shadow:0 1px 3px rgba(0,0,0,.7);
        }

        /* ===== Panel riwayat absensi per lokasi ===== */
        .riwayat-header{
            display:flex; align-items:center; justify-content:space-between; gap:8px;
            padding:0.75rem 1rem; border-bottom:1px solid var(--line-soft); flex-shrink:0;
        }
        .riwayat-title-group{ display:flex; align-items:center; gap:8px; min-width:0; }
        .riwayat-title-icon{
            width:26px; height:26px; border-radius:8px; flex-shrink:0;
            background:var(--brand-green-light); color:var(--brand-green-dark);
            display:flex; align-items:center; justify-content:center;
        }
        .riwayat-title{ font-family:var(--font-display); font-weight:700; font-size:13px; color:var(--ink-900); }
        .riwayat-count-pill{
            font-size:11px; font-weight:700; color:var(--brand-green-dark);
            background:var(--brand-green-light); border:1px solid rgba(14,124,63,.15);
            padding:2px 10px; border-radius:999px; flex-shrink:0; min-width:22px; text-align:center;
        }
        /* Semua item riwayat dirender (tidak dibatasi jumlahnya). Kalau lebih
           tinggi dari ruang yang tersedia, scroll di DALAM kotak ini saja —
           kartu di sekitarnya (topbar, kamera, dst) tetap diam di tempat. */
        .riwayat-list{ overflow-y:auto; overflow-x:hidden; flex:1; min-height:0; -webkit-overflow-scrolling:touch; scrollbar-width:thin; }
        .riwayat-list::-webkit-scrollbar{ width:6px; }
        .riwayat-list::-webkit-scrollbar-track{ background:transparent; }
        .riwayat-list::-webkit-scrollbar-thumb{ background:var(--line); border-radius:999px; }
        .riwayat-list::-webkit-scrollbar-thumb:hover{ background:var(--ink-400); }
        .riwayat-row{
            display:flex; align-items:center; gap:10px;
            padding:0.5rem 1rem; border-bottom:1px solid var(--line-soft);
            transition:background-color .15s;
        }
        .riwayat-row:hover{ background:#fafbfc; }
        .riwayat-row:last-child{ border-bottom:none; }
        .riwayat-row.is-new{ animation: riwayat-flash 1.6s ease-out; }
        @keyframes riwayat-flash{ 0%{ background:var(--brand-green-light); } 100%{ background:transparent; } }
        .riwayat-avatar{
            width:28px; height:28px; min-width:28px; border-radius:9999px;
            background:var(--line-soft); color:var(--ink-400); font-weight:700; font-size:11px;
            display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0;
            box-shadow:0 0 0 1px var(--line);
        }
        .riwayat-avatar img{ width:100%; height:100%; object-fit:cover; display:block; }
        .riwayat-info{ min-width:0; flex:1; }
        .riwayat-nama{ font-size:12.5px; font-weight:600; color:var(--ink-900); line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:0; }
        .riwayat-meta{ font-size:10.5px; color:var(--ink-400); line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:0.1rem 0 0; }
        .riwayat-jam{ font-size:11px; font-weight:700; color:var(--brand-green-dark); flex-shrink:0; white-space:nowrap; }
        .riwayat-empty, .riwayat-loading{ padding:1.5rem 1.1rem; text-align:center; font-size:12.5px; color:var(--ink-400); }

    </style>
</head>
<body class="antialiased text-slate-800">

<div id="scan-fullscreen-wrap" style="min-height:100dvh; display:flex; flex-direction:column;">

    {{-- Topbar monitoring: identitas sekolah + status sistem + kontrol keamanan --}}
    <header class="monitor-topbar">
        <div style="display:flex; align-items:center; gap:11px; min-width:0;">
            <div class="sidebar-logo-box">
                <img src="{{ asset('images/logo-smp.webp') }}" alt="Logo">
            </div>
            <div style="min-width:0;">
                <p class="monitor-title">Scan Presensi SMP Negeri 2 Gedangan</p>
                <p class="monitor-subtitle-row">
                    <span class="status-pill">
                        <span id="topbar-status-dot" class="status-dot"></span>
                        <span id="topbar-status-text">Menyiapkan kamera…</span>
                    </span>
                </p>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
            <button type="button" id="fullscreen-toggle-btn" class="topbar-btn" title="Tampilan layar penuh">
                <svg style="width:14px;height:14px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 20.25v-4.5m0 4.5h-4.5m4.5 0L15 15"/></svg>
                <span class="hidden-on-narrow">Layar penuh</span>
            </button>
        </div>
    </header>

    @if(session('success'))
        <div style="flex-shrink:0; margin:14px 20px 0;" class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="flex-shrink:0; margin:14px 20px 0;" class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

<div id="scan-page-grid" style="width:100%; flex:1; min-height:0;">

<div id="scan-camera-col">
    {{-- Preview webcam bergaya monitoring: vignette halus, chip status kamera, kotak target scan --}}
    <div id="scan-camera-card" class="panel-card" style="overflow:hidden;">
        <div class="camera-frame-wrap">
            <video id="camera-preview" style="width:100%; height:100%; object-fit:cover; display:block; position:relative; z-index:0;" muted playsinline autoplay></video>

            <span class="camera-status-chip" id="camera-status-chip"><span class="dot"></span><span id="camera-status-chip-text">Camera: OFF</span></span>

            <div id="camera-loading" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; z-index:4; background:rgba(11,13,12,.55);">
                <svg id="camera-spinner" style="width:1.75rem; height:1.75rem; color:rgba(255,255,255,0.7); animation: camera-spin 0.8s linear infinite;" fill="none" viewBox="0 0 24 24"><circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>

            <div class="scan-target-zone">
                <div class="scan-target">
                    <span class="scan-target-guide"></span>
                    <span class="scan-target-corner tl"></span>
                    <span class="scan-target-corner tr"></span>
                    <span class="scan-target-corner bl"></span>
                    <span class="scan-target-corner br"></span>
                    <div id="scan-line"></div>
                </div>
                <p class="scan-target-hint">Posisikan kode QR kartu pelajar di dalam kotak</p>
            </div>
        </div>

        <div style="padding:0.75rem 1.25rem; flex-shrink:0;">
            <p id="camera-status" style="font-size:0.8125rem; color:#475569; text-align:center; min-height:1.25rem; margin:0;">Meminta izin kamera…</p>

            <div id="camera-device-wrap" style="display:none; margin-top:0.5rem;">
                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:0.25rem;">Pilih kamera</label>
                <select id="camera-device-select" style="width:100%; font-size:0.8125rem; border:1px solid #e2e8f0; border-radius:0.5rem; padding:0.5rem 0.75rem; outline:none; background:#ffffff; color:#1e293b;"></select>
            </div>
        </div>
    </div>
</div>

<div id="scan-side-col">

    <div id="scan-jam-wrap">
        @include('partials.jam-realtime', ['size' => 'big'])
    </div>

    <div id="scan-location-wrap" class="panel-card" style="padding:0.95rem 1.1rem; text-align:left;">
        <label for="location_id" class="field-eyebrow">Titik hadir</label>
        <div class="select-wrap">
            <select
                name="location_id"
                id="location_id"
                required
                class="field-select"
            >
                <option value="" selected disabled>- Pilih lokasi -</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            <svg class="select-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
        </div>
        <p id="location-info" class="field-hint">
            Silakan pilih lokasi hadir terlebih dahulu sebelum melakukan scan.
        </p>
    </div>

    <div id="scan-nis-card" class="panel-card" style="padding:1.1rem; text-align:center;">

        <div id="scan-error" class="hidden mb-2.5 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-2 rounded-xl text-left">
            <svg style="width:18px;height:18px;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <span id="scan-error-text"></span>
        </div>

        {{-- Kotak info hasil scan terakhir sengaja dihilangkan atas permintaan
             (dulu di sini: nama, kelas, NIS, dan status hadir/sudah absen). --}}

        <form id="scan-form" action="{{ route('absensi.proses') }}" method="POST">
            @csrf
            <label for="nis" class="field-eyebrow" style="text-align:left;">Kode NIS / Hasil Scan</label>
            <input
                type="text"
                name="nis"
                id="nis"
                autocomplete="off"
                placeholder="NIS akan muncul di kolom ini"
                class="w-full text-center text-lg font-mono border-2 border-slate-200 rounded-xl px-4 py-2.5 brand-ring outline-none transition"
            >
            {{-- Tombol "Catat Kehadiran" sengaja dihapus supaya kartu ini lebih
                 ringkas — submit dilakukan cukup dengan menekan Enter di input
                 NIS (form tetap submit secara native walau tanpa tombol). --}}
        </form>
    </div>

    {{-- Riwayat absensi sesuai lokasi yang sedang dipilih. Panel ini mengisi
         sisa tinggi layar; kalau isinya lebih panjang dari ruang yang ada,
         yang scroll cukup kotak riwayat-nya saja (#riwayat-list), bukan
         seluruh halaman — dan semua data tetap dirender, tidak dipotong. --}}
    <div id="scan-riwayat-card" class="panel-card">
        <div class="riwayat-header">
            <div class="riwayat-title-group">
                <span class="riwayat-title-icon">
                    <svg style="width:14px;height:14px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <span class="riwayat-title">Riwayat Presensi</span>
            </div>
            <span id="riwayat-count-pill" class="riwayat-count-pill">0</span>
        </div>
        <div id="riwayat-list" class="riwayat-list">
            <p class="riwayat-empty">Pilih lokasi hadir untuk melihat riwayat.</p>
        </div>
    </div>
</div>

</div>

<style>
    /* Default: halaman BOLEH di-scroll kalau kontennya lebih tinggi dari layar.
       overflow:hidden cuma dipasang saat benar-benar dalam mode fullscreen
       (lihat class "is-fullscreen" di bawah, di-toggle lewat Fullscreen API). */
    #scan-fullscreen-wrap{ min-height:100dvh; overflow-y:auto; overflow-x:hidden; -webkit-overflow-scrolling:touch; }
    #scan-fullscreen-wrap.is-fullscreen{ height:100dvh; overflow:hidden; }

    /* ===== Default (HP/tablet sempit, atau lebar belum terdeteksi): satu kolom, boleh scroll ===== */
    #scan-page-grid{
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:16px;
        padding:16px 20px 20px;
        box-sizing:border-box;
        min-height:100%;
    }
    #scan-camera-col{
        width:100%; max-width:34rem; margin:0 auto;
        display:flex; flex-direction:column;
        min-height:0;
    }
    #scan-side-col{
        width:100%; max-width:34rem; margin:0 auto;
        display:flex; flex-direction:column;
        gap:0.85rem;
        min-height:0;
        overflow:hidden;
    }

    #scan-jam-wrap{ flex-shrink:0; zoom:0.72; margin:-6px 0; }
    #scan-location-wrap{ flex-shrink:0; }
    #scan-camera-card{ flex-shrink:0; }
    #scan-camera-card .camera-frame-wrap{ aspect-ratio: 4 / 3; }
    #scan-nis-card{ flex-shrink:0; }
    /* Riwayat mengisi SISA ruang yang ada. Semua baris dirender; kalau tidak
       muat, #riwayat-list yang scroll (lihat CSS .riwayat-list di atas),
       kartu ini sendiri overflow:hidden hanya supaya sudut radius tetap rapi.

       Di mode normal (bukan fullscreen), #scan-fullscreen-wrap boleh discroll
       dan kolomnya TIDAK dikunci ke tinggi layar, jadi flex:1 saja tidak cukup
       untuk membatasi tinggi kartu ini — kalau isinya panjang, dia akan ikut
       mendorong tinggi seluruh halaman. Makanya dikasih max-height berbasis
       viewport supaya begitu penuh, .riwayat-list di dalamnya yang scroll
       sendiri, sama seperti perilaku di mode layar penuh. */
    #scan-riwayat-card{ flex:1; min-height:0; max-height:min(60dvh, 480px); display:flex; flex-direction:column; overflow:hidden; }

    @media (max-width: 480px){
        .hidden-on-narrow{ display:none; }
        #topbar-clock-pill{ display:none; }
    }

    /* ===== Layar lebar / mode kios (PC AIO): 2 kolom, tinggi dikunci ke layar, TANPA scroll =====
       Kamera besar di kiri mengisi tinggi layar, jam + lokasi + form NIS di kanan.

       Class "layout-wide" di-set lewat JavaScript (lihat script paling bawah) berdasarkan lebar
       jendela, TERPISAH dari class "is-fullscreen" (yang nyala kalau beneran masuk Fullscreen API
       lewat tombol/F11). Sebelumnya tinggi cuma dikunci pas KEDUANYA aktif, jadi di jendela biasa
       (layout-wide tapi belum is-fullscreen) halaman masih ikut discroll. Sekarang dikunci begitu
       "layout-wide" aktif saja, supaya tampilan jendela biasa di layar lebar SAMA PERSIS kayak
       mode layar penuh — nggak ada scroll halaman, cuma riwayat presensi yang scroll sendiri. */
    #scan-fullscreen-wrap.layout-wide{ height:100dvh; overflow:hidden; }

    #scan-fullscreen-wrap.layout-wide #scan-page-grid{
        display:grid;
        grid-template-columns: minmax(320px, 560px) minmax(340px, 1fr);
        grid-template-rows: minmax(0, 1fr);
        justify-content:center;
        align-items:stretch;
        gap:28px;
        height:100%;
        max-height:100%;
        padding:20px 32px;
        overflow:hidden;
    }

    #scan-fullscreen-wrap.layout-wide #scan-camera-col{
        max-width:none;
        margin:0;
        height:100%;
        min-height:0;
    }
    #scan-fullscreen-wrap.layout-wide #scan-camera-card{
        flex-shrink:1;
        height:100%;
        display:flex;
        flex-direction:column;
        min-height:0;
    }
    /* Kamera mengisi tinggi kolom, bukan lagi dipaku rasio 4:3, biar ngga kepotong di layar pendek */
    #scan-fullscreen-wrap.layout-wide #scan-camera-card .camera-frame-wrap{
        aspect-ratio:unset;
        flex:1;
        min-height:0;
    }

    #scan-fullscreen-wrap.layout-wide #scan-side-col{
        max-width:none;
        margin:0;
        height:100%;
        min-height:0;
        gap:10px;
        justify-content:flex-start;
        overflow-y:auto;
        overflow-x:hidden;
    }
    #scan-fullscreen-wrap.layout-wide #scan-jam-wrap{ zoom:0.8; margin:-8px 0; }
    #scan-fullscreen-wrap.layout-wide #scan-nis-card{
        flex-shrink:0;
        display:flex;
        flex-direction:column;
        justify-content:center;
    }
    /* Panel riwayat mengisi SISA tinggi kolom kanan. Semua baris riwayat
       dirender; kalau kepanjangan, #riwayat-list scroll sendiri di dalam
       kotaknya tanpa mempengaruhi tinggi kartu lain di kolom ini. Tinggi kolom
       sudah dikunci ke layar lewat #scan-page-grid di atas, jadi flex:1 saja
       cukup — tidak perlu max-height dvh manual lagi seperti di mode sempit.

       min-height dikasih supaya kartu ini nggak ikut kegencet sampai cuma
       muat 1 baris waktu jendela browser pendek (misal karena tab/address
       bar makan tempat, bukan browser fullscreen beneran). Kalau ruang
       kolom kanan memang kurang dari total kebutuhan (jam+lokasi+nis+ riwayat
       minimal ini), #scan-side-col di atas yang scroll, bukan riwayat-nya
       dipaksa muat di ruang sempit. */
    #scan-fullscreen-wrap.layout-wide #scan-riwayat-card{
        flex:1;
        flex-shrink:0;
        min-height:220px;
        max-height:none;
        display:flex;
        flex-direction:column;
        overflow:hidden;
    }

    /* ===== Layar sangat lebar (AIO 24"+ / 1920px CSS-px ke atas): sedikit lebih lega ===== */
    #scan-fullscreen-wrap.layout-wide.layout-wide-xl #scan-page-grid{
        grid-template-columns: minmax(360px, 640px) minmax(380px, 1fr);
        gap:36px;
        padding:28px 48px;
    }
    #scan-fullscreen-wrap.layout-wide.layout-wide-xl #scan-jam-wrap{ zoom:0.95; }
    #scan-fullscreen-wrap.layout-wide.layout-wide-xl #scan-nis-card input#nis{ font-size:1.35rem; padding-top:1.1rem; padding-bottom:1.1rem; }
</style>

<style>

    #scan-line {
        position: absolute;
        left: 0;
        right: 0;
        top: 4%;
        height: 3px;
        border-radius: 2px;
        background: linear-gradient(90deg, transparent, #34d399, transparent);
        box-shadow: 0 0 10px 2px rgba(52, 211, 153, 0.7);
        animation: scan-line-move 2.2s ease-in-out infinite;
    }
    @keyframes scan-line-move {
        0%   { top: 4%; opacity: 0; }
        12%  { opacity: 1; }
        88%  { opacity: 1; }
        100% { top: 94%; opacity: 0; }
    }
    @keyframes camera-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Kotak info hasil scan (#scan-result) sudah dihapus dari markup, jadi
       aturan status-hadir/status-sudah untuknya tidak diperlukan lagi. */
</style>

{{-- Fallback untuk kondisi tanpa JavaScript: server tetap redirect balik dengan session flash seperti biasa --}}
@if(session('popup_siswa'))
<?php $p = session('popup_siswa'); ?>
<div id="popup-overlay" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div id="popup-card" class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center transform transition-all">
        @if($p['status'] === 'sudah')
            <div class="w-14 h-14 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        @endif

        @if(!empty($p['foto_url']))
            <div style="width:96px; height:96px; border-radius:9999px; overflow:hidden; flex-shrink:0; margin:0 auto 1rem; border:2px solid #e2e8f0; box-sizing:border-box;">
                <img src="{{ $p['foto_url'] }}" alt="Foto {{ $p['nama'] }}" width="96" height="96" style="width:96px; height:96px; max-width:96px; max-height:96px; object-fit:cover; display:block;" onerror="this.parentElement.style.display='none'; this.parentElement.nextElementSibling.style.display='flex';">
            </div>
            <div class="w-24 h-24 rounded-full bg-slate-100 items-center justify-center text-slate-400 text-3xl font-semibold mx-auto mb-4" style="display:none">{{ strtoupper(substr($p['nama'], 0, 1)) }}</div>
        @else
            <div class="w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-3xl font-semibold mx-auto mb-4">
                {{ strtoupper(substr($p['nama'], 0, 1)) }}
            </div>
        @endif

        <h3 class="text-xl font-bold text-slate-800">{{ $p['nama'] }}</h3>
        <p class="text-xs font-mono text-slate-400 mt-1">NIS {{ $p['nis'] }}</p>
        <p class="text-sm text-slate-500 mt-0.5">{{ $p['kelas'] }}</p>

        @if(!empty($p['location_name']))
            <p class="text-sm text-slate-600 mt-1.5 font-medium">Lokasi: {{ $p['location_name'] }}</p>
        @endif

        <div class="mt-4">
            @if($p['status'] === 'hadir')
                <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 text-sm font-medium px-3 py-1.5 rounded-full"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Hadir · {{ $p['jam'] }}</span>
            @else
                <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-sm font-medium px-3 py-1.5 rounded-full">Sudah hadir pukul {{ $p['jam'] }}</span>
            @endif
        </div>
    </div>
</div>
<script>
    setTimeout(function() {
        var overlay = document.getElementById('popup-overlay');
        if (overlay) {
            overlay.style.transition = 'opacity 0.3s';
            overlay.style.opacity = '0';
            setTimeout(function() { overlay.remove(); }, 300);
        }
    }, 2500);

    // Isi juga panel hasil scan di kartu NIS, biar konsisten dengan alur AJAX normal.
    (function () {
        var p = @json($p);
        var resultBox = document.getElementById('scan-result');
        if (!resultBox || !p) return;

        var avatar = document.getElementById('scan-result-avatar');
        var nama = document.getElementById('scan-result-nama');
        var meta = document.getElementById('scan-result-meta');
        var badge = document.getElementById('scan-result-badge');

        if (avatar) {
            avatar.innerHTML = '';
            if (p.foto_url) {
                var fbImg = document.createElement('img');
                fbImg.src = p.foto_url;
                fbImg.alt = '';
                fbImg.width = 48;
                fbImg.height = 48;
                fbImg.style.cssText = 'width:48px; height:48px; max-width:48px; max-height:48px; object-fit:cover; display:block;';
                fbImg.onerror = function () {
                    this.remove();
                    avatar.textContent = (p.nama || '?').charAt(0).toUpperCase();
                };
                avatar.appendChild(fbImg);
            } else {
                avatar.textContent = (p.nama || '?').charAt(0).toUpperCase();
            }
        }
        if (nama) nama.textContent = p.nama || '-';
        if (meta) meta.textContent = 'NIS ' + (p.nis || '-') + ' · ' + (p.kelas || '-');

        resultBox.classList.remove('status-hadir', 'status-sudah');
        if (badge) badge.classList.remove('status-hadir', 'status-sudah');

        if (p.status === 'hadir') {
            resultBox.classList.add('status-hadir');
            if (badge) { badge.classList.add('status-hadir'); badge.textContent = 'Hadir · ' + p.jam; }
        } else {
            resultBox.classList.add('status-sudah');
            if (badge) { badge.classList.add('status-sudah'); badge.textContent = 'Sudah hadir · ' + p.jam; }
        }

        resultBox.classList.remove('hidden');
    })();
</script>
@endif

<script>
(function () {
    var form = document.getElementById('scan-form');
    var input = document.getElementById('nis');
    var locationSelect = document.getElementById('location_id');
    var submitBtn = document.getElementById('scan-submit');
    var errorBox = document.getElementById('scan-error');
    var errorText = document.getElementById('scan-error-text');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var busy = false;

    // Lokasi dipertahankan saat halaman di-refresh pada tab yang sama.
    // Saat tab baru dibuka, sessionStorage kosong sehingga user wajib memilih lokasi.
    var LOCATION_STORAGE_KEY = 'scan_absensi_location_id';
    var LOCATION_NAME_STORAGE_KEY = 'scan_absensi_location_name';

    function restoreSelectedLocation() {
        var savedLocationId = sessionStorage.getItem(LOCATION_STORAGE_KEY);
        if (!savedLocationId) {
            locationSelect.value = '';
            return;
        }

        var optionExists = Array.prototype.some.call(
            locationSelect.options,
            function (option) { return option.value === savedLocationId; }
        );

        if (optionExists) {
            locationSelect.value = savedLocationId;
        } else {
            sessionStorage.removeItem(LOCATION_STORAGE_KEY);
            sessionStorage.removeItem(LOCATION_NAME_STORAGE_KEY);
            locationSelect.value = '';
        }
    }

    locationSelect.addEventListener('change', function () {
        if (this.value) {
            sessionStorage.setItem(LOCATION_STORAGE_KEY, this.value);
            var selected = this.options[this.selectedIndex];
            sessionStorage.setItem(
                LOCATION_NAME_STORAGE_KEY,
                selected ? selected.textContent.trim() : ''
            );

            var info = document.getElementById('location-info');
            if (info) {
                info.textContent = 'Lokasi aktif: ' + (selected ? selected.textContent.trim() : '');
            }

            loadRiwayat();
        }
    });

    restoreSelectedLocation();

    if (locationSelect.value) {
        var restoredOption = locationSelect.options[locationSelect.selectedIndex];
        var restoredInfo = document.getElementById('location-info');
        if (restoredInfo && restoredOption) {
            restoredInfo.textContent = 'Lokasi aktif: ' + restoredOption.textContent.trim();
        }
    }

    // Sengaja TIDAK memanggil input.focus() di sini — kalau di-fokus otomatis,
    // HP/tablet akan langsung memunculkan keyboard virtual padahal NIS-nya
    // sudah otomatis terisi dari hasil scan kamera (lihat handleDecodeResult),
    // tidak perlu mengetik manual.
    loadRiwayat();

    // ================== Riwayat absensi sesuai lokasi ==================
    // Endpoint ini perlu ditambahkan di backend (route + controller), lihat
    // catatan di akhir chat. Kalau endpoint belum ada, panel cukup menampilkan
    // pesan placeholder tanpa mengganggu alur scan yang lain.
    var RIWAYAT_URL = '{{ route('absensi.live-feed') }}';
    var riwayatList = document.getElementById('riwayat-list');
    var riwayatCountPill = document.getElementById('riwayat-count-pill');
    var riwayatLoading = false;
    var riwayatAutoTimer = null;

    function riwayatInitial(nama) {
        return (nama || '?').trim().charAt(0).toUpperCase() || '?';
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderRiwayatPlaceholder(text) {
        if (!riwayatList) return;
        riwayatList.innerHTML = '<p class="riwayat-empty">' + escapeHtml(text) + '</p>';
        if (riwayatCountPill) riwayatCountPill.textContent = '0';
    }

    function renderRiwayat(items, highlightNis) {
        if (!riwayatList) return;
        items = items || [];

        if (!items.length) {
            if (riwayatCountPill) riwayatCountPill.textContent = '0';
            renderRiwayatPlaceholder('Belum ada yang hadir di lokasi ini hari ini.');
            return;
        }

        riwayatList.innerHTML = items.map(function (it) {
            var avatarInner = it.foto_url
                ? '<img src="' + it.foto_url + '" alt="" onerror="this.remove();">'
                : escapeHtml(riwayatInitial(it.nama));
            var rowClass = 'riwayat-row' + (highlightNis && it.nis == highlightNis ? ' is-new' : '');

            return '' +
                '<div class="' + rowClass + '">' +
                    '<div class="riwayat-avatar">' + avatarInner + '</div>' +
                    '<div class="riwayat-info">' +
                        '<p class="riwayat-nama">' + escapeHtml(it.nama || '-') + '</p>' +
                        '<p class="riwayat-meta">' + escapeHtml(it.kelas || '-') + ' &middot; NIS ' + escapeHtml(it.nis || '-') + '</p>' +
                    '</div>' +
                    '<span class="riwayat-jam">' + escapeHtml(it.jam || '-') + '</span>' +
                '</div>';
        }).join('');

        // Semua item ditampilkan apa adanya (tidak ada lagi yang disembunyikan
        // lewat JS) — kalau daftarnya lebih panjang dari kotaknya, kotak yang
        // scroll sendiri (lihat CSS .riwayat-list: overflow-y:auto).
        if (riwayatCountPill) riwayatCountPill.textContent = items.length;
    }

    function loadRiwayat(highlightNis) {
        var locId = locationSelect.value;
        if (!locId) {
            renderRiwayatPlaceholder('Pilih lokasi hadir untuk melihat riwayat.');
            return;
        }
        if (riwayatLoading) return;
        riwayatLoading = true;

        fetch(RIWAYAT_URL + '?location_id=' + encodeURIComponent(locId), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('bad status');
            return res.json();
        })
        .then(function (data) {
            var items = data.data || data.riwayat || (Array.isArray(data) ? data : []);
            renderRiwayat(items, highlightNis);
        })
        .catch(function () {
            renderRiwayatPlaceholder('Gagal memuat riwayat, coba lagi.');
        })
        .finally(function () {
            riwayatLoading = false;
        });
    }

    // Auto-refresh dipercepat jadi tiap 3 detik supaya riwayat terasa realtime
    // (sebelumnya 20 detik — kelihatan seperti "harus refresh manual" karena
    // absensi dari perangkat/lokasi lain baru muncul lama sekali).
    function restartRiwayatAutoRefresh() {
        if (riwayatAutoTimer) clearInterval(riwayatAutoTimer);
        riwayatAutoTimer = setInterval(function () {
            if (locationSelect.value) loadRiwayat();
        }, 3000);
    }
    restartRiwayatAutoRefresh();

    // Browser men-throttle setInterval saat tab disembunyikan/di-minimize,
    // jadi begitu tab aktif lagi datanya bisa basi cukup lama. Refresh
    // langsung begitu tab/jendela kembali terlihat atau mendapat fokus,
    // supaya tidak perlu reload manual untuk lihat data terbaru.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && locationSelect.value) loadRiwayat();
    });
    window.addEventListener('focus', function () {
        if (locationSelect.value) loadRiwayat();
    });

    function hideError() {
        errorBox.classList.add('hidden');
    }

    function showError(msg) {
        errorText.textContent = msg;
        errorBox.classList.remove('hidden');
        setTimeout(hideError, 3000);
    }

    // Suara "buzz" dobel (nada rendah, khas error) + ucapan saat NIS/barcode tidak dikenali
    function playInvalidSound() {
        var ctx = getAudioCtx();
        if (!ctx) return;
        if (ctx.state === 'suspended') ctx.resume();
        playBeep(220, 0.14);
        setTimeout(function () { playBeep(220, 0.14); }, 180);
        setTimeout(function () { speakText('Barcode tidak valid'); }, 420);
    }

    function removePopup() {
        var old = document.getElementById('popup-overlay');
        if (old) old.remove();
    }

    // Panel hasil scan di dalam kartu NIS: menampilkan nama, kelas, dan NIS
    // siswa yang barusan terdeteksi. Tetap tampil (tidak auto-hilang) baik untuk
    // absen baru (hadir) maupun kalau kartu yang sama di-scan ulang (sudah absen),
    // supaya operator selalu bisa lihat identitas siswa terakhir yang ke-scan.
    var scanResultBox = document.getElementById('scan-result');
    var scanResultAvatar = document.getElementById('scan-result-avatar');
    var scanResultNama = document.getElementById('scan-result-nama');
    var scanResultMeta = document.getElementById('scan-result-meta');
    var scanResultBadge = document.getElementById('scan-result-badge');

    function updateScanResultPanel(p) {
        if (!scanResultBox) return;

        if (scanResultAvatar) {
            scanResultAvatar.innerHTML = '';
            if (p.foto_url) {
                var img = document.createElement('img');
                img.src = p.foto_url;
                img.alt = '';
                img.width = 48;
                img.height = 48;
                img.style.cssText = 'width:48px;height:48px;max-width:48px;max-height:48px;object-fit:cover;display:block;';
                img.onerror = function () {
                    this.remove();
                    scanResultAvatar.textContent = (p.nama || '?').charAt(0).toUpperCase();
                };
                scanResultAvatar.appendChild(img);
            } else {
                scanResultAvatar.textContent = (p.nama || '?').charAt(0).toUpperCase();
            }
        }

        if (scanResultNama) scanResultNama.textContent = p.nama || '-';
        if (scanResultMeta) scanResultMeta.textContent = 'NIS ' + (p.nis || '-') + ' · ' + (p.kelas || '-');

        scanResultBox.classList.remove('status-hadir', 'status-sudah');
        if (scanResultBadge) scanResultBadge.classList.remove('status-hadir', 'status-sudah');

        if (p.status === 'hadir') {
            scanResultBox.classList.add('status-hadir');
            if (scanResultBadge) {
                scanResultBadge.classList.add('status-hadir');
                scanResultBadge.textContent = 'Hadir · ' + p.jam;
            }
        } else {
            scanResultBox.classList.add('status-sudah');
            if (scanResultBadge) {
                scanResultBadge.classList.add('status-sudah');
                scanResultBadge.textContent = 'Sudah hadir · ' + p.jam;
            }
        }

        scanResultBox.classList.remove('hidden');
    }

    // ==== Suara notifikasi: bunyi "tit" + ucapkan nama siswa ====
    var audioCtx = null;
    function getAudioCtx() {
        if (!audioCtx) {
            try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
        }
        return audioCtx;
    }
    // Browser modern memblokir suara (AudioContext maupun speechSynthesis) sampai
    // ada interaksi user. Di alat scan ini nyaris tidak ada yang di-klik (operator
    // cuma tap dropdown lokasi sekali di awal, siswa tinggal tempel kartu) — jadi
    // dengar baik-baik SEMUA jenis interaksi (klik, tap/touch, tombol keyboard),
    // bukan cuma 'click' saja, supaya suara ke-"buka kunci" secepat mungkin begitu
    // ada sentuhan pertama apa pun di halaman ini.
    var audioUnlocked = false;
    function unlockAudio() {
        if (audioUnlocked) return;
        audioUnlocked = true;

        var ctx = getAudioCtx();
        if (ctx && ctx.state === 'suspended') ctx.resume();

        // Speech Synthesis juga butuh "dipanaskan" lewat gesture user, bukan cuma
        // audio context-nya. Ucapkan teks nyaris kosong (spasi) sekali supaya
        // engine TTS browser resmi ke-aktifkan; setelah ini scan otomatis (tanpa
        // klik sama sekali) tetap bisa bersuara.
        if (window.speechSynthesis) {
            try {
                window.speechSynthesis.resume();
                var warm = new SpeechSynthesisUtterance(' ');
                warm.volume = 0;
                window.speechSynthesis.speak(warm);
            } catch (e) {}
        }

        ['click', 'touchstart', 'touchend', 'pointerdown', 'keydown'].forEach(function (evt) {
            document.removeEventListener(evt, unlockAudio);
        });
    }
    ['click', 'touchstart', 'touchend', 'pointerdown', 'keydown'].forEach(function (evt) {
        document.addEventListener(evt, unlockAudio, { once: false });
    });

    // Bug lama di Chrome: kalau tab dibiarkan lama (kios menyala berjam-jam),
    // speechSynthesis diam-diam "macet" dan berhenti bersuara sama sekali tanpa
    // error apa pun. Trik yang sudah lama dipakai banyak developer: pause()
    // lalu resume() secara berkala supaya engine-nya tetap "hidup".
    if (window.speechSynthesis) {
        setInterval(function () {
            try {
                if (window.speechSynthesis.speaking || window.speechSynthesis.pending) {
                    window.speechSynthesis.pause();
                    window.speechSynthesis.resume();
                }
            } catch (e) {}
        }, 5000);
    }

    function playBeep(freq, duration) {
        var ctx = getAudioCtx();
        if (!ctx) return;
        if (ctx.state === 'suspended') ctx.resume();
        try {
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq || 1046;
            // Volume "tit" dinaikkan (dari 0.35 ke 0.85) supaya tetap kedengaran
            // jelas di luar ruangan / area yang agak berisik.
            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.85, ctx.currentTime + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + (duration || 0.15));
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + (duration || 0.15) + 0.03);
        } catch (e) {}
    }

    // Pilih suara pria untuk text-to-speech, kalau browser/OS menyediakannya.
    // Daftar voice kadang baru siap async (event 'voiceschanged'), makanya di-cache
    // dan dicari ulang begitu daftar voice berubah.
    var cachedMaleVoice = null;
    var maleVoiceHints = ['male', 'pria', 'laki', 'man', 'boy'];
    // Nama-nama voice ID/EN yang umumnya berkarakter pria di berbagai OS/browser.
    var knownMaleVoiceNames = ['ardi', 'google bahasa indonesia', 'david', 'mark', 'daniel', 'alex', 'fred', 'rishi'];

    function pickMaleVoice() {
        if (!window.speechSynthesis) return null;
        var voices = window.speechSynthesis.getVoices() || [];
        if (!voices.length) return null;

        // Prioritas 1: voice bahasa Indonesia yang namanya mengindikasikan suara pria.
        var idVoices = voices.filter(function (v) { return (v.lang || '').toLowerCase().indexOf('id') === 0; });
        var pools = [idVoices, voices];

        for (var p = 0; p < pools.length; p++) {
            var pool = pools[p];
            for (var i = 0; i < pool.length; i++) {
                var name = (pool[i].name || '').toLowerCase();
                if (maleVoiceHints.some(function (h) { return name.indexOf(h) !== -1; })) return pool[i];
            }
            for (var j = 0; j < pool.length; j++) {
                var name2 = (pool[j].name || '').toLowerCase();
                if (knownMaleVoiceNames.some(function (h) { return name2.indexOf(h) !== -1; })) return pool[j];
            }
        }

        // Fallback: voice bahasa Indonesia pertama yang ada, kalau tidak ketemu penanda gender.
        return idVoices[0] || null;
    }

    if (window.speechSynthesis) {
        cachedMaleVoice = pickMaleVoice();
        window.speechSynthesis.addEventListener('voiceschanged', function () {
            cachedMaleVoice = pickMaleVoice();
        });
    }

    // Simpan utterance yang sedang jalan di variabel LUAR fungsi (bukan cuma
    // variabel lokal) — ini menghindari bug lama di Chrome yang suka men-GC
    // (membuang) objek SpeechSynthesisUtterance sebelum sempat selesai bicara
    // kalau tidak ada referensi lain yang menahannya, bikin suara tiba-tiba
    // diam tanpa error.
    var activeUtterance = null;

    function speakText(text) {
        try {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            var utter = new SpeechSynthesisUtterance(text);
            utter.lang = 'id-ID';
            utter.rate = 1;
            utter.pitch = 1;
            // Volume di-set eksplisit ke maksimum (1 = 100%). Ini batas atas
            // dari Web Speech API browser — untuk kedengaran sampai keluar
            // ruangan, sisanya tergantung volume perangkat/speaker fisiknya
            // (naikkan volume sistem atau sambungkan speaker eksternal/toa).
            utter.volume = 1;
            if (!cachedMaleVoice) cachedMaleVoice = pickMaleVoice();
            if (cachedMaleVoice) utter.voice = cachedMaleVoice;
            activeUtterance = utter;
            window.speechSynthesis.speak(utter);
        } catch (e) {}
    }

    // Nama siswa biasanya tersimpan/ditampilkan dalam HURUF BESAR SEMUA.
    // Kalau teks all-caps langsung diucapkan lewat speechSynthesis, banyak
    // browser/voice yang mengiranya singkatan dan malah MENGEJA per huruf,
    // bukan membaca nama secara normal. Fungsi ini hanya mengubah teks yang
    // akan DIUCAPKAN menjadi Title Case (huruf awal tiap kata besar, sisanya
    // kecil) — tidak mengubah p.nama aslinya, jadi tampilan di popup/panel
    // lain tetap seperti semula.
    function toSpeechCase(name) {
        if (!name) return name;
        return name.toLowerCase().replace(/(^|\s)([a-z])/g, function (match, sep, chr) {
            return sep + chr.toUpperCase();
        });
    }

    function playAttendanceSound(p) {
        var namaUcap = toSpeechCase((p.nama || 'Siswa').trim());

        if (p.status === 'sudah') {
            // Sudah hadir sebelumnya: bunyi nada rendah + ucapkan bahwa sudah hadir
            playBeep(523, 0.12);
            setTimeout(function () {
                speakText(namaUcap + ' sudah hadir sebelumnya');
            }, 180);
        } else {
            // Hadir baru: bunyi "tit" lalu ucapkan nama siswa
            playBeep(1046, 0.12);
            setTimeout(function () {
                speakText(namaUcap + ' telah hadir');
            }, 180);
        }
    }

    function showPopup(p) {
        removePopup();
        playAttendanceSound(p);
        updateScanResultPanel(p);

        var statusConfig = {
            hadir: { badge: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500', label: 'Hadir' },
            sudah: { badge: 'bg-slate-100 text-slate-600', dot: '', label: 'Sudah hadir pukul' }
        };
        var cfg = statusConfig[p.status] || statusConfig.hadir;

        var overlay = document.createElement('div');
        overlay.id = 'popup-overlay';
        overlay.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';

        var card = document.createElement('div');
        card.className = 'bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center transform transition-all';

        if (p.status === 'sudah') {
            var warnWrap = document.createElement('div');
            warnWrap.className = 'w-14 h-14 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4';
            warnWrap.innerHTML = '<svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            card.appendChild(warnWrap);
        }

        if (p.foto_url) {
            var avatarWrap = document.createElement('div');
            avatarWrap.className = 'mx-auto mb-4 border-2 border-slate-200';
            avatarWrap.style.cssText = 'width:96px; height:96px; max-width:96px; max-height:96px; border-radius:9999px; overflow:hidden; flex-shrink:0; box-sizing:border-box;';

            var avatar = document.createElement('img');
            avatar.src = p.foto_url;
            avatar.alt = 'Foto ' + (p.nama || 'siswa');
            avatar.width = 96;
            avatar.height = 96;
            avatar.style.cssText = 'width:96px; height:96px; max-width:96px; max-height:96px; object-fit:cover; display:block;';
            avatar.onerror = function () {
                var fallback = document.createElement('div');
                fallback.className = 'w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-3xl font-semibold mx-auto mb-4';
                fallback.textContent = (p.nama || '?').charAt(0).toUpperCase();
                avatarWrap.replaceWith(fallback);
            };
            avatarWrap.appendChild(avatar);
            card.appendChild(avatarWrap);
        } else {
            var avatar = document.createElement('div');
            avatar.className = 'w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-3xl font-semibold mx-auto mb-4';
            avatar.textContent = (p.nama || '?').charAt(0).toUpperCase();
            card.appendChild(avatar);
        }

        var nama = document.createElement('h3');
        nama.className = 'text-xl font-bold text-slate-800';
        nama.textContent = p.nama;
        card.appendChild(nama);

        var nis = document.createElement('p');
        nis.className = 'text-xs font-mono text-slate-400 mt-1';
        nis.textContent = 'NIS ' + (p.nis || '');
        card.appendChild(nis);

        var kelas = document.createElement('p');
        kelas.className = 'text-sm text-slate-500 mt-0.5';
        kelas.textContent = p.kelas || '';
        card.appendChild(kelas);

        // Informasi lokasi absen yang dipilih saat barcode berhasil diproses.
        if (p.location_name) {
            var lokasi = document.createElement('p');
            lokasi.className = 'text-sm text-slate-600 mt-1.5 font-medium';
            lokasi.textContent = 'Lokasi: ' + p.location_name;
            card.appendChild(lokasi);
        }

        var badgeWrap = document.createElement('div');
        badgeWrap.className = 'mt-4';
        var badge = document.createElement('span');
        badge.className = 'inline-flex items-center gap-1.5 text-sm font-medium px-3 py-1.5 rounded-full ' + cfg.badge;
        if (cfg.dot) {
            var dot = document.createElement('span');
            dot.className = 'w-2 h-2 rounded-full ' + cfg.dot;
            badge.appendChild(dot);
        }
        badge.appendChild(document.createTextNode(' ' + cfg.label + ' · ' + p.jam));
        badgeWrap.appendChild(badge);
        card.appendChild(badgeWrap);

        overlay.appendChild(card);
        document.body.appendChild(overlay);

        setTimeout(function () {
            overlay.style.transition = 'opacity 0.3s';
            overlay.style.opacity = '0';
            setTimeout(function () { overlay.remove(); }, 300);
        }, 2500);
    }


    // QR/barcode kartu berisi Nama + NIS + Kelas.
    // Sistem selalu mengambil NIS sebagai identitas siswa.
    function extractBarcodeNis(raw) {
        raw = String(raw || '').trim();
        if (!raw) return '';

        try {
            var payload = JSON.parse(raw);
            if (payload && payload.nis !== undefined && String(payload.nis).trim()) {
                return String(payload.nis).trim();
            }
        } catch (e) {
            // Bukan JSON: anggap isi scanner langsung berupa NIS.
        }

        return raw;
    }

    // Kalau ada scan baru masuk saat request sebelumnya masih diproses server
    // (jaringan lambat, dsb), NIS-nya tidak dibuang — ditampung di sini dan
    // otomatis diproses begitu request sebelumnya selesai. Supaya antrian siswa
    // di depan kamera tidak ada yang "kelewat" gara-gara nge-scan pas sistem sibuk.
    var pendingNis = null;

    function processNis(nis) {
        hideError();
        busy = true;
        if (submitBtn) submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                nis: nis,
                location_id: locationSelect.value || sessionStorage.getItem(LOCATION_STORAGE_KEY) || null
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.popup) {
                // Backend menerima location_id. Untuk menampilkan nama lokasi
                // pada popup, ambil nama lokasi langsung dari dropdown yang dipilih.
                var selectedLocation = locationSelect.options[locationSelect.selectedIndex];
                data.popup.location_name = selectedLocation ? selectedLocation.textContent.trim() : '';
                showPopup(data.popup);
                loadRiwayat(data.popup.nis);
            } else if (!data.success) {
                showError(data.message || 'NIS tidak terdaftar');
                playInvalidSound();
            }
        })
        .catch(function () {
            showError('Gagal terhubung ke server, coba lagi');
            playInvalidSound();
        })
        .finally(function () {
            input.value = '';
            // Tidak auto-focus lagi ke input NIS di sini — supaya keyboard
            // virtual tidak muncul berulang tiap habis satu siswa discan;
            // scan berikutnya tetap otomatis mengisi field lewat kamera.
            busy = false;
            if (submitBtn) submitBtn.disabled = false;
            // Tetap di halaman scan setelah proses selesai.
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Kalau selama proses tadi ada scan lain yang masuk, langsung
            // lanjutkan tanpa perlu siswa itu discan ulang.
            if (pendingNis) {
                var next = pendingNis;
                pendingNis = null;
                processNis(next);
            }
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var rawBarcode = input.value.trim();
        var nis = extractBarcodeNis(rawBarcode);
        if (!nis) {
            input.focus();
            return;
        }

        if (!locationSelect.value) {
            showError('Pilih lokasi hadir dulu');
            locationSelect.focus();
            return;
        }

        if (busy) {
            pendingNis = nis;
            input.value = '';
            return;
        }

        processNis(nis);
    });

    // ==== Kamera langsung aktif otomatis saat halaman dibuka (inline, bukan modal) ====
    var cameraStatus = document.getElementById('camera-status');
    var cameraLoading = document.getElementById('camera-loading');
    var cameraDeviceWrap = document.getElementById('camera-device-wrap');
    var cameraDeviceSelect = document.getElementById('camera-device-select');
    var videoEl = document.getElementById('camera-preview');
    var zxingReader = null;
    var lastScanValue = '';
    var lastScanTime = 0;

    var topbarStatusDot = document.getElementById('topbar-status-dot');
    var topbarStatusText = document.getElementById('topbar-status-text');
    var cameraStatusChip = document.getElementById('camera-status-chip');
    var cameraStatusChipText = document.getElementById('camera-status-chip-text');

    function setCameraOnlineUI(isOnline, text) {
        if (topbarStatusText) topbarStatusText.textContent = text;
        if (topbarStatusDot) topbarStatusDot.classList.toggle('is-live', !!isOnline);
        if (cameraStatusChip) cameraStatusChip.classList.toggle('is-on', !!isOnline);
        if (cameraStatusChipText) cameraStatusChipText.textContent = 'Camera: ' + (isOnline ? 'ON' : 'OFF');
    }

    // Batasi decoder cuma coba format QR (bukan semua format barcode kayak
    // EAN/Code128/DataMatrix) supaya tiap frame diproses lebih cepat —
    // kartu pelajar di sini memang selalu QR (lihat SiswaController::buildQrPng).
    // TRY_HARDER dinyalakan supaya QR yang agak buram/miring/kurang fokus tetap
    // kebaca — aman dari sisi kecepatan karena format sudah dibatasi cuma QR.
    function buildFastQrHints() {
        try {
            var hints = new Map();
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.QR_CODE]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
            return hints;
        } catch (e) {
            return undefined;
        }
    }

    function loadZXing(callback) {
        if (window.ZXing && window.ZXing.BrowserMultiFormatReader) {
            callback();
            return;
        }

        var sources = [
            'https://cdn.jsdelivr.net/npm/@zxing/library@0.20.0',
            'https://unpkg.com/@zxing/library@0.20.0'
        ];

        function tryLoad(index) {
            if (index >= sources.length) {
                cameraStatus.textContent = 'Gagal memuat library scanner (cek koneksi internet)';
                setCameraOnlineUI(false, 'Gagal memuat scanner');
                return;
            }

            var script = document.createElement('script');
            script.src = sources[index];
            script.onload = function () {
                if (window.ZXing && window.ZXing.BrowserMultiFormatReader) {
                    callback();
                } else {
                    tryLoad(index + 1);
                }
            };
            script.onerror = function () {
                tryLoad(index + 1);
            };
            document.head.appendChild(script);
        }

        tryLoad(0);
    }

    // Constraints kamera resolusi lebih tinggi + autofocus kontinu (kalau device/
    // browser mendukung) supaya gambar QR lebih tajam dan cepat kebaca, terutama
    // saat kamera murah/kurang fokus. "advanced" diabaikan begitu saja oleh browser
    // yang tidak mendukungnya, jadi aman dipasang di semua device.
    function buildCameraConstraints(deviceId) {
        var video = {
            width: { ideal: 1280 },
            height: { ideal: 720 },
            advanced: [{ focusMode: 'continuous' }]
        };
        if (deviceId) {
            video.deviceId = { exact: deviceId };
        } else {
            video.facingMode = 'environment';
        }
        return { video: video, audio: false };
    }

    function handleDecodeResult(result) {
        if (!result) return;

        var text = result.getText();
        var nis = extractBarcodeNis(text);
        var now = Date.now();
        if (nis === lastScanValue && (now - lastScanTime) < 4000) return;

        lastScanValue = nis;
        lastScanTime = now;

        var detectedLabel = nis;
        try {
            var payload = JSON.parse(text);
            if (payload && payload.nama) {
                detectedLabel = payload.nama + (payload.nis ? ' | NIS ' + payload.nis : '');
            }
        } catch (e) {}

        cameraStatus.textContent = '✓ Terdeteksi: ' + detectedLabel;
        input.value = nis;

        if (form.requestSubmit) {
            form.requestSubmit();
        } else {
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    }

    function onCameraReady() {
        cameraLoading.style.display = 'none';
        cameraStatus.textContent = 'Siapkan kartu pelajar anda terlebih dahulu.';
        setCameraOnlineUI(true, 'Kamera aktif · Siap untuk scan');
    }

    function onCameraError(err) {
        cameraLoading.style.display = 'none';
        var msg = describeCameraError(err);
        cameraStatus.textContent = msg;
        setCameraOnlineUI(false, 'Kamera bermasalah');
    }

    function decodeWithDevice(deviceId) {
        cameraLoading.style.display = 'flex';
        cameraStatus.textContent = 'Menyalakan kamera…';
        setCameraOnlineUI(false, 'Menyalakan kamera…');

        // decodeFromConstraints memberi kontrol resolusi & autofocus (lebih tajam,
        // lebih cepat kebaca, lebih tahan gambar kurang jelas). Kalau browser/versi
        // ZXing tidak mendukungnya, fallback otomatis ke decodeFromVideoDevice
        // seperti sebelumnya supaya kamera tetap jalan.
        if (typeof zxingReader.decodeFromConstraints === 'function') {
            zxingReader.decodeFromConstraints(
                buildCameraConstraints(deviceId || null),
                videoEl,
                function (result) { handleDecodeResult(result); }
            ).then(onCameraReady).catch(function (err) {
                zxingReader.decodeFromVideoDevice(deviceId || null, videoEl, function (result) {
                    handleDecodeResult(result);
                }).then(onCameraReady).catch(onCameraError);
            });
        } else {
            zxingReader.decodeFromVideoDevice(deviceId || null, videoEl, function (result) {
                handleDecodeResult(result);
            }).then(onCameraReady).catch(onCameraError);
        }
    }

    function describeCameraError(err) {
        var name = err && err.name ? err.name : '';

        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            return 'Izin kamera ditolak. Klik ikon kamera/gembok di address bar browser, ubah izin kamera jadi "Allow", lalu refresh halaman.';
        }
        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            return 'Kamera tidak ditemukan. Pastikan laptop/HP punya webcam dan sudah terpasang.';
        }
        if (name === 'NotReadableError' || name === 'TrackStartError') {
            return 'Kamera sedang dipakai aplikasi lain (Zoom, Teams, dll). Tutup aplikasi itu lalu refresh halaman.';
        }
        if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
            return 'Kamera tidak mendukung pengaturan yang diminta. Coba pilih kamera lain di dropdown.';
        }
        if (name === 'SecurityError') {
            return 'Akses kamera diblokir karena koneksi tidak aman. Buka lewat https:// atau localhost/127.0.0.1.';
        }
        return 'Tidak bisa akses kamera' + (err && err.message ? ': ' + err.message : '') + '. Cek izin kamera di browser & Windows.';
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            cameraStatus.textContent = 'Browser ini tidak mendukung akses kamera. Gunakan Chrome/Edge versi terbaru.';
            setCameraOnlineUI(false, 'Browser tidak didukung');
            return;
        }

        var isSecure = window.isSecureContext ||
            location.protocol === 'https:' ||
            location.hostname === 'localhost' ||
            location.hostname === '127.0.0.1';

        if (!isSecure) {
            cameraStatus.textContent = 'Akses kamera perlu koneksi aman (https:// atau localhost/127.0.0.1). URL saat ini: ' + location.origin;
            setCameraOnlineUI(false, 'Koneksi tidak aman');
            return;
        }

        cameraStatus.textContent = 'Meminta izin kamera…';
        setCameraOnlineUI(false, 'Meminta izin kamera…');

        loadZXing(function () {
            try {
                zxingReader = new ZXing.BrowserMultiFormatReader(buildFastQrHints(), 1);

                zxingReader.listVideoInputDevices().then(function (devices) {
                    if (devices && devices.length > 1) {
                        cameraDeviceSelect.innerHTML = '';
                        devices.forEach(function (d, i) {
                            var opt = document.createElement('option');
                            opt.value = d.deviceId;
                            opt.textContent = d.label || ('Kamera ' + (i + 1));
                            cameraDeviceSelect.appendChild(opt);
                        });
                        var backCam = devices.find(function (d) {
                            return /back|belakang|rear|environment/i.test(d.label);
                        });
                        if (backCam) cameraDeviceSelect.value = backCam.deviceId;
                        cameraDeviceWrap.style.display = 'block';
                    } else {
                        cameraDeviceWrap.style.display = 'none';
                    }

                    decodeWithDevice(cameraDeviceSelect.value || null);
                }).catch(function (err) {
                    decodeWithDevice(null);
                });
            } catch (err) {
                cameraLoading.style.display = 'none';
                cameraStatus.textContent = 'Terjadi kesalahan saat menyiapkan scanner: ' + (err && err.message ? err.message : 'unknown error');
                setCameraOnlineUI(false, 'Kesalahan scanner');
            }
        });
    }

    function stopCamera() {
        if (zxingReader) {
            try { zxingReader.reset(); } catch (e) {}
        }
        setCameraOnlineUI(false, 'Kamera dijeda (layar terkunci)');
    }

    cameraDeviceSelect.addEventListener('change', function () {
        if (zxingReader) {
            try { zxingReader.reset(); } catch (e) {}
        }
        decodeWithDevice(cameraDeviceSelect.value);
    });

    // Matikan kamera dengan rapi kalau halaman ditinggalkan (hemat resource & baterai)
    window.addEventListener('beforeunload', function () {
        if (zxingReader) {
            try { zxingReader.reset(); } catch (e) {}
        }
    });

    // ================== Jam & tanggal kecil di topbar ==================
    

    // ================== Fullscreen toggle (mode kios) ==================
    var fullscreenBtn = document.getElementById('fullscreen-toggle-btn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function () {});
            } else {
                document.exitFullscreen().catch(function () {});
            }
        });
    }

    // ================== Mode layout: sempit (1 kolom) vs lebar/kios (2 kolom, tanpa scroll) ==================
    // Fullscreen (F11 / tombol layar penuh) TIDAK mengubah lebar CSS — itu cuma menyembunyikan
    // chrome browser. Lebar efektif tetap tergantung resolusi layar dibagi zoom browser, jadi kalau
    // AIO di-zoom, window.innerWidth bisa < 900 walau fisik layarnya lebar. Supaya mode kios selalu
    // dapat tampilan 2 kolom yang dirancang untuk itu, layout dipaksa "wide" begitu fullscreen aktif,
    // apa pun lebar CSS-nya saat itu.
    var scanWrap = document.getElementById('scan-fullscreen-wrap');
    function applyLayoutMode() {
        if (!scanWrap) return;
        var isFullscreen = !!document.fullscreenElement;
        var isWide = window.innerWidth >= 900 || isFullscreen;
        var isXlWide = window.innerWidth >= 1440;
        scanWrap.classList.toggle('layout-wide', isWide);
        scanWrap.classList.toggle('layout-wide-xl', isWide && isXlWide);
        // "is-fullscreen" cuma nandain status Fullscreen API beneran (buat kebutuhan lain kalau
        // ada). Penguncian tinggi/scroll halaman sekarang ikut "layout-wide" (lihat CSS), jadi
        // jendela biasa di layar lebar juga tampil identik dengan mode layar penuh.
        scanWrap.classList.toggle('is-fullscreen', isFullscreen);
    }
    applyLayoutMode();
    window.addEventListener('resize', applyLayoutMode);
    document.addEventListener('fullscreenchange', applyLayoutMode);

    // Kamera langsung nyala begitu halaman selesai dimuat
    startCamera();
})();
</script>


</div>
</body>
</html>
