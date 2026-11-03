<header class="nav-wrap"><nav class="nav container" aria-label="Navigasi utama">
    @php($navigationBase = ($adminMode ?? false) ? $panelRoute('admin.dashboard') : route('home'))
    @php($principalGreetingPage = request()->routeIs('principal-greeting'))
    <a class="brand" href="{{ $navigationBase }}#beranda" aria-label="{{ $settings['school_name'] ?? 'Nama Sekolah' }}, beranda"><span class="brand-mark" aria-label="Logo sekolah">@if(filled($settings['school_logo_path'] ?? null))<img src="{{ Storage::disk('public')->url($settings['school_logo_path']) }}" alt="Logo {{ $settings['school_name'] ?? 'sekolah' }}">@else Logo @endif</span><span class="brand-name">{{ $settings['school_name'] ?? 'Nama Sekolah' }}</span></a>
    <div class="nav-links" id="navLinks" aria-label="Menu halaman">
        @foreach(['beranda'=>'Beranda','profil'=>'Profil','akademik'=>'Akademik','pengumuman'=>'Pengumuman','ekstrakurikuler'=>'Ekstrakurikuler','galeri'=>'Galeri'] as $id=>$label)
        @php($sectionHref = $navigationBase.'#'.$id)
        @if(($adminMode ?? false) && $id === 'pengumuman') @php($sectionHref = $panelRoute('admin.content.index','announcement')) @endif
        @if(($adminMode ?? false) && $id === 'galeri') @php($sectionHref = $panelRoute('admin.content.index','gallery')) @endif
        <a data-nav-section="{{ $id }}" class="{{ $principalGreetingPage && $id === 'profil' ? 'active' : '' }}" @if($principalGreetingPage && $id === 'profil') aria-current="location" @endif href="{{ $sectionHref }}">{{ $label }}</a>
        @endforeach
        <a class="{{ request()->routeIs('information.*', 'article.show', 'admin.articles.*', 'guru.articles.*') ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? $panelRoute('admin.articles.index') : route('information.articles') }}" @if(request()->routeIs('information.*', 'article.show', 'admin.articles.*', 'guru.articles.*')) aria-current="page" @endif>Artikel</a>
        <a data-nav-section="kontak" href="{{ $navigationBase }}#kontak">Kontak</a>
        @if(!($adminMode ?? false) || auth()->user()?->isAdmin())
            <a class="nav-spmb-cta" href="{{ ($adminMode ?? false) ? $panelRoute('admin.spmb.dashboard') : route('spmb.public') }}">SPMB</a>
        @endif
    </div>
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Buka menu" aria-controls="navLinks" aria-expanded="false"><span aria-hidden="true">☰</span></button>
</nav></header>
