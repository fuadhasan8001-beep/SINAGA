<div class="topline"><div class="container"><span>Selamat datang di website sekolah</span><span class="top-extra"><a href="{{ route('contact') }}">Hubungi kami</a></span></div></div>
<header class="nav-wrap"><nav class="nav container" aria-label="Navigasi utama">
    <a class="brand" href="{{ route('home') }}" aria-label="{{ $settings['school_name'] ?? 'Nama Sekolah' }}, beranda"><span class="brand-mark" aria-label="Tempat logo sekolah">Logo</span><span class="brand-name">{{ $settings['school_name'] ?? 'Nama Sekolah' }}<small>{{ $settings['school_tagline'] ?? 'Informasi dan layanan sekolah' }}</small></span></a>
    <div class="nav-links" id="navLinks"><a href="{{ route('home') }}">Beranda</a><a href="{{ route('profile') }}">Profil</a><a href="{{ route('academics') }}">Akademik</a><a href="{{ route('news') }}">Berita</a><a href="{{ route('extracurriculars') }}">Ekstrakurikuler</a><a href="{{ route('contact') }}">Kontak</a></div>
    <a class="btn nav-cta" href="{{ route('ppdb') }}">Informasi PPDB <span>↗</span></a><button class="menu-btn" id="menuBtn" aria-label="Buka menu" aria-expanded="false">☰</button>
</nav></header>
