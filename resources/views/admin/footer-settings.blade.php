@extends('layouts.admin')
@section('title', 'Pengaturan Footer')
@section('heading', 'Pengaturan Footer')
@section('subtitle', 'Kelola informasi yang ditampilkan pada footer publik website sekolah.')
@section('content')
@if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
<form method="post" action="{{ $panelRoute('admin.footer.update') }}" class="panel footer-settings-form">
    @csrf @method('PUT')

    <section class="footer-settings-section footer-settings-card">
        <div class="footer-settings-heading"><span>01</span><div><h2>Identitas Sekolah</h2><p>Nama, slogan, dan deskripsi yang tampil pada footer.</p></div></div>
        <div class="footer-settings-grid footer-identity-fields">
            <label>Nama sekolah<input name="school_name" required maxlength="180" value="{{ old('school_name', $settings->get('school_name', 'SMPN 1 Gondang')) }}">@error('school_name')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Slogan atau keterangan singkat<input name="school_tagline" maxlength="180" value="{{ old('school_tagline', $settings->get('school_tagline', '')) }}">@error('school_tagline')<small class="error">{{ $message }}</small>@enderror</label>
            <label class="footer-description-field">Deskripsi sekolah<textarea name="footer_description" required maxlength="1000" rows="3">{{ old('footer_description', $settings->get('footer_description')) }}</textarea>@error('footer_description')<small class="error">{{ $message }}</small>@enderror</label>
        </div>
        <p class="footer-address-note">Logo sekolah tetap menggunakan gambar yang dikelola pada halaman Kelola Profil.</p>
    </section>

    <section class="footer-settings-section footer-settings-card">
        <div class="footer-settings-heading"><span>02</span><div><h2>Alamat Sekolah</h2><p>Alamat lengkap akan dirangkai dan ditampilkan pada kartu alamat footer.</p></div></div>
        <div class="footer-settings-grid footer-address-fields">
            <label>Alamat jalan<textarea name="address" maxlength="500" rows="2">{{ old('address', $settings->get('address', '')) }}</textarea>@error('address')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Kota / kabupaten<input name="address_city" maxlength="120" value="{{ old('address_city', $settings->get('footer_city', '')) }}">@error('address_city')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Provinsi / wilayah<input name="address_region" maxlength="120" value="{{ old('address_region', $settings->get('footer_region', '')) }}">@error('address_region')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Kode pos (opsional)<input name="address_postal_code" maxlength="20" value="{{ old('address_postal_code', $settings->get('footer_postal_code', '')) }}">@error('address_postal_code')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Tautan Google Maps (opsional)<input type="url" name="address_map_url" maxlength="2048" value="{{ old('address_map_url', $settings->get('address_map_url', '')) }}" placeholder="https://maps.google.com/">@error('address_map_url')<small class="error">{{ $message }}</small>@enderror</label>
        </div>
    </section>

    <section class="footer-settings-section footer-settings-card">
        <div class="footer-settings-heading"><span>03</span><div><h2>Informasi Kontak</h2><p>WhatsApp terhubung langsung ke chat; telepon alternatif menggunakan panggilan perangkat.</p></div></div>
        <div class="footer-settings-grid footer-contact-fields">
            <label>Nomor WhatsApp sekolah<input name="phone" maxlength="50" value="{{ old('phone', $settings->get('phone', '')) }}" placeholder="08xx atau +62xx">@error('phone')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Telepon alternatif (opsional)<input name="phone_alternative" maxlength="50" value="{{ old('phone_alternative', $settings->get('phone_alternative', '')) }}">@error('phone_alternative')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Email resmi sekolah<input type="email" name="email" maxlength="254" value="{{ old('email', $settings->get('email', '')) }}">@error('email')<small class="error">{{ $message }}</small>@enderror</label>
        </div>
    </section>

    <section class="footer-settings-section footer-settings-card">
        <div class="footer-settings-heading"><span>04</span><div><h2>Media Sosial</h2><p>Isi URL akun yang aktif. Baris yang dikosongkan tidak akan ditampilkan pada footer.</p></div></div>
        <div class="footer-repeat-list" data-footer-repeat="social">
            @foreach(old('footer_social', $settings->get('footer_social')) as $index => $link)
            <div class="footer-repeat-row"><label>Platform<input name="footer_social[{{ $index }}][label]" maxlength="80" value="{{ $link['label'] ?? '' }}" placeholder="Instagram, Facebook, TikTok, lainnya"></label><label>URL akun<input type="url" name="footer_social[{{ $index }}][url]" maxlength="500" value="{{ $link['url'] ?? '' }}" placeholder="https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus media sosial">Hapus</button></div>
            @endforeach
        </div>
        <button type="button" class="footer-add-row" data-footer-add="social">＋ Tambah media sosial</button>
        @error('footer_social')<small class="error">{{ $message }}</small>@enderror
    </section>

    <section class="footer-settings-section footer-settings-card">
        <div class="footer-settings-heading"><span>05</span><div><h2>Navigasi Footer</h2><p>Atur label dan tujuan tautan pada kolom Jelajahi.</p></div></div>
        <div class="footer-repeat-list" data-footer-repeat="navigation">
            @foreach(old('footer_navigation', $settings->get('footer_navigation')) as $index => $link)
            <div class="footer-repeat-row"><label>Nama tautan<input name="footer_navigation[{{ $index }}][label]" required maxlength="80" value="{{ $link['label'] ?? '' }}"></label><label>Alamat tautan<input name="footer_navigation[{{ $index }}][url]" required maxlength="500" value="{{ $link['url'] ?? '' }}" placeholder="/#profil atau https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus tautan">Hapus</button></div>
            @endforeach
        </div>
        <button type="button" class="footer-add-row" data-footer-add="navigation">＋ Tambah tautan</button>
        @error('footer_navigation')<small class="error">{{ $message }}</small>@enderror
    </section>

    <section class="footer-settings-section footer-settings-card">
        <div class="footer-settings-heading"><span>06</span><div><h2>Copyright</h2><p>Atur keterangan singkat pada bagian paling bawah footer.</p></div></div>
        <div class="footer-settings-grid footer-copyright-fields">
            <label>Tahun<input type="number" name="footer_copyright_year" required min="2000" max="2200" value="{{ old('footer_copyright_year', $settings->get('footer_copyright_year')) }}">@error('footer_copyright_year')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Nama pada copyright<input name="footer_copyright_name" required maxlength="180" value="{{ old('footer_copyright_name', $settings->get('footer_copyright_name')) }}">@error('footer_copyright_name')<small class="error">{{ $message }}</small>@enderror</label>
            <label class="footer-copyright-note">Keterangan tambahan<input name="footer_copyright_note" maxlength="240" value="{{ old('footer_copyright_note', $settings->get('footer_copyright_note', '')) }}">@error('footer_copyright_note')<small class="error">{{ $message }}</small>@enderror</label>
        </div>
    </section>

    <div class="footer-settings-actions"><a class="secondary" href="{{ $panelRoute('admin.dashboard') }}">Batal</a><button class="primary">Simpan Perubahan</button></div>
</form>
<template id="footer-navigation-template"><div class="footer-repeat-row"><label>Nama tautan<input data-name="label" required maxlength="80"></label><label>Alamat tautan<input data-name="url" required maxlength="500" placeholder="/#profil atau https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus tautan">Hapus</button></div></template>
<template id="footer-social-template"><div class="footer-repeat-row"><label>Platform<input data-name="label" maxlength="80" placeholder="Instagram, Facebook, TikTok, lainnya"></label><label>URL akun<input type="url" data-name="url" maxlength="500" placeholder="https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus media sosial">Hapus</button></div></template>
<script>
document.querySelectorAll('[data-footer-add]').forEach((button) => button.addEventListener('click', () => {
    const type = button.dataset.footerAdd;
    const list = document.querySelector(`[data-footer-repeat="${type}"]`);
    const template = document.querySelector(`#footer-${type}-template`);
    const indices = [...list.querySelectorAll('input[name]')].map((input) => Number(input.name.match(/\[(\d+)\]/)?.[1] ?? -1));
    const index = Math.max(-1, ...indices) + 1;
    const row = template.content.firstElementChild.cloneNode(true);
    row.querySelectorAll('[data-name]').forEach((input) => { input.name = `footer_${type}[${index}][${input.dataset.name}]`; });
    list.append(row);
}));
document.addEventListener('click', (event) => {
    const button = event.target.closest('.footer-remove-row');
    if (button) button.closest('.footer-repeat-row').remove();
});
</script>
@endsection
