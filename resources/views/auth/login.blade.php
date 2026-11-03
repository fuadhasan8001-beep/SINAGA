<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Masuk — SINAGA</title>
    @vite(['resources/css/admin.css','resources/css/admin-redesign.css'])
</head>
<body class="login-page">
    <main class="login-wrap">
        <form method="post" action="{{ route('login.store') }}" class="card login-card">
            @csrf
            <div class="brand login-brand">
                @if(filled($schoolLogoPath ?? null))
                    <img class="login-brand-logo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($schoolLogoPath) }}" alt="Logo SMPN 1 Gondang">
                @else
                    <span class="mark" aria-hidden="true">S</span>
                @endif
                <div><b>Panel Pengelola</b><small>Website Sekolah</small></div>
            </div>
            <h1>Masuk ke SINAGA</h1>
            <p class="muted">Gunakan akun Admin atau Guru yang telah dibuat.</p>
            <label for="loginUsername">Username atau email<input id="loginUsername" type="text" name="username" autocomplete="username" value="{{ old('username', old('email')) }}" required autofocus></label>
            @error('username')<span class="error">{{ $message }}</span>@enderror
            @error('email')<span class="error">{{ $message }}</span>@enderror
            <label for="loginPassword">Kata sandi</label>
            <div class="login-password-field">
                <input id="loginPassword" type="password" name="password" autocomplete="current-password" required>
                <button class="password-toggle" type="button" aria-label="Tampilkan kata sandi" aria-controls="loginPassword" aria-pressed="false">
                    <svg data-eye-open viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg data-eye-closed viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/></svg>
                </button>
            </div>
            <button class="primary" type="submit">Masuk</button>
            <a class="back" href="{{ route('home') }}">Kembali ke website</a>
        </form>
    </main>
    <script>
        const passwordInput = document.getElementById('loginPassword');
        const passwordToggle = document.querySelector('.password-toggle');
        passwordToggle?.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(showPassword));
            passwordToggle.setAttribute('aria-label', showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            passwordToggle.querySelector('[data-eye-open]').hidden = showPassword;
            passwordToggle.querySelector('[data-eye-closed]').hidden = !showPassword;
        });
    </script>
</body>
</html>
