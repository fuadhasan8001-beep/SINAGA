@php($adminUser = auth()->user()?->isAdmin())
<button class="site-admin-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false"><span aria-hidden="true">☰</span><span>Kelola</span></button>
<div class="site-admin-scrim" data-sidebar-close></div>
<aside class="site-admin-sidebar sidebar" id="admin-sidebar" aria-label="Menu pengelolaan">
    <div class="site-admin-head">
        <div class="site-admin-brand"><span class="site-admin-mark" aria-hidden="true">S</span><div><strong>{{ $adminUser ? 'Kelola Website' : 'Kelola Konten' }}</strong><small>SINAGA · Panel {{ $adminUser ? 'Admin' : 'Guru' }}</small></div></div>
        <button type="button" data-sidebar-close aria-label="Tutup menu">×</button>
    </div>
    <p>{{ $adminUser ? 'Kelola website sekolah dan seluruh kontennya.' : 'Kelola artikel, galeri, dan pengumuman sekolah.' }}</p>
    <nav>
        @if($adminUser)
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ $panelRoute('admin.dashboard') }}"><span class="site-admin-icon" aria-hidden="true">⌂</span>Dashboard</a>
            <span class="site-admin-label">MANAJEMEN KONTEN WEBSITE</span>
            <a class="{{ request()->routeIs('admin.settings.*') && request()->query('section', 'profile') === 'profile' ? 'active' : '' }}" href="{{ $panelRoute('admin.settings.edit',['section'=>'profile','edit'=>1]) }}"><span class="site-admin-icon" aria-hidden="true">▣</span>Profil Sekolah</a>
            <a class="{{ request()->routeIs('admin.settings.*') && request()->query('section') === 'hero' ? 'active' : '' }}" href="{{ $panelRoute('admin.settings.edit',['section'=>'hero']) }}"><span class="site-admin-icon" aria-hidden="true">▧</span>Kelola Hero</a>
            <a class="{{ request()->routeIs('admin.settings.*') && request()->query('section') === 'academic' ? 'active' : '' }}" href="{{ $panelRoute('admin.settings.edit',['section'=>'academic','edit'=>1]) }}"><span class="site-admin-icon" aria-hidden="true">◫</span>Akademik</a>
            <a class="{{ request()->routeIs('admin.articles.*') ? 'active' : '' }}" href="{{ $panelRoute('admin.articles.index') }}"><span class="site-admin-icon" aria-hidden="true">▤</span>Kelola Artikel</a>
            <a class="{{ request()->routeIs('admin.articles.reviews') ? 'active' : '' }}" href="{{ $panelRoute('admin.articles.reviews') }}"><span class="site-admin-icon" aria-hidden="true">✓</span>Persetujuan Artikel</a>
            <a class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'announcement' ? 'active' : '' }}" href="{{ $panelRoute('admin.content.index','announcement') }}"><span class="site-admin-icon" aria-hidden="true">◉</span>Kelola Pengumuman</a>
            <a class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'extracurricular' ? 'active' : '' }}" href="{{ $panelRoute('admin.content.index','extracurricular') }}"><span class="site-admin-icon" aria-hidden="true">◇</span>Ekstrakurikuler</a>
            <a class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'gallery' ? 'active' : '' }}" href="{{ $panelRoute('admin.content.index','gallery') }}"><span class="site-admin-icon" aria-hidden="true">▧</span>Galeri</a>
            <a class="{{ request()->routeIs('admin.settings.*') && request()->query('section') === 'contact' ? 'active' : '' }}" href="{{ $panelRoute('admin.settings.edit',['section'=>'contact']) }}"><span class="site-admin-icon" aria-hidden="true">⌁</span>Kontak</a>
            <a class="{{ request()->routeIs('admin.footer.*') ? 'active' : '' }}" href="{{ $panelRoute('admin.footer.edit') }}"><span class="site-admin-icon" aria-hidden="true">▥</span>Pengaturan Footer</a>
            <a class="{{ request()->routeIs('admin.spmb.*') ? 'active' : '' }}" href="{{ $panelRoute('admin.spmb.dashboard') }}"><span class="site-admin-icon" aria-hidden="true">✳</span>SPMB</a>
            <span class="site-admin-label">MANAJEMEN PESAN & AKUN</span>
            <a class="{{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ $panelRoute('admin.messages.index') }}"><span class="site-admin-icon" aria-hidden="true">✉</span>Pesan Kontak</a>
            <a class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ $panelRoute('admin.profile.edit') }}"><span class="site-admin-icon" aria-hidden="true">◌</span>Profil Akun</a>
            <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ $panelRoute('admin.users.index') }}"><span class="site-admin-icon" aria-hidden="true">♙</span>Akun Guru</a>
        @else
            <span class="site-admin-label">MENU GURU</span>
            <a class="{{ request()->routeIs('guru.dashboard') ? 'active' : '' }}" href="{{ $panelRoute('admin.dashboard') }}"><span class="site-admin-icon" aria-hidden="true">⌂</span>Dashboard</a>
            <a class="{{ request()->routeIs('guru.articles.*') ? 'active' : '' }}" href="{{ $panelRoute('admin.articles.index') }}"><span class="site-admin-icon" aria-hidden="true">▤</span>Kelola Artikel</a>
            <a class="{{ request()->routeIs('guru.content.*') && request()->route('type') === 'gallery' ? 'active' : '' }}" href="{{ $panelRoute('admin.content.index','gallery') }}"><span class="site-admin-icon" aria-hidden="true">▧</span>Kelola Galeri</a>
            <a class="{{ request()->routeIs('guru.content.*') && request()->route('type') === 'announcement' ? 'active' : '' }}" href="{{ $panelRoute('admin.content.index','announcement') }}"><span class="site-admin-icon" aria-hidden="true">◉</span>Kelola Pengumuman</a>
        @endif
    </nav>
    <div class="site-admin-foot">
        @if($adminUser)<a href="{{ route('home') }}">Mode pengunjung</a>@endif
        <form id="logoutForm" method="post" action="{{ route('logout') }}">@csrf<button type="button" data-logout-open>Keluar</button></form>
    </div>
</aside>

<dialog class="logout-dialog" id="logoutConfirmation" aria-labelledby="logoutDialogTitle" aria-describedby="logoutDialogDescription">
    <div class="logout-dialog-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3m9-8h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg></div>
    <h2 id="logoutDialogTitle">Yakin ingin keluar?</h2>
    <p id="logoutDialogDescription">Apakah kamu yakin ingin mengakhiri sesi sekarang? Kamu harus login kembali untuk mengakses halaman ini.</p>
    <div class="logout-dialog-actions"><button type="button" class="logout-cancel" data-logout-cancel>Batal</button><button type="button" class="logout-confirm" data-logout-confirm>Ya, Keluar</button></div>
</dialog>

<dialog class="action-confirm-dialog" id="actionConfirmDialog" aria-labelledby="actionConfirmTitle" aria-describedby="actionConfirmDescription">
    <div class="logout-dialog-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3"/></svg></div>
    <h2 id="actionConfirmTitle">Konfirmasi penghapusan</h2>
    <p id="actionConfirmDescription">Tindakan ini tidak dapat dibatalkan.</p>
    <div class="logout-dialog-actions"><button type="button" class="logout-cancel" data-action-cancel>Tidak, Batalkan</button><button type="button" class="logout-confirm" data-action-confirm>Ya, Hapus</button></div>
</dialog>
