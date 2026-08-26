<?php $size = $size ?? 'small'; $inline = $inline ?? false; ?>

@if($size === 'big')
    <div class="rounded-2xl shadow-sm text-center mb-6"
         style="background: linear-gradient(135deg, #0e7c3f 0%, #063b1f 100%); padding: 1.75rem 1.5rem;">
        <p class="font-medium mb-2" style="color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 0.15em;">Waktu Sekarang</p>
        <p id="jam-realtime-big" class="font-mono" style="color: #ffffff; font-size: 2.5rem; font-weight: 700; letter-spacing: 0.05em; font-variant-numeric: tabular-nums; line-height: 1.1;">--:--:--</p>
        <p id="tanggal-realtime-big" class="mt-2" style="color: rgba(255,255,255,0.6); font-size: 0.875rem;">Memuat tanggal…</p>
    </div>
@else
    <div class="{{ $inline ? '' : 'flex justify-end mb-4' }}">
        <div class="inline-flex items-center gap-2.5 bg-white border border-slate-200 rounded-full shadow-sm" style="padding: 0.5rem 1rem 0.5rem 0.75rem;">
            <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" style="background: rgba(14,124,63,0.12); color: #0e7c3f;">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="leading-tight text-left">
                <p id="jam-realtime-small" class="font-mono font-bold" style="color: #334155; font-size: 0.875rem; font-variant-numeric: tabular-nums;">--:--:--</p>
                <p id="tanggal-realtime-small" style="color: #94a3b8; font-size: 11px;">Memuat tanggal…</p>
            </div>
        </div>
    </div>
@endif

<script>
    (function () {
        var jamEl = document.getElementById('jam-realtime-{{ $size }}');
        var tglEl = document.getElementById('tanggal-realtime-{{ $size }}');

        function update() {
            var now = new Date();
            if (jamEl) {
                jamEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            }
            if (tglEl) {
                var tanggal = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                tglEl.textContent = tanggal + ' · WIB';
            }
        }

        update();
        setInterval(update, 1000);
    })();
</script>
