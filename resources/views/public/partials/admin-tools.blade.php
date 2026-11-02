<button class="site-admin-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false"><span aria-hidden="true">☰</span><span>Kelola</span></button>
<div class="site-admin-scrim" data-sidebar-close></div>
<aside class="site-admin-sidebar sidebar" id="admin-sidebar" aria-label="Menu pengelolaan">
    <div class="site-admin-head"><div class="site-admin-brand"><span class="site-admin-mark" aria-hidden="true">S</span><div><strong>Kelola Website</strong><small>SINAGA · Panel Admin</small></div></div><button type="button" data-sidebar-close aria-label="Tutup menu">×</button></div>
    <p>{{ request()->routeIs('admin.dashboard') ? 'Kelola website dan kembali ke tampilan viewer kapan saja.' : 'Pilih menu untuk mengelola website. Kembali ke tampilan website melalui tombol di bagian atas.' }}</p>
    <nav>
        <span class="site-admin-label">MANAJEMEN KONTEN WEBSITE</span>
        <a class="{{ request()->routeIs('admin.settings.*') && request()->query('section', 'profile') === 'profile' ? 'active' : '' }}" href="{{ route('admin.settings.edit',['section'=>'profile','edit'=>1]) }}"><span class="site-admin-icon" aria-hidden="true">▣</span>Profil Sekolah</a>
        <a class="{{ request()->routeIs('admin.settings.*') && request()->query('section') === 'academic' ? 'active' : '' }}" href="{{ route('admin.settings.edit',['section'=>'academic','edit'=>1]) }}"><span class="site-admin-icon" aria-hidden="true">◫</span>Akademik</a>
        <a class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'announcement' ? 'active' : '' }}" href="{{ route('admin.content.index','announcement') }}"><span class="site-admin-icon" aria-hidden="true">◉</span>Kelola Pengumuman</a>
        <a class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'extracurricular' ? 'active' : '' }}" href="{{ route('admin.content.index','extracurricular') }}"><span class="site-admin-icon" aria-hidden="true">◇</span>Ekstrakurikuler</a>
        <a class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'gallery' ? 'active' : '' }}" href="{{ route('admin.content.index','gallery') }}"><span class="site-admin-icon" aria-hidden="true">▧</span>Galeri</a>
        <a class="{{ request()->routeIs('admin.settings.*') && request()->query('section') === 'contact' ? 'active' : '' }}" href="{{ route('admin.settings.edit',['section'=>'contact']) }}"><span class="site-admin-icon" aria-hidden="true">⌁</span>Kontak</a>
        @if(auth()->user()?->isAdmin())<a class="{{ request()->routeIs('admin.footer.*') ? 'active' : '' }}" href="{{ route('admin.footer.edit') }}"><span class="site-admin-icon" aria-hidden="true">▤</span>Pengaturan Footer</a>@endif
        <span class="site-admin-label">MANAJEMEN PESAN</span>
        <a class="{{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}"><span class="site-admin-icon" aria-hidden="true">✉</span>Pesan Kontak</a>
        <span class="site-admin-label">MANAJEMEN AKUN</span>
        <a class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}"><span class="site-admin-icon" aria-hidden="true">◌</span>Profil Akun</a>
        <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="site-admin-icon" aria-hidden="true">♙</span>Akun Guru</a>
    </nav>
    <div class="site-admin-foot"><a href="{{ route('home') }}">Mode pengunjung</a><form method="post" action="{{ route('logout') }}">@csrf<button>Keluar</button></form></div>
</aside>
