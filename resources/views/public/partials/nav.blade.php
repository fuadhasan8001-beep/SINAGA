<header class="nav-wrap"><nav class="nav container" aria-label="Navigasi utama">
    <a class="brand" href="{{ ($adminMode ?? false) ? route('admin.dashboard') : route('home') }}" aria-label="{{ $settings['school_name'] ?? 'Nama Sekolah' }}, beranda"><span class="brand-mark" aria-label="Tempat logo sekolah">Logo</span><span class="brand-name">{{ $settings['school_name'] ?? 'Nama Sekolah' }}<small>{{ $settings['school_tagline'] ?? 'Informasi dan layanan sekolah' }}</small></span></a>
    <div class="nav-links" id="navLinks">
        <a class="{{ request()->routeIs('home','admin.dashboard') && !request()->query('section') ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard') : route('home') }}">Beranda</a>
        <a class="{{ request()->routeIs('profile') || request()->query('section') === 'profil' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'profil']) : route('profile') }}">Profil</a>
        <a class="{{ request()->routeIs('academics') || request()->query('section') === 'program' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'program']) : route('academics') }}">Akademik</a>
        <a class="{{ request()->routeIs('news','article.show') || request()->query('section') === 'berita' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'berita']) : route('news') }}">Berita</a>
        <a class="{{ request()->routeIs('extracurriculars') || request()->query('section') === 'ekskul' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'ekskul']) : route('extracurriculars') }}">Ekstrakurikuler</a>
        <a class="{{ request()->routeIs('gallery') || request()->query('section') === 'galeri' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'galeri']) : route('gallery') }}">Galeri</a>
        <a class="{{ request()->routeIs('contact') || request()->query('section') === 'kontak' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'kontak']) : route('contact') }}">Kontak</a>
    </div>
    <a class="btn nav-cta" href="{{ ($adminMode ?? false) ? route('admin.spmb.dashboard') : route('spmb.public') }}">Informasi SPMB</a><button class="menu-btn" id="menuBtn" aria-label="Buka menu" aria-expanded="false">☰</button>
</nav></header>
