<div class="topline"><div class="container"><span>Selamat datang di website sekolah</span><span class="top-extra"><a href="{{ route('contact') }}">Hubungi kami</a></span></div></div>
<header class="nav-wrap"><nav class="nav container" aria-label="Navigasi utama">
    <a class="brand" href="{{ route('home') }}" aria-label="{{ $settings['school_name'] ?? 'Nama Sekolah' }}, beranda"><span class="brand-mark" aria-label="Tempat logo sekolah">Logo</span><span class="brand-name">{{ $settings['school_name'] ?? 'Nama Sekolah' }}<small>{{ $settings['school_tagline'] ?? 'Informasi dan layanan sekolah' }}</small></span></a>
    <div class="nav-links" id="navLinks">
        <a class="{{ request()->routeIs('home','admin.dashboard') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
        <a class="{{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}">Profil</a>
        <a class="{{ request()->routeIs('academics') ? 'active' : '' }}" href="{{ route('academics') }}">Akademik</a>
        <a class="{{ request()->routeIs('news','article.show') ? 'active' : '' }}" href="{{ route('news') }}">Berita</a>
        <a class="{{ request()->routeIs('extracurriculars') ? 'active' : '' }}" href="{{ route('extracurriculars') }}">Ekstrakurikuler</a>
        <a class="{{ request()->routeIs('gallery') ? 'active' : '' }}" href="{{ route('gallery') }}">Galeri</a>
        <a class="{{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Kontak</a>
    </div>
    <a class="btn nav-cta" href="{{ route('ppdb') }}">Informasi PPDB</a><button class="menu-btn" id="menuBtn" aria-label="Buka menu" aria-expanded="false">☰</button>
</nav></header>
