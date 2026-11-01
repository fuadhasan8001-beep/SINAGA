<header class="nav-wrap"><nav class="nav container" aria-label="Navigasi utama">
    <a class="brand" href="{{ ($adminMode ?? false) ? route('admin.dashboard') : route('home') }}" aria-label="{{ $settings['school_name'] ?? 'Nama Sekolah' }}, beranda"><span class="brand-mark" aria-label="Logo sekolah">@if(filled($settings['school_logo_path'] ?? null))<img src="{{ Storage::disk('public')->url($settings['school_logo_path']) }}" alt="Logo {{ $settings['school_name'] ?? 'sekolah' }}">@else Logo @endif</span><span class="brand-name">{{ $settings['school_name'] ?? 'Nama Sekolah' }}</span></a>
    <div class="nav-links" id="navLinks">
        <a class="{{ request()->routeIs('home','admin.dashboard') && !request()->query('section') ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard') : route('home') }}">Beranda</a>
        <a class="{{ request()->routeIs('profile') || request()->query('section') === 'profil' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.settings.edit',['section'=>'profile','edit'=>1]) : route('profile') }}">Profil</a>
        <a class="{{ request()->routeIs('academics') || request()->query('section') === 'program' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.settings.edit',['section'=>'academic','edit'=>1]) : route('academics') }}">Akademik</a>
        <a class="{{ request()->routeIs('announcements') || request()->query('section') === 'layanan' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'layanan']) : route('announcements') }}">Pengumuman</a>
        <a class="{{ request()->routeIs('extracurriculars') || request()->query('section') === 'ekskul' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'ekskul']) : route('extracurriculars') }}">Ekstrakurikuler</a>
        <a class="{{ request()->routeIs('gallery') || request()->query('section') === 'galeri' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'galeri']) : route('home').'#galeri' }}">Galeri</a>
        <a class="{{ request()->routeIs('contact') || request()->query('section') === 'kontak' ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? route('admin.dashboard',['section'=>'kontak']) : route('home').'#kontak' }}">Kontak</a>
    </div>
    <button class="menu-btn" id="menuBtn" aria-label="Buka menu" aria-expanded="false">☰</button>
</nav></header>
