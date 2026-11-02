@extends('layouts.admin')
@section('title', 'Pengaturan Footer')
@section('heading', 'Pengaturan Footer')
@section('subtitle', 'Kelola identitas, tautan navigasi, kontak, media sosial, dan copyright yang tampil di bagian bawah website.')
@section('content')
<form method="post" action="{{ route('admin.footer.update') }}" class="panel footer-settings-form">
    @csrf @method('PUT')
    <section class="footer-settings-section">
        <div class="footer-settings-heading"><span>01</span><div><h2>Identitas Sekolah</h2><p>Logo dikelola melalui penyimpanan logo sekolah yang sudah tersedia.</p></div></div>
        <div class="footer-settings-grid">
            <label class="footer-logo-setting">Logo sekolah
                @if($settings->get('school_logo_path'))<img src="{{ Storage::disk('public')->url($settings->get('school_logo_path')) }}" alt="Logo sekolah saat ini">@else<div class="footer-logo-placeholder">Logo belum diunggah</div>@endif
                <a class="secondary" href="{{ route('admin.photos.edit','logo') }}">{{ $settings->get('school_logo_path') ? 'Ganti logo' : 'Unggah logo' }}</a>
            </label>
            <div class="footer-setting-fields">
                <label>Nama sekolah<input name="school_name" required maxlength="180" value="{{ old('school_name', $settings->get('school_name', 'SMPN 1 Gondang')) }}">@error('school_name')<small class="error">{{ $message }}</small>@enderror</label>
                <label>Slogan singkat<input name="school_tagline" maxlength="180" value="{{ old('school_tagline', $settings->get('school_tagline', 'Sekolah yang unggul dan berkarakter')) }}">@error('school_tagline')<small class="error">{{ $message }}</small>@enderror</label>
                <label>Deskripsi footer<textarea name="footer_description" required maxlength="1000" rows="3">{{ old('footer_description', $settings->get('footer_description')) }}</textarea>@error('footer_description')<small class="error">{{ $message }}</small>@enderror</label>
                <label>Alamat sekolah<textarea name="address" maxlength="500" rows="2">{{ old('address', $settings->get('address', '')) }}</textarea>@error('address')<small class="error">{{ $message }}</small>@enderror</label>
            </div>
        </div>
    </section>
    <section class="footer-settings-section">
        <div class="footer-settings-heading"><span>02</span><div><h2>Navigasi Website</h2><p>Atur nama dan alamat tautan yang tampil pada kolom navigasi footer.</p></div></div>
        <div class="footer-repeat-list" data-footer-repeat="navigation">
            @foreach(old('footer_navigation', $settings->get('footer_navigation')) as $index => $link)
            <div class="footer-repeat-row"><label>Nama tautan<input name="footer_navigation[{{ $index }}][label]" required maxlength="80" value="{{ $link['label'] ?? '' }}"></label><label>Alamat tautan<input name="footer_navigation[{{ $index }}][url]" required maxlength="500" value="{{ $link['url'] ?? '' }}" placeholder="/#profil atau https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus tautan">Hapus</button></div>
            @endforeach
        </div>
        <button type="button" class="footer-add-row" data-footer-add="navigation">＋ Tambah tautan</button>
        @error('footer_navigation')<small class="error">{{ $message }}</small>@enderror
    </section>
    <section class="footer-settings-section">
        <div class="footer-settings-heading"><span>03</span><div><h2>Kontak dan Media Sosial</h2><p>Kontak menggunakan informasi sekolah. Tambahkan atau hapus akun sosial sesuai kebutuhan.</p></div></div>
        <div class="footer-settings-grid footer-contact-social">
            <div class="footer-setting-fields">
                <label>Email sekolah<input type="email" name="email" maxlength="254" value="{{ old('email', $settings->get('email', '')) }}">@error('email')<small class="error">{{ $message }}</small>@enderror</label>
                <label>Nomor telepon<input name="phone" maxlength="50" value="{{ old('phone', $settings->get('phone', '')) }}">@error('phone')<small class="error">{{ $message }}</small>@enderror</label>
                <p class="footer-address-note">Alamat yang tampil di bagian kontak menggunakan alamat sekolah di bagian Identitas Sekolah.</p>
            </div>
            <div>
                <div class="footer-repeat-list" data-footer-repeat="social">
                    @foreach(old('footer_social', $settings->get('footer_social')) as $index => $link)
                    <div class="footer-repeat-row"><label>Platform<input name="footer_social[{{ $index }}][label]" maxlength="80" value="{{ $link['label'] ?? '' }}"></label><label>URL profil<input type="url" name="footer_social[{{ $index }}][url]" maxlength="500" value="{{ $link['url'] ?? '' }}" placeholder="https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus media sosial">Hapus</button></div>
                    @endforeach
                </div>
                <button type="button" class="footer-add-row" data-footer-add="social">＋ Tambah media sosial</button>
                @error('footer_social')<small class="error">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>
    <section class="footer-settings-section">
        <div class="footer-settings-heading"><span>04</span><div><h2>Copyright</h2><p>Atur keterangan singkat pada bagian paling bawah footer.</p></div></div>
        <div class="footer-settings-grid footer-copyright-fields">
            <label>Tahun<input type="number" name="footer_copyright_year" required min="2000" max="2200" value="{{ old('footer_copyright_year', $settings->get('footer_copyright_year')) }}">@error('footer_copyright_year')<small class="error">{{ $message }}</small>@enderror</label>
            <label>Nama pada copyright<input name="footer_copyright_name" required maxlength="180" value="{{ old('footer_copyright_name', $settings->get('footer_copyright_name')) }}">@error('footer_copyright_name')<small class="error">{{ $message }}</small>@enderror</label>
            <label class="footer-copyright-note">Keterangan tambahan<input name="footer_copyright_note" maxlength="240" value="{{ old('footer_copyright_note', $settings->get('footer_copyright_note', '')) }}">@error('footer_copyright_note')<small class="error">{{ $message }}</small>@enderror</label>
        </div>
    </section>
    <div class="footer-settings-actions"><a class="secondary" href="{{ route('admin.dashboard') }}">Kembali</a><button class="primary">Simpan pengaturan footer</button></div>
</form>
<template id="footer-navigation-template"><div class="footer-repeat-row"><label>Nama tautan<input data-name="label" required maxlength="80"></label><label>Alamat tautan<input data-name="url" required maxlength="500" placeholder="/#profil atau https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus tautan">Hapus</button></div></template>
<template id="footer-social-template"><div class="footer-repeat-row"><label>Platform<input data-name="label" maxlength="80"></label><label>URL profil<input type="url" data-name="url" maxlength="500" placeholder="https://..."></label><button type="button" class="footer-remove-row" aria-label="Hapus media sosial">Hapus</button></div></template>
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
