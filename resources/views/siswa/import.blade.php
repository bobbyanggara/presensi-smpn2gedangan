<?php $title = 'Import Data Siswa'; $subtitle = 'Import massal dari Excel dengan NIS, Nama, dan Kelas'; ?>
@include('partials.header')

<div class="max-w-6xl space-y-5">
    @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-4 text-sm">{{ session('success') }}</div>@endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div><h3 class="font-semibold text-slate-800">1. Siapkan Excel</h3><p class="text-sm text-slate-500">Gunakan tepat tiga kolom: NIS, Nama, Kelas.</p></div>
            <a href="{{ route('siswa.import.template') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-medium transition" style="background-color:#ffcc00; color:#4a3900" onmouseover="this.style.backgroundColor='#e6b800'" onmouseout="this.style.backgroundColor='#ffcc00'">Download Template Excel</a>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm border border-slate-100"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">NIS</th><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Kelas</th></tr></thead><tbody><tr><td class="px-4 py-3 font-mono">240001</td><td class="px-4 py-3">Contoh Nama Siswa</td><td class="px-4 py-3">IX A</td></tr></tbody></table></div>
        <p class="text-xs text-slate-500 mt-3">Jika NIS memiliki angka 0 di depan, format kolom NIS sebagai <b>Text</b> di Excel agar angka 0 tidak hilang.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 class="font-semibold text-slate-800 mb-1">2. Upload dan validasi</h3>
        <p class="text-sm text-slate-500 mb-5">Maksimal 5.000 baris per import. Data belum langsung disimpan sebelum kamu mengonfirmasi.</p>
        <form action="{{ route('siswa.import.preview') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 items-end">
            @csrf
            <div class="flex-1 w-full"><label class="block text-sm font-medium text-slate-700 mb-1.5">File Excel</label><input type="file" name="file" required accept=".xlsx,.xls,.csv,.txt" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm"></div>
            <button class="brand-bg text-white font-medium px-5 py-2.5 rounded-xl">Validasi & Preview</button>
        </form>
        @error('file')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
    </div>

    @php($summary = session('import_summary'))
    @if($summary)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-500">Total</p><p class="text-xl font-semibold text-slate-800">{{ $summary['total'] }}</p></div>
        <div class="bg-white border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-500">Data baru</p><p class="text-xl font-semibold text-emerald-600">{{ $summary['new'] }}</p></div>
        <div class="bg-white border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-500">Diperbarui</p><p class="text-xl font-semibold text-blue-600">{{ $summary['update'] }}</p></div>
        <div class="bg-white border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-500">Masalah</p><p class="text-xl font-semibold {{ $summary['errors'] ? 'text-red-600' : 'text-slate-800' }}">{{ $summary['errors'] }}</p></div>
    </div>
    @endif

    @if($preview)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100"><h3 class="font-semibold text-slate-800">3. Preview</h3><p class="text-sm text-slate-500">{{ count($rows) }} baris valid, {{ count($importErrors) }} masalah. Saat konfirmasi, hanya data yang valid yang akan diimpor.</p></div>
        @if($importErrors)<div class="m-5 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm max-h-52 overflow-auto"><p class="font-semibold mb-2">Ada data yang bermasalah dan akan dilewati:</p><ul class="list-disc pl-5 space-y-1">@foreach($importErrors as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="overflow-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Baris</th><th class="px-4 py-3 text-left">NIS</th><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Kelas</th><th class="px-4 py-3 text-left">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        @foreach(array_slice($rows, 0, 100) as $row)<tr><td class="px-4 py-3">{{ $row['_line'] }}</td><td class="px-4 py-3 font-mono">{{ $row['nis'] }}</td><td class="px-4 py-3">{{ $row['nama'] }}</td><td class="px-4 py-3">{{ $row['kelas'] }}</td><td class="px-4 py-3"><span class="text-xs font-semibold px-2 py-1 rounded-full {{ $row['_action'] === 'UPDATE' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $row['_action'] }}</span></td></tr>@endforeach
        </tbody></table></div>
        @if(count($rows) > 100)<p class="px-5 py-3 text-xs text-slate-500">Menampilkan 100 baris pertama. Seluruh {{ count($rows) }} baris akan diproses saat konfirmasi.</p>@endif
        <div class="p-5">
            <div id="import-progress-wrap" class="hidden mb-4">
                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                    <div id="import-progress-bar" class="bg-emerald-600 h-3 transition-all duration-300" style="width:0%"></div>
                </div>
                <p id="import-progress-text" class="text-sm text-slate-600 mt-2">Memproses 0 dari 0 siswa...</p>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('siswa.import') }}" id="import-cancel-link" class="border border-slate-200 text-slate-700 font-medium px-5 py-2.5 rounded-xl">Batalkan</a>
                <button type="button" id="btn-confirm-import" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2.5 rounded-xl">Konfirmasi Import</button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
(function () {
    const btn = document.getElementById('btn-confirm-import');
    if (!btn) return;

    const wrap = document.getElementById('import-progress-wrap');
    const bar = document.getElementById('import-progress-bar');
    const text = document.getElementById('import-progress-text');
    const cancelLink = document.getElementById('import-cancel-link');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const processUrl = "{{ route('siswa.import.process') }}";
    const indexUrl = "{{ route('siswa.index') }}";

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        cancelLink.classList.add('pointer-events-none', 'opacity-40');
        wrap.classList.remove('hidden');

        let offset = 0;
        const limit = 250;

        try {
            while (true) {
                const res = await fetch(processUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ offset, limit }),
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Import gagal.');

                const total = data.total;
                offset = data.processed;
                const pct = total > 0 ? Math.round((offset / total) * 100) : 100;
                bar.style.width = pct + '%';
                text.textContent = 'Memproses ' + offset.toLocaleString('id-ID') + ' dari ' + total.toLocaleString('id-ID') + ' siswa...';

                if (data.done) {
                    text.textContent = 'Selesai! ' + total.toLocaleString('id-ID') + ' data valid berhasil diproses. Data yang bermasalah dilewati. Mengalihkan...';
                    setTimeout(() => { window.location.href = indexUrl; }, 800);
                    break;
                }
            }
        } catch (err) {
            text.textContent = 'Gagal: ' + err.message;
            text.classList.add('text-red-600');
            btn.disabled = false;
            cancelLink.classList.remove('pointer-events-none', 'opacity-40');
        }
    });
})();
</script>
@include('partials.footer')
