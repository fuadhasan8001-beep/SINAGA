@extends('layouts.admin')
@section('title','Edit SPMB')
@section('heading','Informasi SPMB')
@section('subtitle','Perbarui poster, pengumuman, dan contact person SPMB.')
@section('content')
<form class="spmb-editor" data-spmb-editor method="post" enctype="multipart/form-data" action="{{ $panelRoute('admin.spmb.dashboard.update') }}">
    @csrf
    @method('PUT')

    <section class="spmb-editor-card" aria-labelledby="spmbPosterHeading">
        <div class="spmb-editor-card__heading"><span class="spmb-editor-card__number">01</span><div><h2 id="spmbPosterHeading">Poster SPMB</h2><p>Poster akan tampil di halaman informasi SPMB.</p></div></div>
        @if($settings->poster_path)
            <div class="spmb-editor-poster-current"><img data-current-poster src="{{ Storage::disk('public')->url($settings->poster_path) }}" alt="Poster SPMB saat ini"></div>
        @endif
        <label class="spmb-editor-field" for="spmbPoster">{{ $settings->poster_path ? 'Ganti poster (opsional)' : 'Pilih poster' }}
            <input id="spmbPoster" type="file" name="poster" accept="image/jpeg,image/png,image/webp" data-poster-input>
            <small>JPG, PNG, atau WebP. Maksimal 10 MB. Poster lama tetap digunakan jika tidak memilih gambar baru.</small>
            @error('poster')<span class="spmb-editor-error">{{ $message }}</span>@enderror
        </label>
        <div class="spmb-editor-preview" data-poster-preview hidden><strong>Pratinjau poster baru</strong><img alt="Pratinjau poster SPMB yang dipilih"></div>
    </section>

    <section class="spmb-editor-card" aria-labelledby="spmbAnnouncementHeading">
        <div class="spmb-editor-card__heading"><span class="spmb-editor-card__number">02</span><div><h2 id="spmbAnnouncementHeading">Pengumuman</h2><p>Tulis informasi SPMB yang ingin dibaca calon murid dan orang tua.</p></div></div>
        <label class="spmb-editor-field" for="spmbAnnouncement">Teks pengumuman
            <textarea id="spmbAnnouncement" name="introduction" rows="10" maxlength="5000">{{ old('introduction',$settings->introduction) }}</textarea>
            <small>Maksimal 5.000 karakter. Baris baru akan dipertahankan pada halaman pengunjung.</small>
            @error('introduction')<span class="spmb-editor-error">{{ $message }}</span>@enderror
        </label>
    </section>

    <section class="spmb-editor-card" aria-labelledby="spmbContactHeading">
        <div class="spmb-editor-card__heading"><span class="spmb-editor-card__number">03</span><div><h2 id="spmbContactHeading">Contact person</h2><p>Masukkan nomor WhatsApp panitia yang dapat dihubungi pengunjung.</p></div></div>
        <label class="spmb-editor-field" for="spmbWhatsapp">Nomor contact person
            <input id="spmbWhatsapp" type="tel" name="contact_whatsapp" inputmode="tel" autocomplete="tel" pattern="[+0-9().\s-]{9,24}" maxlength="24" value="{{ old('contact_whatsapp',$settings->contact_whatsapp) }}" placeholder="0812-3456-7890">
            <small>Gunakan nomor Indonesia 08…, 62…, atau +62…. Nomor disimpan dalam format internasional.</small>
            @error('contact_whatsapp')<span class="spmb-editor-error">{{ $message }}</span>@enderror
        </label>
    </section>

    <div class="spmb-editor-actions"><p>Perubahan langsung tampil setelah disimpan.</p><button class="primary" type="submit">Simpan Perubahan</button></div>
</form>
@endsection
