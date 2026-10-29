@extends('layouts.admin')
@section('title',$photo->exists?'Edit Foto Dokumentasi':'Tambah Foto Dokumentasi')
@section('heading',$photo->exists?'Edit Foto Dokumentasi':'Tambah Foto Dokumentasi')
@section('subtitle','Unggah foto resmi sekolah dan gunakan keterangan yang sesuai.')
@section('content')
<form class="panel profile-form spmb-form" method="post" enctype="multipart/form-data" action="{{ $photo->exists?route('admin.spmb.photos.update',$photo):route('admin.spmb.photos.store') }}">@csrf @if($photo->exists)@method('PUT')@endif
<label>Judul foto<input name="title" required maxlength="160" value="{{ old('title',$photo->title) }}"></label><label>Keterangan<textarea name="caption" rows="4" maxlength="2000">{{ old('caption',$photo->caption) }}</textarea></label><label>Foto {{ $photo->exists?'baru (opsional)':'' }}<input type="file" name="image" accept="image/jpeg,image/png,image/webp" {{ $photo->exists?'':'required' }}>@error('image')<small class="error">{{ $message }}</small>@enderror</label>@if($photo->exists)<img class="spmb-photo-preview" src="{{ Storage::disk('public')->url($photo->image_path) }}" alt="{{ $photo->title }}">@endif<label>Urutan tampil<input type="number" name="sort_order" min="0" max="99999" value="{{ old('sort_order',$photo->sort_order??0) }}"></label><label class="remember"><input type="checkbox" name="published" value="1" @checked(old('published',$photo->published))> Tampilkan di halaman publik</label>
<div class="actions"><a class="secondary" href="{{ route('admin.spmb.photos.index') }}">Batal</a><span class="grow"></span><button class="primary" type="submit">Simpan foto</button></div></form>
@endsection
