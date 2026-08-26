<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — SMP Negeri 2 Gedangan</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:600,700,800|inter:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{
            --font-display:'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            --font-body:'Inter', ui-sans-serif, system-ui, sans-serif;
            --brand-green:#0e7c3f;
            --brand-green-dark:#0b6633;
            --brand-green-darker:#063b1f;
            --brand-yellow:#ffcc00;
            --brand-blue:#0ea5e9;
            --brand-red:#e11d48;
        }
        *{ box-sizing:border-box; }
        html,body{ margin:0; padding:0; height:100%; }
        body{ font-family:var(--font-body); color:#1e293b; }
        h1,h2,h3{ font-family:var(--font-display); letter-spacing:-0.01em; }

        .login-wrap{ display:flex; min-height:100vh; }

        /* ===== Panel kiri (gambar / branding) =====
           GANTI GAMBAR: ubah url(...) di .login-left di bawah ini dengan
           gambar milik Bobby sendiri, misalnya:
           background-image:url('{{ asset('images/login-bg.jpg') }}');
           Overlay gradasi hijau tetap dipertahankan supaya teks putih di
           atasnya tetap terbaca di gambar apa pun. */
        .login-left{
            flex:1 1 44%;
            position:relative;
            overflow:hidden;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            padding:40px 44px;
            color:#fff;
            background-image:
                linear-gradient(180deg, rgba(6,59,31,.75) 0%, rgba(6,59,31,.6) 45%, rgba(6,59,31,.92) 100%),
                url('{{ asset('images/login-bg.webp') }}');
            background-size:cover;
            background-position:center;
            background-color:var(--brand-green-darker);
        }
        .login-left-top{ position:relative; z-index:1; display:flex; align-items:center; gap:12px; }
        .login-logo-box{ width:44px; height:44px; min-width:44px; border-radius:12px; background:#fff; padding:6px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(0,0,0,.2); }
        .login-logo-box img{ width:100%; height:100%; object-fit:contain; display:block; }
        .login-school-name{ font-family:var(--font-display); font-weight:800; font-size:15px; line-height:1.2; }

        .login-left-bottom{ position:relative; z-index:1; max-width:420px; }
        .login-headline{ font-size:32px; font-weight:800; line-height:1.2; margin:0 0 10px; }
        .login-subtext{ font-size:14px; color:rgba(255,255,255,.8); line-height:1.6; margin:0 0 26px; }

        .login-feature{ display:flex; align-items:flex-start; gap:14px; margin-bottom:18px; }
        .login-feature-icon{ width:38px; height:38px; min-width:38px; border-radius:10px; background:rgba(255,255,255,.14); display:flex; align-items:center; justify-content:center; }
        .login-feature-icon svg{ width:19px; height:19px; }
        .login-feature-title{ font-size:14px; font-weight:700; margin:0 0 2px; color:#fff; }
        .login-feature-desc{ font-size:12.5px; color:rgba(255,255,255,.7); margin:0; line-height:1.5; }

        /* ===== Panel kanan (form) ===== */
        .login-right{
            flex:1 1 56%;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#f8faf9;
            padding:32px 24px;
        }
        .login-card{ width:100%; max-width:380px; }
        .login-eyebrow{ display:flex; align-items:center; gap:8px; font-size:11.5px; font-weight:700; letter-spacing:.08em; color:var(--brand-green-dark); text-transform:uppercase; margin-bottom:14px; }
        .login-eyebrow::before{ content:""; width:18px; height:2px; background:var(--brand-green); display:inline-block; }
        .login-card h2{ font-size:24px; font-weight:800; color:#0f172a; margin:0 0 22px; }

        .login-field{ margin-bottom:18px; }
        .login-label{ display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px; }
        .login-input-wrap{ position:relative; }
        .login-input-wrap svg{ position:absolute; left:13px; top:50%; transform:translateY(-50%); width:17px; height:17px; color:var(--brand-green-dark); opacity:.6; pointer-events:none; }
        .login-input{
            width:100%; padding:12px 40px 12px 40px; font-size:14px;
            border:1.5px solid transparent; border-radius:10px; background:#f1f0e9;
            color:#0f172a; transition:border-color .15s, box-shadow .15s, background-color .15s;
        }
        .login-input::placeholder{ color:#9a988f; }
        .login-toggle-password{
            position:absolute; right:11.5px; top:50%; transform:translateY(-50%);
            width:26px; height:26px; line-height:0; display:flex; align-items:center; justify-content:center;
            background:none; border:none; padding:0; margin:0; cursor:pointer; color:#9a988f; border-radius:6px;
        }
        .login-toggle-password:hover{ color:#334155; background:rgba(0,0,0,.05); }
        .login-toggle-password svg{ display:block; width:17px; height:17px; pointer-events:none; }
        .login-input:focus{ outline:none; background:#fff; border-color:var(--brand-green); box-shadow:0 0 0 3px rgba(14,124,63,.12); }
        .login-error{ font-size:12.5px; color:var(--brand-red); margin-top:6px; }
        .login-status{ font-size:13px; font-weight:600; color:var(--brand-green); background:#e9f7ee; border:1px solid #cdeed9; padding:10px 14px; border-radius:10px; margin-bottom:20px; }

        .login-remember-row{ margin-bottom:22px; }
        .login-remember{ display:flex; align-items:center; gap:8px; font-size:13.5px; color:#475569; }
        .login-remember input{ accent-color:var(--brand-green); width:15px; height:15px; }

        .login-submit{
            width:100%; padding:13px; border:none; border-radius:10px;
            background:var(--brand-green); color:#fff; font-size:14.5px; font-weight:700;
            cursor:pointer; transition:background-color .15s;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .login-submit:hover{ background:var(--brand-green-dark); }
        .login-submit:disabled{ background:#9ca3af; cursor:not-allowed; }

        .login-input:disabled{ background:#e9e8e1; color:#9a988f; cursor:not-allowed; }
        .login-toggle-password:disabled{ cursor:not-allowed; opacity:.5; }
        .login-remember input:disabled{ cursor:not-allowed; }

        .login-lockout-note{
            display:flex; align-items:flex-start; gap:9px; margin-bottom:18px;
            padding:12px 14px; border-radius:10px; background:#fef2f2; color:#b91c1c;
            font-size:12.5px; line-height:1.5; border:1px solid #fecaca;
        }
        .login-lockout-note svg{ width:16px; height:16px; min-width:16px; margin-top:1px; }

        .login-help{ text-align:center; font-size:13px; color:#64748b; margin-top:22px; }
        .login-help a{ color:var(--brand-green-dark); font-weight:600; text-decoration:none; }
        .login-help a:hover{ text-decoration:underline; }

        /* ===== Mobile ===== */
        .login-mobile-header{ display:none; }
        @media (max-width:960px){
            .login-left{ display:none; }
            .login-right{
                min-height:100vh;
                align-items:flex-start;
                padding:32px 20px;
                background-image:
                    linear-gradient(180deg, rgba(6,59,31,.55) 0%, rgba(6,59,31,.65) 55%, rgba(6,59,31,.85) 100%),
                    url('{{ asset('images/login-bg.webp') }}');
                background-size:cover;
                background-position:center;
                background-color:var(--brand-green-darker);
            }
            .login-card{
                margin-top:24px;
                background:#fff;
                border-radius:18px;
                padding:26px 22px;
                box-shadow:0 10px 30px rgba(0,0,0,.25);
            }
            .login-mobile-header{ display:flex; flex-direction:column; align-items:center; text-align:center; margin-bottom:28px; }
            .login-mobile-header .login-logo-box{ width:56px; height:56px; background:#fff; border:1px solid #e2e8f0; margin-bottom:12px; }
            .login-mobile-header h1{ font-size:15px; font-weight:800; color:#0f172a; margin:0; }
            .login-mobile-header p{ font-size:12px; color:#64748b; margin:2px 0 0; }
        }
    </style>
</head>
<body>
    <div class="login-wrap">

        <!-- Panel kiri: gambar + branding -->
        <div class="login-left">
            <div class="login-left-top">
                <div class="login-logo-box">
                    <img src="{{ asset('images/logo-smp.webp') }}" alt="Logo SMP Negeri 2 Gedangan">
                </div>
                <div class="login-school-name">SMP Negeri 2<br>Gedangan</div>
            </div>

            <div class="login-left-bottom">
                <h1 class="login-headline">Halo,<br>selamat datang.</h1>
                <p class="login-subtext">Kelola data siswa, presensi, dan laporan kehadiran sistem SMP Negeri 2 Gedangan dari satu dashboard.</p>

                <div class="login-feature">
                    <div class="login-feature-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                    </div>
                    <div>
                        <p class="login-feature-title">Presensi Cepat</p>
                        <p class="login-feature-desc">Scan QR / barcode siswa secara real-time di setiap lokasi.</p>
                    </div>
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    </div>
                    <div>
                        <p class="login-feature-title">Rekap Otomatis</p>
                        <p class="login-feature-desc">Ringkasan kehadiran harian tersusun otomatis tanpa input manual.</p>
                    </div>
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <p class="login-feature-title">Laporan Lengkap</p>
                        <p class="login-feature-desc">Laporan bulanan per kelas &amp; lokasi, siap diekspor kapan saja.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel kanan: form login -->
        <div class="login-right">
            <div class="login-card">

                <div class="login-mobile-header">
                    <div class="login-logo-box">
                        <img src="{{ asset('images/logo-smp.webp') }}" alt="Logo SMP Negeri 2 Gedangan">
                    </div>
                    <h1>SMP Negeri 2 Gedangan</h1>
                    <p>Sistem Presensi Siswa</p>
                </div>

                <div class="login-eyebrow">Admin Panel</div>
                <h2>Masuk ke Dashboard</h2>

                @if (session('status'))
                    <div class="login-status">{{ session('status') }}</div>
                @endif

                @php($lockoutSeconds = (int) session('login_lockout_seconds', 0))
                @if($lockoutSeconds > 0)
                    <div class="login-lockout-note" id="loginLockoutNote">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <span>Terlalu banyak percobaan gagal. Coba lagi dalam <strong id="loginLockoutSeconds">{{ $lockoutSeconds }}</strong> detik.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="login-field">
                        <label for="username" class="login-label">Username</label>
                        <div class="login-input-wrap">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 10-2.636 6.364M16.5 12V8.25" /></svg>
                            <input id="username" class="login-input" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="username">
                        </div>
                        @if($lockoutSeconds <= 0)
                            @error('username')<p class="login-error">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <div class="login-field">
                        <label for="password" class="login-label">Kata Sandi</label>
                        <div class="login-input-wrap">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            <input id="password" class="login-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                            <button type="button" class="login-toggle-password" id="togglePassword" aria-label="Tampilkan kata sandi" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <g id="eyeIconShow"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.01 9.964 7.183.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.01-9.964-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></g>
                                    <g id="eyeIconHide" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></g>
                                </svg>
                            </button>
                        </div>
                        @error('password')<p class="login-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="login-remember-row">
                        <label class="login-remember">
                            <input type="checkbox" name="remember" id="rememberMe">
                            Ingat saya di perangkat ini
                        </label>
                    </div>

                    <button type="submit" class="login-submit" id="loginSubmit">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" /></svg>
                        <span id="loginSubmitLabel">Masuk</span>
                    </button>
                </form>

                <p class="login-help">Lupa kredensial atau butuh akses? <a href="https://wa.me/6285232521522">Hubungi tim IT</a></p>
            </div>
        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeShow = document.getElementById('eyeIconShow');
        const eyeHide = document.getElementById('eyeIconHide');

        togglePassword.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            eyeShow.style.display = isHidden ? 'none' : '';
            eyeHide.style.display = isHidden ? '' : 'none';
            togglePassword.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    </script>

    {{-- Kalau lagi kena lock (5x gagal login), nonaktifkan semua tombol/input
         selama hitungan mundur berjalan, biar user gak bisa spam submit lagi
         sebelum waktunya. Otomatis aktif lagi begitu hitungan habis. --}}
    @if($lockoutSeconds > 0)
    <script>
        (function () {
            var secondsLeft = {{ (int) $lockoutSeconds }};
            var note = document.getElementById('loginLockoutNote');
            var secondsEl = document.getElementById('loginLockoutSeconds');
            var submitBtn = document.getElementById('loginSubmit');
            var submitLabel = document.getElementById('loginSubmitLabel');
            var controls = [
                document.getElementById('username'),
                document.getElementById('password'),
                document.getElementById('togglePassword'),
                document.getElementById('rememberMe'),
                submitBtn,
            ];

            function setDisabled(disabled) {
                controls.forEach(function (el) {
                    if (el) el.disabled = disabled;
                });
            }

            function tick() {
                if (secondsEl) secondsEl.textContent = secondsLeft;
                if (submitLabel) submitLabel.textContent = 'Tunggu ' + secondsLeft + ' detik…';

                if (secondsLeft <= 0) {
                    clearInterval(timer);
                    setDisabled(false);
                    if (submitLabel) submitLabel.textContent = 'Masuk';
                    if (note) note.style.display = 'none';
                    return;
                }
                secondsLeft -= 1;
            }

            setDisabled(true);
            tick();
            var timer = setInterval(tick, 1000);
        })();
    </script>
    @endif
</body>
</html>
