<?php $title = 'QR Presensi'; $subtitle = 'Generate dan unduh QR code untuk presensi siswa'; ?>
@include('partials.header')

@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Generate QR Presensi</h3>
        <p class="text-sm text-slate-500">Pilih kelas atau cari nama/NIS, lalu unduh QR barcode dalam format PNG (satu file per siswa) atau PDF (kartu QR gabungan).</p>
    </div>

    <form method="GET" action="{{ route('siswa.export-qr') }}" class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-3">
        <input type="search" name="search" value="{{ $search }}" placeholder="Cari NIS atau nama..." class="border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm bg-white brand-ring outline-none w-full sm:w-56">
        <select name="kelas" onchange="this.form.submit()" class="border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm bg-white brand-ring outline-none w-full sm:w-36">
            <option value="">Semua kelas</option>
            @foreach($kelasOptions as $option)
                <option value="{{ $option }}" @selected($kelas === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium">Cari</button>
        @if($search !== '' || $kelas !== '')
            <a href="{{ route('siswa.export-qr') }}" class="text-sm text-slate-500 hover:text-slate-700 px-2 py-2.5 whitespace-nowrap">Reset filter</a>
        @endif
    </form>

    <div class="p-6 space-y-5">
        <div class="rounded-xl border border-slate-200 p-5 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-sm font-medium text-slate-800">
                    {{ $matchedCount }} siswa cocok dengan filter saat ini
                    @if($kelas !== '' || $search !== '')
                        <span class="text-slate-400 font-normal">({{ $kelas !== '' ? "kelas: {$kelas}" : 'semua kelas' }}{{ $search !== '' ? ", cari: \"{$search}\"" : '' }})</span>
                    @endif
                </p>
                @if($totalWithoutNis > 0)
                    <p class="text-xs text-amber-600 mt-1">{{ $totalWithoutNis }} siswa di seluruh data belum memiliki NIS sehingga tidak akan muncul di QR manapun sebelum NIS diisi.</p>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="downloadAllQrPng(this)" class="inline-flex items-center gap-1.5 text-sm font-medium bg-emerald-600 text-white px-4 py-2.5 rounded-xl hover:bg-emerald-700 transition" @disabled($matchedCount === 0)>
                    Unduh PNG ({{ $matchedCount }})
                </button>
                <a href="{{ route('siswa.barcode.pdf.all', array_filter(['search' => $search, 'kelas' => $kelas])) }}" target="_blank" rel="noopener" id="pdf-all-btn" class="inline-flex items-center gap-1.5 text-sm font-medium px-4 py-2.5 rounded-xl transition{{ $matchedCount === 0 ? ' opacity-50 pointer-events-none' : '' }}" style="background-color:#ffcc00; color:#4a3900" onmouseover="if(!this.classList.contains('pointer-events-none')) this.style.backgroundColor='#e6b800'" onmouseout="this.style.backgroundColor='#ffcc00'">
                    Unduh PDF Gabungan
                </a>
            </div>
        </div>

        @if($matchedCount === 0 && $matchedSiswas->isEmpty())
            <p class="text-sm text-slate-400 text-center py-6">Tidak ada siswa yang cocok dengan filter ini. Ubah filter atau lengkapi NIS siswa terlebih dahulu di <a href="{{ route('siswa.index') }}" class="brand-text hover:underline">Data Siswa</a>.</p>
        @endif
    </div>

    @if($matchedSiswas->isNotEmpty())
    <div class="border-t border-slate-100">
        <div class="px-6 pt-5 pb-1">
            <h4 class="text-sm font-semibold text-slate-700">Daftar siswa sesuai filter</h4>
            <p class="text-xs text-slate-400 mt-0.5">Siswa yang belum punya NIS tetap ditampilkan di sini, tapi tidak ikut terunduh sampai NIS-nya dilengkapi.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                        <th class="text-left px-6 py-3">No</th>
                        <th class="text-left px-6 py-3">NIS</th>
                        <th class="text-left px-6 py-3">Nama</th>
                        <th class="text-left px-6 py-3">Kelas</th>
                        <th class="text-left px-6 py-3">Status QR</th>
                        <th class="text-right px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($matchedSiswas as $siswa)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="px-6 py-3 text-slate-400">{{ $matchedSiswas->firstItem() + $loop->index }}</td>
                        <td class="px-6 py-3 font-mono text-slate-700">{{ $siswa->nis ?: '-' }}</td>
                        <td class="px-6 py-3 font-medium text-slate-800 whitespace-nowrap">{{ $siswa->nama }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $siswa->kelas }}</td>
                        <td class="px-6 py-3">
                            @if(filled($siswa->nis))
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Siap</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Isi NIS dulu</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            @if(filled($siswa->nis))
                                <a href="{{ route('siswa.barcode.png', $siswa) }}" class="text-emerald-600 hover:text-emerald-800 font-medium mr-3" title="Unduh QR Code PNG">QR PNG</a>
                                <a href="{{ route('siswa.barcode.pdf', $siswa) }}" target="_blank" rel="noopener" class="font-medium brand-red-text hover:opacity-75" title="Buka & cetak QR sebagai PDF">QR PDF</a>
                            @else
                                <span class="text-slate-400" title="Isi NIS terlebih dahulu di Data Siswa">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($matchedSiswas->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $matchedSiswas->links() }}</div>
        @endif
    </div>
    @endif
</div>

<script>
async function downloadAllQrPng(button) {
    const originalText = button?.textContent || 'Unduh PNG';
    if (button) {
        button.disabled = true;
        button.textContent = 'Menyiapkan QR...';
    }

    try {
        const response = await fetch('{{ route('siswa.barcode.png.all', array_filter(['search' => $search, 'kelas' => $kelas])) }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        });

        const contentType = response.headers.get('content-type') || '';
        if (!response.ok || !contentType.includes('application/json')) {
            throw new Error('Server tidak mengembalikan daftar QR. Pastikan Anda sudah login.');
        }

        const data = await response.json();
        if (!data.items?.length) {
            alert('Tidak ada siswa dengan NIS yang dapat dibuatkan QR.');
            return;
        }

        for (let i = 0; i < data.items.length; i++) {
            const item = data.items[i];
            if (button) button.textContent = `QR ${i + 1}/${data.items.length}...`;

            const qrResponse = await fetch(item.url, {
                headers: { 'Accept': 'image/png' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const qrType = qrResponse.headers.get('content-type') || '';
            if (!qrResponse.ok || !qrType.includes('image/png')) {
                throw new Error(`QR ${item.nis} gagal dibuat.`);
            }

            const blob = await qrResponse.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = `qr-${item.nis}.png`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);

            // Jeda kecil membantu Chrome memproses download beruntun.
            await new Promise(resolve => setTimeout(resolve, 350));
        }

        alert(`${data.count} file PNG QR siswa selesai dikirim ke download. Jika Chrome meminta izin multiple downloads, pilih Allow/Izinkan.`);
    } catch (error) {
        console.error(error);
        alert(error.message || 'Gagal mengunduh QR PNG semua siswa.');
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = originalText;
        }
    }
}

</script>

@include('partials.footer')
