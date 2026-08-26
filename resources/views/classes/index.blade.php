<?php $title = 'Kelola Data Kelas'; $subtitle = 'Master data kelas, dipakai untuk filter dan pilihan kelas siswa'; ?>
@include('partials.header')

<div x-data="{ modalOpen: false, editing: null, form: { id: null, name: '', grade: '', status: true } }">

@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h3 class="font-semibold text-slate-800">Daftar Kelas</h3>
            <p class="text-sm text-slate-500">{{ $classes->total() }} kelas terdaftar</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button"
                @click="editing = null; form = { id: null, name: '', grade: '', status: true }; modalOpen = true"
                class="inline-flex items-center gap-1.5 text-sm font-medium brand-bg text-white px-4 py-2.5 rounded-xl brand-bg-hover transition">
                + Tambah Kelas
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('classes.index') }}" class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-3">
        <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama kelas..." class="flex-1 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm bg-white brand-ring outline-none">
        <button class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium">Cari</button>
        @if($search !== '')
            <a href="{{ route('classes.index') }}" class="text-sm text-slate-500 hover:text-slate-700 px-2 py-2.5">Reset</a>
        @endif
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                    <th class="text-left px-6 py-3">Nama Kelas</th>
                    <th class="text-left px-6 py-3">Tingkat</th>
                    <th class="text-left px-6 py-3">Jumlah Siswa</th>
                    <th class="text-left px-6 py-3">Status</th>
                    <th class="text-right px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($classes as $class)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-6 py-3 font-medium text-slate-800">{{ $class->name }}</td>
                    <td class="px-6 py-3 text-slate-600">{{ $class->grade ?: '-' }}</td>
                    <td class="px-6 py-3 text-slate-600">
                        <a href="{{ route('siswa.index', ['kelas' => $class->name]) }}" class="brand-text hover:underline">
                            {{ $class->students_count }} siswa
                        </a>
                    </td>
                    <td class="px-6 py-3">
                        @if($class->status)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Aktif</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <button type="button"
                            @click="editing = {{ $class->id }}; form = { id: {{ $class->id }}, name: @js($class->name), grade: @js($class->grade), status: {{ $class->status ? 'true' : 'false' }} }; modalOpen = true"
                            class="text-slate-500 brand-text-hover font-medium mr-3">Edit</button>
                        <form action="{{ route('classes.destroy', $class) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                onclick="return confirm({{ $class->students_count > 0 ? '\'Kelas ' . addslashes($class->name) . ' masih punya ' . $class->students_count . ' siswa. Hapus kelas ini akan ikut menghapus SEMUA siswa dan riwayat presensinya. Lanjutkan?\'' : '\'Yakin hapus kelas ' . addslashes($class->name) . '?\'' }})"
                                class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-14 text-center text-slate-400">
                    Belum ada data kelas.<br>
                    <span class="text-sm">Kelas akan otomatis muncul di sini begitu ada siswa dengan data kelas.</span>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($classes->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">{{ $classes->links() }}</div>
    @endif
</div>

<!-- Modal Tambah/Edit Kelas -->
<div x-show="modalOpen" x-cloak style="display:none" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div x-show="modalOpen" x-transition.opacity @click="modalOpen = false" class="absolute inset-0 bg-slate-900/50"></div>
    <div x-show="modalOpen" x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-semibold text-slate-800 mb-4" x-text="editing ? 'Edit Kelas' : 'Tambah Kelas'"></h3>
        <form :action="editing ? `{{ url('classes') }}/${editing}` : '{{ route('classes.store') }}'" method="POST" class="space-y-4">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Kelas</label>
                <input type="text" name="name" x-model="form.name" required maxlength="50" placeholder="IX A" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm brand-ring outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Tingkat (opsional)</label>
                <input type="text" name="grade" x-model="form.grade" maxlength="10" placeholder="IX" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm brand-ring outline-none transition">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" x-model="form.status" class="rounded border-slate-300 brand-ring">
                Kelas aktif
            </label>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="brand-bg text-white text-sm font-medium px-5 py-2.5 rounded-lg brand-bg-hover transition" x-text="editing ? 'Simpan Perubahan' : 'Tambah Kelas'"></button>
                <button type="button" @click="modalOpen = false" class="text-sm text-slate-500 hover:text-slate-700 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

</div>

@include('partials.footer')
