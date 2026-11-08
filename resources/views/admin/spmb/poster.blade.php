@extends('layouts.admin')
@section('title','Poster SPMB')
@section('heading','Poster dan Foto SPMB')
@section('subtitle','Poster SPMB disimpan terpisah dari galeri dan foto website sekolah.')
@section('content')
<div class="panel spmb-poster-panel"><div class="panel-head"><h2>Poster saat ini</h2></div>@if($settings->poster_path)<img class="spmb-poster-preview" src="{{ Storage::disk('public')->url($settings->poster_path) }}" alt="Poster SPMB saat ini">@if($settings->poster_caption)<p class="spmb-poster-caption">{{ $settings->poster_caption }}</p>@endif @else<div class="spmb-poster-empty">Poster belum diunggah</div>@endif</div>
<form class="panel profile-form spmb-form" method="post" enctype="multipart/form-data" action="{{ $panelRoute('admin.spmb.poster.update') }}">@csrf
<label>Pilih poster baru (JPG, PNG, WebP; maksimal 10 MB)<input id="spmbPoster" type="file" name="poster" accept="image/jpeg,image/png,image/webp" {{ $settings->poster_path?'':'required' }}>@error('poster')<small class="error">{{ $message }}</small>@enderror</label>
<div class="spmb-upload-preview" id="spmbPosterPreview" hidden><span>Pratinjau pilihan baru</span><img alt="Pratinjau poster baru"></div>
<label>Keterangan poster (opsional)<input type="text" name="poster_caption" maxlength="250" value="{{ old('poster_caption',$settings->poster_caption) }}">@error('poster_caption')<small class="error">{{ $message }}</small>@enderror</label>
<div class="actions"><a class="secondary" href="{{ $panelRoute('admin.spmb.dashboard') }}">Batal</a><span class="grow"></span><button class="primary" type="submit">Simpan poster</button></div></form>
@if($settings->poster_path)<form method="post" action="{{ $panelRoute('admin.spmb.poster.destroy') }}" onsubmit="return confirm('Hapus poster SPMB saat ini?')">@csrf @method('DELETE')<button class="danger" type="submit">Hapus poster</button></form>@endif
@endsection
