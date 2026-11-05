<header class="nav-wrap"><nav class="nav container" aria-label="Navigasi utama">
    @if($adminMode ?? false)<button class="site-admin-toggle site-admin-toggle--header" type="button" aria-controls="admin-sidebar" aria-expanded="false"><span aria-hidden="true">☰</span><span>Kelola</span></button>@endif
    @php($navigationBase = ($adminMode ?? false) ? $panelRoute('admin.dashboard') : route('home'))
    @php($principalGreetingPage = request()->routeIs('principal-greeting'))
    <a class="brand" href="{{ $navigationBase }}" aria-label="{{ $settings['school_name'] ?? 'Nama Sekolah' }}, beranda"><span class="brand-mark" aria-label="Logo sekolah">@if(filled($settings['school_logo_path'] ?? null))<img src="{{ Storage::disk('public')->url($settings['school_logo_path']) }}" alt="Logo {{ $settings['school_name'] ?? 'sekolah' }}">@else Logo @endif</span><span class="brand-name">{{ $settings['school_name'] ?? 'Nama Sekolah' }}</span></a>
    @php($publicSectionRoutes = ['beranda' => 'home', 'profil' => 'profile', 'akademik' => 'academics', 'pengumuman' => 'announcements', 'ekstrakurikuler' => 'extracurriculars', 'galeri' => 'gallery', 'kontak' => 'contact'])
    @php($activePublicRoute = request()->route()?->getName())
    <div class="nav-links" id="navLinks" aria-label="Menu halaman">
        @foreach(['beranda'=>'Beranda','profil'=>'Profil','akademik'=>'Akademik','pengumuman'=>'Pengumuman','ekstrakurikuler'=>'Ekstrakurikuler','galeri'=>'Galeri'] as $id=>$label)
        @php($sectionHref = ($adminMode ?? false) ? $navigationBase.'#'.$id : route($publicSectionRoutes[$id]))
        @if(($adminMode ?? false) && $id === 'pengumuman') @php($sectionHref = $panelRoute('admin.content.index','announcement')) @endif
        @if(($adminMode ?? false) && $id === 'galeri') @php($sectionHref = $panelRoute('admin.content.index','gallery')) @endif
        @php($isActive = !($adminMode ?? false) && ($activePublicRoute === $publicSectionRoutes[$id] || ($principalGreetingPage && $id === 'profil')))
        <a data-nav-section="{{ $id }}" class="{{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="location" @endif href="{{ $sectionHref }}">{{ $label }}</a>
        @endforeach
        <a class="{{ request()->routeIs('information.*', 'article.show', 'admin.articles.*', 'guru.articles.*') ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? $panelRoute('admin.articles.index') : route('information.articles') }}" @if(request()->routeIs('information.*', 'article.show', 'admin.articles.*', 'guru.articles.*')) aria-current="page" @endif>Artikel</a>
        <a data-nav-section="kontak" href="{{ ($adminMode ?? false) ? $navigationBase.'#kontak' : route($publicSectionRoutes['kontak']) }}">Kontak</a>
        @if(!($adminMode ?? false) || auth()->user()?->isAdmin())
            <a class="nav-spmb-cta {{ !($adminMode ?? false) && request()->routeIs('spmb.public','ppdb') ? 'active' : '' }}" href="{{ ($adminMode ?? false) ? $panelRoute('admin.spmb.dashboard') : route('spmb.public') }}" @if(!($adminMode ?? false) && request()->routeIs('spmb.public','ppdb')) aria-current="page" @endif>SPMB</a>
        @endif
        @if(!($adminMode ?? false) && auth()->check())
            @php($panelRoleLabel = auth()->user()->isAdmin() ? 'Admin' : 'Guru')
            <a class="nav-spmb-cta nav-panel-return" href="{{ $panelRoute('admin.dashboard') }}" aria-label="Kembali ke mode {{ $panelRoleLabel }}">Kembali ke {{ $panelRoleLabel }}</a>
        @endif
    </div>
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Buka menu" aria-controls="navLinks" aria-expanded="false"><span aria-hidden="true">☰</span></button>
</nav></header>
