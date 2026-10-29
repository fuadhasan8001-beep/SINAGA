<button class="site-admin-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false"><span aria-hidden="true">☰</span><span>Kelola</span></button>
<div class="site-admin-scrim" data-sidebar-close></div>
<aside class="site-admin-sidebar sidebar" id="admin-sidebar" aria-label="Menu pengelolaan">
    <div class="site-admin-head"><div class="site-admin-brand"><span class="site-admin-mark" aria-hidden="true">S</span><div><strong>Kelola Website</strong><small>SINAGA · Panel Admin</small></div></div><button type="button" data-sidebar-close aria-label="Tutup menu">×</button></div>
    <p>{{ request()->routeIs('admin.dashboard') ? 'Kelola website dan kembali ke tampilan viewer kapan saja.' : 'Pilih menu untuk mengelola website. Kembali ke tampilan website melalui tombol di bagian atas.' }}</p>
    <nav>
        <span class="site-admin-label">KELOLA UTAMA</span>
        <a class="{{ request()->routeIs('admin.settings.*') || (request()->routeIs('admin.dashboard') && request()->query('section') === 'profil') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><span class="site-admin-icon" aria-hidden="true">▣</span>Profil & informasi sekolah</a>
        <a class="{{ (request()->routeIs('admin.articles.*') && !request()->routeIs('admin.articles.reviews')) || (request()->routeIs('admin.dashboard') && request()->query('section') === 'berita') ? 'active' : '' }}" href="{{ route('admin.articles.index') }}"><span class="site-admin-icon" aria-hidden="true">▤</span>Artikel & berita</a>
        <a class="{{ request()->routeIs('admin.articles.reviews') ? 'active' : '' }}" href="{{ route('admin.articles.reviews') }}"><span class="site-admin-icon" aria-hidden="true">✓</span>Persetujuan artikel</a>
        <span class="site-admin-label">SEMUA KONTEN</span>
        @foreach(\App\Http\Controllers\Admin\ContentEntryController::TYPES as $type=>$label)<a class="{{ request()->routeIs('admin.content.*') && request()->route('type') === $type ? 'active' : '' }}" href="{{ route('admin.content.index',$type) }}"><span class="site-admin-icon" aria-hidden="true">{{ match($type){'announcement'=>'◉','event'=>'◷','teacher'=>'♙','extracurricular'=>'◇','achievement'=>'★','gallery'=>'▧','document'=>'▱'} }}</span>{{ $label }}</a>@endforeach
        <span class="site-admin-label">PENERIMAAN</span>
        <a class="spmb-nav-link {{ request()->routeIs('admin.spmb.*') ? 'active' : '' }}" href="{{ route('admin.spmb.dashboard') }}"><span class="site-admin-icon" aria-hidden="true">▤</span>Kelola SPMB <small>SPMB</small></a>
        <a class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><span class="site-admin-icon" aria-hidden="true">▦</span>Kategori artikel</a>
        <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="site-admin-icon" aria-hidden="true">♙</span>Akun Guru</a>
        <a class="{{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}"><span class="site-admin-icon" aria-hidden="true">✉</span>Pesan kontak</a>
        <a class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><span class="site-admin-icon" aria-hidden="true">⚙</span>Pengaturan Website</a>
        <a class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}"><span class="site-admin-icon" aria-hidden="true">◌</span>Profil akun</a>
    </nav>
    <div class="site-admin-foot"><a href="{{ route('home') }}">Mode pengunjung</a><form method="post" action="{{ route('logout') }}">@csrf<button>Keluar</button></form></div>
</aside>
