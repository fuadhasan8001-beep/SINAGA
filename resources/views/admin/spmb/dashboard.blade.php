@extends('layouts.admin')
@section('title','Edit SPMB')
@section('heading','Informasi SPMB')
@section('subtitle','Kelola poster, keterangan tambahan, dan WhatsApp Contact Person dalam satu formulir.')
@section('content')
<form class="spmb-editor" method="post" enctype="multipart/form-data" action="{{ $panelRoute('admin.spmb.dashboard.update') }}">
    @csrf
    @method('PUT')

    <section class="spmb-editor-card" aria-labelledby="spmbPosterHeading">
        <div class="spmb-editor-card__heading"><span class="spmb-editor-card__number">01</span><div><h2 id="spmbPosterHeading">Foto poster</h2><p>Unggah poster SPMB yang akan langsung tampil untuk pengunjung.</p></div></div>
        @if($settings->poster_path)
            <div class="spmb-editor-poster-current"><img src="{{ Storage::disk('public')->url($settings->poster_path) }}" alt="Poster SPMB saat ini"></div>
        @endif
        <label class="spmb-editor-field" for="spmbPoster">{{ $settings->poster_path ? 'Ganti poster (opsional)' : 'Pilih poster' }}
            <input id="spmbPoster" type="file" name="poster" accept="image/jpeg,image/png,image/webp">
            <small>JPG, PNG, atau WebP. Maksimal 10 MB. Poster lama tetap tersimpan jika unggahan gagal.</small>
            @error('poster')<span class="spmb-editor-error">{{ $message }}</span>@enderror
        </label>
        <div class="spmb-editor-preview" id="spmbPosterPreview" hidden><strong>Pratinjau poster baru</strong><img alt="Pratinjau poster SPMB yang dipilih"></div>
    </section>

    <section class="spmb-editor-card" aria-labelledby="spmbDescriptionHeading">
        <div class="spmb-editor-card__heading"><span class="spmb-editor-card__number">02</span><div><h2 id="spmbDescriptionHeading">Keterangan tambahan</h2><p>Isi informasi pendaftaran dengan baris yang rapi. Teks akan ditampilkan utuh.</p></div></div>
        <label class="spmb-editor-field" for="spmbIntroduction">Keterangan untuk pengunjung
            <textarea id="spmbIntroduction" name="introduction" rows="7" maxlength="5000" placeholder="Tuliskan informasi tambahan SPMB…">{{ old('introduction',$settings->introduction) }}</textarea>
            <small>Maksimal 5.000 karakter.</small>
            @error('introduction')<span class="spmb-editor-error">{{ $message }}</span>@enderror
        </label>
    </section>

    <section class="spmb-editor-card" aria-labelledby="spmbContactHeading">
        <div class="spmb-editor-card__heading"><span class="spmb-editor-card__number">03</span><div><h2 id="spmbContactHeading">WhatsApp Contact Person</h2><p>Gunakan nomor internasional tanpa tanda plus atau spasi.</p></div></div>
        <label class="spmb-editor-field" for="spmbWhatsapp">Nomor WhatsApp
            <input id="spmbWhatsapp" type="tel" name="contact_whatsapp" inputmode="numeric" autocomplete="tel" pattern="62[0-9]{8,13}" maxlength="15" value="{{ old('contact_whatsapp',$settings->contact_whatsapp) }}" placeholder="6281234567890">
            <small>Contoh: 6281234567890. Kosongkan jika tombol kontak belum ingin ditampilkan.</small>
            @error('contact_whatsapp')<span class="spmb-editor-error">Masukkan nomor Indonesia dengan format 62xxxxxxxxxx.</span>@enderror
        </label>
    </section>

    <div class="spmb-editor-actions"><p>Setiap kali disimpan, perubahan langsung diterbitkan pada halaman pengunjung.</p><button class="primary" type="submit">Simpan dan terbitkan</button></div>
</form>
@endsection
