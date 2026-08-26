<?php $title = 'Data Siswa'; $subtitle = 'Kelola data NIS, nama, dan kelas'; ?>
@include('partials.header')

@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex flex-col gap-3">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h3 class="font-semibold text-slate-800">Daftar Siswa</h3>
                <p class="text-sm text-slate-500">{{ $siswas->total() }} siswa terdaftar</p>
            </div>
            <div class="flex items-center gap-1.5">
                <a href="{{ route('siswa.import') }}" class="inline-flex items-center gap-1.5 text-sm font-medium px-4 py-2.5 rounded-xl transition" style="background-color:#ffcc00; color:#4a3900" onmouseover="this.style.backgroundColor='#e6b800'" onmouseout="this.style.backgroundColor='#ffcc00'">Import Massal</a>
                <a href="{{ route('siswa.create') }}" class="inline-flex items-center gap-1.5 text-sm font-medium brand-bg text-white px-4 py-2.5 rounded-xl brand-bg-hover transition">+ Tambah Siswa</a>
            </div>
        </div>

        <div class="flex flex-wrap items-start gap-x-8 gap-y-3 pt-1">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Data Siswa (Excel)</p>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('siswa.export', array_filter(['search' => $search, 'kelas' => $kelas])) }}" class="inline-flex items-center gap-1.5 text-sm font-medium border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl hover:bg-slate-50 transition">
                        {{ ($search !== '' || $kelas !== '') ? 'Export Data (Sesuai Filter)' : 'Export Data Siswa' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @if($search !== '' || $kelas !== '')
        <div class="px-6 py-2 bg-amber-50 border-b border-amber-100 text-xs text-amber-700">
            Tombol export di atas hanya akan mengambil siswa sesuai filter yang aktif saat ini{{ $kelas !== '' ? " (kelas: {$kelas})" : '' }}{{ $search !== '' ? " (cari: \"{$search}\")" : '' }}. Untuk generate QR presensi, buka halaman <a href="{{ route('siswa.export-qr') }}" class="underline font-medium">QR Presensi</a>.
        </div>
    @endif

    <form method="GET" action="{{ route('siswa.index') }}" class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-3">
        <input type="search" name="search" value="{{ $search }}" placeholder="Cari NIS atau nama..." class="border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm bg-white brand-ring outline-none w-full sm:w-56">
        <select name="kelas" onchange="this.form.submit()" class="border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm bg-white brand-ring outline-none w-full sm:w-36">
            <option value="">Semua kelas</option>
            @foreach($kelasOptions as $option)
                <option value="{{ $option }}" @selected($kelas === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium">Cari</button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide"><th class="text-left px-6 py-3">No</th><th class="text-left px-6 py-3">Foto</th><th class="text-left px-6 py-3">NIS</th><th class="text-left px-6 py-3">Nama</th><th class="text-left px-6 py-3">Kelas</th><th class="text-right px-6 py-3">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($siswas as $siswa)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-6 py-3 text-slate-400">{{ $siswas->firstItem() + $loop->index }}</td>
                    <td class="px-6 py-3">
                        @if($siswa->foto)
                            <img src="{{ route('siswa.foto', $siswa) }}" alt="Foto {{ $siswa->nama }}" class="w-10 h-10 rounded-full object-cover border border-slate-200" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 items-center justify-center text-sm font-semibold" style="display:none">{{ strtoupper(substr($siswa->nama, 0, 1)) }}</div>
                        @else
                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-sm font-semibold">{{ strtoupper(substr($siswa->nama, 0, 1)) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-3 font-mono text-slate-700">{{ $siswa->nis }}</td>
                    <td class="px-6 py-3 font-medium text-slate-800 whitespace-nowrap">{{ $siswa->nama }}</td>
                    <td class="px-6 py-3 text-slate-600">{{ $siswa->kelas }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('siswa.edit', $siswa) }}" class="text-slate-500 brand-text-hover font-medium mr-3">Edit</a>
                        <form action="{{ route('siswa.destroy', $siswa) }}" method="POST" class="inline">@csrf @method('DELETE')<button type="submit" onclick="return confirm('Yakin hapus data ini?')" class="text-red-500 hover:text-red-700 font-medium">Hapus</button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-14 text-center text-slate-400">Belum ada data siswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($siswas->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">{{ $siswas->links() }}</div>
    @endif
</div>

@include('partials.footer')
