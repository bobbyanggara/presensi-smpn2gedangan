<?php $title = 'Rekap Hari Ini'; $subtitle = \Carbon\Carbon::now()->translatedFormat('l, d F Y'); ?>
@include('partials.header')

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="font-semibold text-slate-800">Daftar Kehadiran</h3>
            <p id="rekap-count-text" class="text-sm text-slate-500">{{ $absensis->count() }} siswa sudah hadir</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('absensi.rekap') }}" class="flex items-center gap-3">
                <select name="kelas" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg pl-3 pr-8 py-2 brand-ring outline-none w-48">
                    <option value="">Semua kelas</option>
                    @foreach($kelasOptions as $option)
                        <option value="{{ $option }}" @selected($kelas === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="location_id" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg px-3 py-2 brand-ring outline-none w-48">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('absensi.rekap.export', request()->only(['kelas', 'location_id'])) }}"
               id="rekap-export-btn"
               class="inline-flex items-center gap-2 text-sm font-medium brand-bg text-white px-4 py-2 rounded-lg brand-bg-hover transition">
                <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                Export ke Excel
            </a>
        </div>
    </div>

    <p class="px-6 pt-3 text-xs text-slate-400">
        Export mengikuti filter kelas &amp; lokasi yang sedang aktif di atas.
    </p>


    <table class="w-full text-sm">
        <thead>
            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <th class="text-left font-medium px-6 py-3">Nama</th>
                <th class="text-left font-medium px-6 py-3">Kelas</th>
                <th class="text-left font-medium px-6 py-3">Lokasi</th>
                <th class="text-left font-medium px-6 py-3">Jam Masuk</th>
                <th class="text-left font-medium px-6 py-3">Status</th>
            </tr>
        </thead>
        <tbody id="rekap-tbody" class="divide-y divide-slate-100">
            @forelse($absensis as $absen)
            <tr class="hover:bg-slate-50/50 transition">
                <td class="px-6 py-3.5 font-medium text-slate-800">{{ $absen->siswa->nama }}</td>
                <td class="px-6 py-3.5 text-slate-600">{{ $absen->siswa->kelas }}</td>
                <td class="px-6 py-3.5 text-slate-600">{{ $absen->location->name ?? '-' }}</td>
                <td class="px-6 py-3.5 text-slate-600 font-mono">{{ $absen->jam_masuk }}</td>
                <td class="px-6 py-3.5">
                    <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 text-xs font-medium px-2.5 py-1 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Hadir</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">Belum ada siswa yang hadir hari ini</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
(function () {
    var tbody = document.getElementById('rekap-tbody');
    var countText = document.getElementById('rekap-count-text');
    var params = new URLSearchParams(window.location.search);
    var pollUrl = '{{ route("absensi.rekap.data") }}';
    var pollInterval = 5000; // ubah ke 3000 kalau mau lebih cepat
    var isTabVisible = true;

    // Cegah XSS: semua teks dari server dimasukkan lewat textContent, bukan innerHTML
    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function statusBadge() {
        var wrap = el('span', 'inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 text-xs font-medium px-2.5 py-1 rounded-full');
        var dot = el('span', 'w-1.5 h-1.5 rounded-full bg-emerald-500');
        wrap.appendChild(dot);
        wrap.appendChild(document.createTextNode(' Hadir'));
        return wrap;
    }

    function renderRows(rows) {
        tbody.innerHTML = '';

        if (!rows.length) {
            var tr = el('tr');
            var td = el('td', 'px-6 py-12 text-center text-slate-400', 'Belum ada siswa yang hadir hari ini');
            td.colSpan = 5;
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        rows.forEach(function (r) {
            var tr = el('tr', 'hover:bg-slate-50/50 transition');
            tr.appendChild(el('td', 'px-6 py-3.5 font-medium text-slate-800', r.nama));
            tr.appendChild(el('td', 'px-6 py-3.5 text-slate-600', r.kelas));
            tr.appendChild(el('td', 'px-6 py-3.5 text-slate-600', r.lokasi));
            tr.appendChild(el('td', 'px-6 py-3.5 text-slate-600 font-mono', r.jam_masuk));
            var tdStatus = el('td', 'px-6 py-3.5');
            tdStatus.appendChild(statusBadge());
            tr.appendChild(tdStatus);
            tbody.appendChild(tr);
        });
    }

    function refresh() {
        if (!isTabVisible) return; // hemat request kalau tab lagi nggak aktif

        fetch(pollUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                renderRows(data.rows);
                if (countText) countText.textContent = data.count + ' siswa sudah hadir';
            })
            .catch(function (err) {
                console.error('Gagal refresh rekap:', err);
            });
    }

    document.addEventListener('visibilitychange', function () {
        isTabVisible = !document.hidden;
        if (isTabVisible) refresh(); // langsung refresh begitu tab dibuka lagi
    });

    setInterval(refresh, pollInterval);
})();
</script>

@include('partials.footer')
