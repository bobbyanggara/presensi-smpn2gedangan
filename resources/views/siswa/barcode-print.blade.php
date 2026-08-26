<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul ?? 'QR Siswa' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, "Segoe UI", sans-serif;
            margin: 0;
            color: #1e293b;
            background: #e2e8f0;
        }

        /* Toolbar hanya tampil di layar, hilang otomatis saat print / save as PDF */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 20px;
            background: #0f172a;
            color: #fff;
        }
        .toolbar p { margin: 0; font-size: 13px; color: #cbd5e1; }
        .toolbar button {
            background: #16a34a;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .toolbar button:hover { background: #15803d; }

        .sheet { padding: 20px; }

        .page {
            width: 100%;
            min-height: 700px;
            text-align: center;
            page-break-after: always;
            page-break-inside: avoid;
            background: #fff;
            margin-bottom: 16px;
            border-radius: 8px;
        }
        .page:last-child { page-break-after: auto; margin-bottom: 0; }

        .title { font-size: 18px; font-weight: bold; padding-top: 80px; margin-bottom: 12px; }
        .name { font-size: 16px; margin-bottom: 5px; }
        .class { font-size: 12px; color: #64748b; margin-bottom: 22px; }
        .qr-wrap { width: 360px; height: 360px; margin: 0 auto 20px; text-align: center; }
        .qr { width: 340px; height: 340px; }
        .nis { font-size: 14px; font-family: "Courier New", monospace; margin-top: 8px; }

        @media print {
            .toolbar { display: none; }
            body { background: #fff; }
            .sheet { padding: 0; }
            .page { border-radius: 0; margin-bottom: 0; box-shadow: none; }
            @page { margin: 28px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <p>Klik "Simpan sebagai PDF", lalu pada jendela print pilih tujuan <strong>Save as PDF / Microsoft Print to PDF</strong>.</p>
        <button onclick="window.print()">🖨️ Simpan sebagai PDF</button>
    </div>

    <div class="sheet">
        @foreach($items as $item)
            <div class="page">
                <div class="title">QR SISWA</div>
                <div class="name">{{ $item['siswa']->nama }}</div>
                <div class="class">Kelas {{ $item['siswa']->kelas }}</div>
                <div class="qr-wrap"><img class="qr" src="{{ $item['qrDataUri'] }}" alt="QR {{ $item['siswa']->nis }}"></div>
                <div class="nis">NIS: {{ $item['siswa']->nis }}</div>
            </div>
        @endforeach
    </div>

    <script>
        // Buka otomatis dialog print begitu halaman siap, supaya alur tetap
        // terasa seperti "download PDF" satu klik. Kalau browser memblokir
        // auto-print, tombol di toolbar tetap bisa dipakai manual.
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
