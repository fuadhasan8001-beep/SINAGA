<button class="site-admin-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false"><span aria-hidden="true">☰</span><span>Kelola</span></button>
<div class="site-admin-scrim" data-sidebar-close></div>
<aside class="site-admin-sidebar sidebar" id="admin-sidebar" aria-label="Menu pengelolaan">
    <div class="site-admin-head"><strong>Kelola website</strong><button type="button" data-sidebar-close aria-label="Tutup menu">×</button></div>
    <p>Anda melihat tampilan publik dengan kontrol administrator.</p>
    <nav>
        <span class="site-admin-label">HALAMAN INI</span>
        <a href="{{ route('admin.settings.edit') }}">Profil & informasi sekolah</a>
        <a href="{{ route('admin.articles.index') }}">Artikel & berita</a>
        <a href="{{ route('admin.content.index','announcement') }}">Pengumuman & agenda</a>
        <a href="{{ route('admin.content.index','extracurricular') }}">Ekstrakurikuler</a>
        <a href="{{ route('admin.content.index','gallery') }}">Galeri foto</a>
        <span class="site-admin-label">SEMUA KONTEN</span>
        @foreach(\App\Http\Controllers\Admin\ContentEntryController::TYPES as $type=>$label)<a href="{{ route('admin.content.index',$type) }}">{{ $label }}</a>@endforeach
        <a href="{{ route('admin.categories.index') }}">Kategori artikel</a>
        <a href="{{ route('admin.messages.index') }}">Pesan kontak</a>
        <a href="{{ route('admin.profile.edit') }}">Profil akun</a>
    </nav>
    <div class="site-admin-foot"><a href="{{ route('home') }}">Mode pengunjung</a><form method="post" action="{{ route('logout') }}">@csrf<button>Keluar</button></form></div>
</aside>
