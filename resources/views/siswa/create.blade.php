<?php $title = 'Tambah Siswa'; $subtitle = 'Isi NIS, nama, dan kelas siswa'; ?>
@include('partials.header')

<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('siswa.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">NIS</label>
                <input type="text" name="nis" value="{{ old('nis') }}" required maxlength="30" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm font-mono brand-ring outline-none transition">
                @error('nis') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama') }}" required maxlength="150" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm brand-ring outline-none transition">
                @error('nama') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelas</label>
                <input type="text" name="kelas" value="{{ old('kelas') }}" required maxlength="50" placeholder="IX A" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm brand-ring outline-none transition">
                @error('kelas') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Foto Siswa</label>
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm bg-white">
                <p class="text-xs text-slate-400 mt-1">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
                @error('foto') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="brand-bg text-white text-sm font-medium px-5 py-2.5 rounded-lg brand-bg-hover transition">Simpan Siswa</button>
                <a href="{{ route('siswa.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition">Batal</a>
            </div>
        </form>
    </div>
</div>

@include('partials.footer')
