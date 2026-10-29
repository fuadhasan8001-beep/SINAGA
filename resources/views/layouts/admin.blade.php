<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#edf8f0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>@yield('title','Panel Pengelola') — {{ config('app.name','SINAGA') }}</title>
    @vite(['resources/css/admin.css','resources/css/admin-redesign.css','resources/css/site-admin.css','resources/css/spmb-admin.css','resources/js/admin.js'])
</head>
<body class="admin-manage">
    @include('public.partials.admin-tools')
    <main class="admin-content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Administrasi website</span>
                <h1>@yield('heading','Pengelolaan')</h1>
                <p>@yield('subtitle','Kelola konten website sekolah.')</p>
            </div>
            <div class="topbar-actions">
                <a class="back-dashboard" href="{{ route('admin.dashboard') }}">Kembali ke Website</a>
                <a class="view-site" href="{{ route('home') }}" target="_blank" rel="noopener">Mode pengunjung</a>
                <span class="user-chip"><span class="user-initial">{{ Str::upper(Str::substr(auth()->user()->name,0,1)) }}</span><span>{{ auth()->user()->name }}<small>{{ auth()->user()->isAdmin()?'Administrator':'Guru' }}</small></span></span>
            </div>
        </header>
        @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="notice error" role="alert">Periksa kembali data yang dimasukkan.</div>@endif
        <section>@yield('content')</section>
    </main>
</body>
</html>
