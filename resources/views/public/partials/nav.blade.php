<header class="nav-wrap"><nav class="nav container" aria-label="Navigasi utama">
    @php($navigationBase = ($adminMode ?? false) ? route('admin.dashboard') : route('home'))
    <a class="brand" href="{{ $navigationBase }}#beranda" aria-label="{{ $settings['school_name'] ?? 'Nama Sekolah' }}, beranda"><span class="brand-mark" aria-label="Logo sekolah">@if(filled($settings['school_logo_path'] ?? null))<img src="{{ Storage::disk('public')->url($settings['school_logo_path']) }}" alt="Logo {{ $settings['school_name'] ?? 'sekolah' }}">@else Logo @endif</span><span class="brand-name">{{ $settings['school_name'] ?? 'Nama Sekolah' }}</span></a>
    <div class="nav-links" id="navLinks" aria-label="Menu halaman">
        @foreach(['beranda'=>'Beranda','profil'=>'Profil','akademik'=>'Akademik','pengumuman'=>'Pengumuman','ekstrakurikuler'=>'Ekstrakurikuler','galeri'=>'Galeri','kontak'=>'Kontak'] as $id=>$label)
        <a data-nav-section="{{ $id }}" href="{{ $navigationBase }}#{{ $id }}">{{ $label }}</a>
        @endforeach
        <a class="nav-spmb-cta" href="{{ ($adminMode ?? false) ? route('admin.spmb.dashboard') : route('spmb.public') }}">SPMB</a>
    </div>
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Buka menu" aria-controls="navLinks" aria-expanded="false"><span aria-hidden="true">☰</span></button>
</nav></header>
