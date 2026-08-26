<?php
$title = 'Dashboard';
$subtitle = 'Ringkasan kehadiran hari ini';
$jam = (int) now()->format('H');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
$namaDepan = explode(' ', auth()->user()->name)[0];
?>
@include('partials.header')

<style>
    .dash-welcome{ background-image:linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-darker) 100%); border-radius:16px; padding:22px 24px; color:#fff; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .dash-welcome h2{ font-size:19px; font-weight:800; }
    .dash-welcome p{ font-size:13px; color:rgba(255,255,255,.7); margin-top:3px; }
    .dash-welcome .clock{ text-align:right; }
    .dash-welcome .clock .time{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:26px; font-weight:700; letter-spacing:.03em; font-variant-numeric:tabular-nums; }
    .dash-welcome .clock .date{ font-size:12px; color:rgba(255,255,255,.65); margin-top:2px; }

    .dash-stats{ display:flex; flex-wrap:wrap; gap:16px; margin-bottom:20px; }
    .dash-stats > *{ flex:1 1 21%; min-width:210px; }
    .stat-card{ background:#fff; border:1px solid #e2e8f0; border-left:4px solid var(--stat-accent, #cbd5e1); border-radius:16px; padding:18px 20px; box-shadow:0 1px 2px rgba(0,0,0,.04); transition:box-shadow .15s, transform .15s; display:block; }
    .stat-card:hover{ box-shadow:0 8px 18px rgba(0,0,0,.08); transform:translateY(-2px); }
    .stat-card .row{ display:flex; align-items:center; justify-content:space-between; }
    .stat-card .lbl{ font-size:13px; font-weight:500; color:#64748b; }
    .stat-card .ico{ width:36px; height:36px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-card .val{ font-family:var(--font-display); font-size:28px; font-weight:800; color:#1e293b; margin-top:10px; }
    .stat-card .sub{ font-size:12px; color:#94a3b8; margin-top:4px; }

    .dash-grid{ display:flex; flex-wrap:wrap; gap:20px; align-items:flex-start; margin-bottom:20px; }
    .dash-grid > *:first-child{ flex:2 1 480px; }
    .dash-grid > *:last-child{ flex:1 1 280px; }

    .card{ background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden; }
    .card-head{ padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .card-head .title-row{ display:flex; align-items:center; gap:10px; }
    .card-head h3{ font-size:14.5px; font-weight:700; color:#1e293b; }
    .card-icon{ width:32px; height:32px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .card-link{ font-size:13px; font-weight:600; color:var(--brand-green-dark); text-decoration:none; white-space:nowrap; }
    .card-link:hover{ text-decoration:underline; }

    .loc-item{ padding:14px 20px; border-bottom:1px solid #f8fafc; }
    .loc-item:last-child{ border-bottom:none; }
    .loc-top{ display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
    .loc-name{ font-size:13.5px; font-weight:500; color:#334155; display:flex; align-items:center; gap:8px; }
    .loc-dot{ width:8px; height:8px; border-radius:999px; flex-shrink:0; }
    .loc-count{ font-size:13px; font-weight:700; color:#1e293b; }
    .loc-bar-track{ height:6px; border-radius:999px; background:#f1f5f9; overflow:hidden; }
    .loc-bar-fill{ height:100%; border-radius:999px; background:var(--brand-green); }
    .loc-empty{ padding:32px 20px; text-align:center; color:#94a3b8; font-size:13px; }

    .trend-wrap{ display:flex; align-items:flex-end; gap:10px; padding:20px; height:150px; }
    .trend-col{ flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; gap:8px; }
    .trend-bar{ width:100%; max-width:34px; border-radius:8px 8px 3px 3px; background:#e2e8f0; position:relative; min-height:4px; transition:height .3s; }
    .trend-bar.today{ background:var(--brand-green); }
    .trend-val{ font-size:11px; font-weight:600; color:#64748b; }
    .trend-val.today{ color:var(--brand-green-dark); }
    .trend-lbl{ font-size:11px; color:#94a3b8; margin-top:2px; }
    .trend-lbl.today{ color:var(--brand-green-dark); font-weight:600; }

    .avatar-sm{ width:32px; height:32px; border-radius:999px; background:#f1f5f9; color:#64748b; display:flex; align-items:center; justify-content:center; font-size:12.5px; font-weight:600; flex-shrink:0; }

    /* ===== Tabel Absen Terbaru ===== */
    .absen-table-wrap{ overflow-y:auto; overflow-x:auto; max-height:280px; }
    .absen-table{ width:100%; min-width:480px; font-size:13.5px; border-collapse:collapse; table-layout:fixed; }
    .absen-table col.col-siswa{ width:46%; }
    .absen-table col.col-lokasi{ width:20%; }
    .absen-table col.col-jam{ width:17%; }
    .absen-table col.col-status{ width:17%; }
    .absen-table thead th{
        position:sticky; top:0; z-index:1;
        background:#f8fafc; color:#64748b; font-size:11px; font-weight:600;
        text-transform:uppercase; letter-spacing:.04em; text-align:left;
        padding:10px 20px; border-bottom:1px solid #e2e8f0;
    }
    .absen-table thead th.col-jam, .absen-table thead th.col-status{ text-align:center; }
    .absen-table tbody td{ padding:10px 20px; vertical-align:middle; border-top:1px solid #f1f5f9; }
    .absen-table tbody tr:hover{ background:#fafbfc; }
    .absen-table .siswa-cell{ display:flex; align-items:center; gap:10px; min-width:0; }
    .absen-table .siswa-info{ min-width:0; }
    .absen-table .siswa-nama{ font-weight:500; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .absen-table .siswa-kelas{ font-size:11.5px; color:#94a3b8; margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .absen-table .lokasi-cell{ color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .absen-table .jam-cell{ color:#64748b; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; text-align:center; font-variant-numeric:tabular-nums; }
    .absen-table .status-cell{ text-align:center; }
    .absen-table .status-badge{ display:inline-flex; align-items:center; gap:6px; background:#ecfdf5; color:#047857; font-size:11.5px; font-weight:500; padding:4px 10px; border-radius:999px; white-space:nowrap; }
    .absen-table .status-dot{ width:6px; height:6px; border-radius:999px; background:#10b981; flex-shrink:0; }
</style>

<div class="dash-welcome">
    <div>
        <h2>{{ $sapaan }}, {{ $namaDepan }} 👋</h2>
        <p>Berikut ringkasan kehadiran siswa SMP Negeri 2 Gedangan hari ini</p>
    </div>
    <div class="clock">
        <div class="time" id="dash-jam">--:--:--</div>
        <div class="date" id="dash-tanggal">Memuat tanggal…</div>
    </div>
</div>
<script>
    (function () {
        var jamEl = document.getElementById('dash-jam');
        var tglEl = document.getElementById('dash-tanggal');
        function update() {
            var now = new Date();
            if (jamEl) jamEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            if (tglEl) tglEl.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) + ' · WIB';
        }
        update();
        setInterval(update, 1000);
    })();
</script>

<div class="dash-stats">
    <a href="{{ route('siswa.index') }}" class="stat-card" style="--stat-accent:#0ea5e9; text-decoration:none">
        <div class="row">
            <p class="lbl">Total Siswa</p>
            <div class="ico" style="background:#eff6ff;color:#0ea5e9;">
                <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></svg>
            </div>
        </div>
        <p class="val">{{ $totalSiswa }}</p>
        <p class="sub">Klik untuk lihat daftar siswa →</p>
    </a>

    <a href="{{ route('absensi.rekap') }}" class="stat-card" style="--stat-accent:#059669; text-decoration:none">
        <div class="row">
            <p class="lbl">Hadir Hari Ini</p>
            <div class="ico" style="background:#ecfdf5;color:#059669;">
                <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
        <p class="val" id="dash-hadir-value" style="color:#059669">{{ $jumlahHadir }}</p>
        <p class="sub" id="dash-hadir-sub">{{ $persentaseHadir }}% dari total siswa · Klik untuk lihat rekap →</p>
    </a>

</div>

<div style="margin-bottom:20px">
    <div class="card">
        <div class="card-head">
            <div class="title-row">
                <div class="card-icon" style="background:#eff6ff;color:#0ea5e9;">
                    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2" /></svg>
                </div>
                <h3>Kehadiran 7 Hari Terakhir</h3>
            </div>
        </div>
        <div class="trend-wrap" id="dash-trend-wrap">
            @foreach($trenMingguan as $hari)
                <div class="trend-col">
                    <span class="trend-val {{ $hari['is_today'] ? 'today' : '' }}">{{ $hari['jumlah'] }}</span>
                    <div class="trend-bar {{ $hari['is_today'] ? 'today' : '' }}" style="height: {{ max(6, round(($hari['jumlah'] / $maxTren) * 90)) }}px"></div>
                    <span class="trend-lbl {{ $hari['is_today'] ? 'today' : '' }}">{{ $hari['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="dash-grid">
    <div class="card">
        <div class="card-head">
            <div class="title-row">
                <div class="card-icon" style="background:#ecfdf5;color:#059669;">
                    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3>Absensi Terbaru</h3>
            </div>
            <a href="{{ route('absensi.rekap') }}" class="card-link">Lihat semua →</a>
        </div>
        <div class="absen-table-wrap">
            <table class="absen-table">
                <colgroup>
                    <col class="col-siswa"><col class="col-lokasi"><col class="col-jam"><col class="col-status">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-siswa">Siswa</th>
                        <th class="col-lokasi">Lokasi</th>
                        <th class="col-jam">Jam</th>
                        <th class="col-status">Status</th>
                    </tr>
                </thead>
                <tbody id="dash-absen-tbody">
                    @forelse($absensiTerbaru as $absen)
                    <tr>
                        <td>
                            <div class="siswa-cell">
                                @if($absen->siswa && $absen->siswa->foto)
                                    <img src="{{ route('siswa.foto', $absen->siswa) }}" alt="Foto {{ $absen->siswa->nama }}" class="avatar-sm" style="object-fit:cover" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="avatar-sm" style="display:none">{{ strtoupper(substr($absen->siswa->nama ?? '?', 0, 1)) }}</div>
                                @else
                                    <div class="avatar-sm">{{ strtoupper(substr($absen->siswa->nama ?? '?', 0, 1)) }}</div>
                                @endif
                                <div class="siswa-info">
                                    <div class="siswa-nama">{{ $absen->siswa->nama ?? '-' }}</div>
                                    <div class="siswa-kelas">{{ $absen->siswa->kelas ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="lokasi-cell">{{ $absen->location->name ?? '-' }}</td>
                        <td class="jam-cell">{{ $absen->jam_masuk }}</td>
                        <td class="status-cell">
                            <span class="status-badge"><span class="status-dot"></span> Hadir</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="padding:56px 20px; text-align:center; color:#94a3b8">
                            <svg style="width:36px;height:36px;margin:0 auto 8px;color:#cbd5e1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Belum ada absensi hari ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="title-row">
                <div class="card-icon" style="background:#f5f3ff;color:#8b5cf6;">
                    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <h3>Kehadiran per Lokasi</h3>
            </div>
        </div>
        @php $dotColors = ['#0e7c3f', '#ffcc00', '#0ea5e9', '#a855f7', '#f97316']; @endphp
        <div id="dash-lokasi-list" data-dot-colors="{{ json_encode($dotColors) }}" data-total-siswa="{{ $totalSiswa }}">
        @forelse($lokasiStats as $i => $lok)
            <div class="loc-item">
                <div class="loc-top">
                    <span class="loc-name"><span class="loc-dot" style="background:{{ $dotColors[$i % count($dotColors)] }}"></span>{{ $lok['nama'] }}</span>
                    <span class="loc-count">{{ $lok['jumlah'] }}</span>
                </div>
                <div class="loc-bar-track">
                    <div class="loc-bar-fill" style="width: {{ $totalSiswa > 0 ? min(100, round(($lok['jumlah'] / $totalSiswa) * 100)) : 0 }}%; background:{{ $dotColors[$i % count($dotColors)] }}"></div>
                </div>
            </div>
        @empty
            <div class="loc-empty">Belum ada lokasi absensi aktif</div>
        @endforelse
        </div>
    </div>
</div>


<div id="dash-toast-container" style="position:fixed; top:16px; right:16px; z-index:9999; display:flex; flex-direction:column; gap:10px; width:320px; max-width:calc(100vw - 32px); pointer-events:none;"></div>

<style>
    .dash-toast{
        background:#ffffff; border:1px solid #e2e8f0; border-left:4px solid #10b981;
        border-radius:14px; box-shadow:0 10px 25px rgba(0,0,0,.12);
        padding:12px 14px; display:flex; align-items:center; gap:10px;
        pointer-events:auto; opacity:0; transform:translateX(24px);
        transition:opacity .25s ease, transform .25s ease;
    }
    .dash-toast.show{ opacity:1; transform:translateX(0); }
    .dash-toast .avatar-sm{ width:34px; height:34px; }
    .dash-toast .title{ font-size:13px; font-weight:600; color:#1e293b; line-height:1.25; }
    .dash-toast .desc{ font-size:11.5px; color:#64748b; margin-top:1px; }
</style>

<script>
(function () {
    var FEED_URL = @json(route('dashboard.feed'));
    var POLL_INTERVAL = 5000;

    var toastContainer = document.getElementById('dash-toast-container');
    var hadirValueEl = document.getElementById('dash-hadir-value');
    var hadirSubEl = document.getElementById('dash-hadir-sub');
    var trendWrap = document.getElementById('dash-trend-wrap');
    var absenTbody = document.getElementById('dash-absen-tbody');
    var lokasiList = document.getElementById('dash-lokasi-list');

    var dotColors = lokasiList ? JSON.parse(lokasiList.getAttribute('data-dot-colors') || '[]') : [];
    var totalSiswa = lokasiList ? parseInt(lokasiList.getAttribute('data-total-siswa') || '0', 10) : 0;

    // Id absen terakhir yang sudah pernah "dilihat", supaya begitu polling pertama
    // jalan kita tidak langsung menganggap semua data yang sudah ada sebagai baru.
    var lastSeenId = {{ (int) ($absensiTerbaru->max('id') ?? 0) }};
    var isFirstPoll = true;

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function initials(nama) {
        return (nama || '?').trim().charAt(0).toUpperCase();
    }

    function showToast(item) {
        var toast = document.createElement('div');
        toast.className = 'dash-toast';

        var avatarHtml = item.foto_url
            ? '<img src="' + escapeHtml(item.foto_url) + '" alt="Foto ' + escapeHtml(item.nama) + '" class="avatar-sm" style="object-fit:cover" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';"><div class="avatar-sm" style="display:none">' + escapeHtml(initials(item.nama)) + '</div>'
            : '<div class="avatar-sm">' + escapeHtml(initials(item.nama)) + '</div>';

        toast.innerHTML =
            avatarHtml +
            '<div style="min-width:0">' +
                '<div class="title">' + escapeHtml(item.nama) + ' baru saja absen</div>' +
                '<div class="desc">' + escapeHtml(item.kelas || '-') + ' · ' + escapeHtml(item.lokasi || '-') + ' · ' + escapeHtml(item.jam || '') + '</div>' +
            '</div>';

        toastContainer.appendChild(toast);
        // Trigger transisi masuk di frame berikutnya
        requestAnimationFrame(function () { toast.classList.add('show'); });

        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 250);
        }, 5000);
    }

    function renderHadir(data) {
        if (hadirValueEl) hadirValueEl.textContent = data.jumlah_hadir;
        if (hadirSubEl) hadirSubEl.textContent = data.persentase_hadir + '% dari total siswa · Klik untuk lihat rekap →';
    }

    function renderTrend(data) {
        if (!trendWrap) return;
        var maxTren = Math.max(1, data.max_tren);
        var html = '';
        data.tren_mingguan.forEach(function (hari) {
            var todayClass = hari.is_today ? ' today' : '';
            var height = Math.max(6, Math.round((hari.jumlah / maxTren) * 90));
            html +=
                '<div class="trend-col">' +
                    '<span class="trend-val' + todayClass + '">' + hari.jumlah + '</span>' +
                    '<div class="trend-bar' + todayClass + '" style="height: ' + height + 'px"></div>' +
                    '<span class="trend-lbl' + todayClass + '">' + escapeHtml(hari.label) + '</span>' +
                '</div>';
        });
        trendWrap.innerHTML = html;
    }

    function renderLokasi(data) {
        if (!lokasiList) return;
        if (!data.lokasi_stats.length) {
            lokasiList.innerHTML = '<div class="loc-empty">Belum ada lokasi absensi aktif</div>';
            return;
        }
        var html = '';
        data.lokasi_stats.forEach(function (lok, i) {
            var color = dotColors[i % dotColors.length] || '#0e7c3f';
            var pct = totalSiswa > 0 ? Math.min(100, Math.round((lok.jumlah / totalSiswa) * 100)) : 0;
            html +=
                '<div class="loc-item">' +
                    '<div class="loc-top">' +
                        '<span class="loc-name"><span class="loc-dot" style="background:' + color + '"></span>' + escapeHtml(lok.nama) + '</span>' +
                        '<span class="loc-count">' + lok.jumlah + '</span>' +
                    '</div>' +
                    '<div class="loc-bar-track">' +
                        '<div class="loc-bar-fill" style="width: ' + pct + '%; background:' + color + '"></div>' +
                    '</div>' +
                '</div>';
        });
        lokasiList.innerHTML = html;
    }

    function statusBadge() {
        return '<span class="status-badge"><span class="status-dot"></span> Hadir</span>';
    }

    function renderAbsenTbody(data) {
        if (!absenTbody) return;
        if (!data.absensi_terbaru.length) {
            absenTbody.innerHTML =
                '<tr><td colspan="4" style="padding:56px 20px; text-align:center; color:#94a3b8">' +
                    '<svg style="width:36px;height:36px;margin:0 auto 8px;color:#cbd5e1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>' +
                    'Belum ada yang absen hari ini' +
                '</td></tr>';
            return;
        }
        var html = '';
        data.absensi_terbaru.forEach(function (absen) {
            var avatarHtml = absen.foto_url
                ? '<img src="' + escapeHtml(absen.foto_url) + '" alt="Foto ' + escapeHtml(absen.nama) + '" class="avatar-sm" style="object-fit:cover" loading="lazy" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';"><div class="avatar-sm" style="display:none">' + escapeHtml(initials(absen.nama)) + '</div>'
                : '<div class="avatar-sm">' + escapeHtml(initials(absen.nama)) + '</div>';

            html +=
                '<tr>' +
                    '<td>' +
                        '<div class="siswa-cell">' +
                            avatarHtml +
                            '<div class="siswa-info">' +
                                '<div class="siswa-nama">' + escapeHtml(absen.nama) + '</div>' +
                                '<div class="siswa-kelas">' + escapeHtml(absen.kelas) + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td class="lokasi-cell">' + escapeHtml(absen.lokasi) + '</td>' +
                    '<td class="jam-cell">' + escapeHtml(absen.jam) + '</td>' +
                    '<td class="status-cell">' + statusBadge() + '</td>' +
                '</tr>';
        });
        absenTbody.innerHTML = html;
    }

    function poll() {
        fetch(FEED_URL, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
            .then(function (data) {
                // Cari absensi yang id-nya lebih baru dari yang terakhir kita lihat,
                // lalu tampilkan sebagai notifikasi toast (urut dari yang paling lama ke baru).
                if (!isFirstPoll) {
                    var newOnes = data.absensi_terbaru
                        .filter(function (item) { return item.id > lastSeenId; })
                        .sort(function (a, b) { return a.id - b.id; });
                    newOnes.forEach(showToast);
                }
                isFirstPoll = false;

                if (data.absensi_terbaru.length) {
                    lastSeenId = Math.max.apply(null, data.absensi_terbaru.map(function (i) { return i.id; }).concat(lastSeenId));
                }

                renderHadir(data);
                renderTrend(data);
                renderLokasi(data);
                renderAbsenTbody(data);
            })
            .catch(function () { /* diamkan saja, coba lagi di polling berikutnya */ });
    }

    poll();
    setInterval(poll, POLL_INTERVAL);
})();
</script>

@include('partials.footer')
