@extends('layouts.admin')
@section('title',$announcement->exists?'Edit Pengumuman SPMB':'Tambah Pengumuman SPMB')
@section('heading',$announcement->exists?'Edit Pengumuman SPMB':'Tambah Pengumuman SPMB')
@section('subtitle','Draf tidak tampil pada halaman publik.')
@section('content')
<form class="panel profile-form spmb-form" method="post" action="{{ $announcement->exists?route('admin.spmb.announcements.update',$announcement):route('admin.spmb.announcements.store') }}">@csrf @if($announcement->exists)@method('PUT')@endif
<label>Judul<input name="title" required minlength="3" maxlength="180" value="{{ old('title',$announcement->title) }}"></label><label>Isi pengumuman<textarea name="body" required rows="8" maxlength="10000">{{ old('body',$announcement->body) }}</textarea></label><label>Tanggal (opsional)<input type="datetime-local" name="starts_at" value="{{ old('starts_at',$announcement->starts_at?->format('Y-m-d\TH:i')) }}"></label><label class="remember"><input type="checkbox" name="published" value="1" @checked(old('published',$announcement->published))> Tayang di halaman SPMB publik</label>
<div class="actions"><a class="secondary" href="{{ route('admin.spmb.announcements.index') }}">Batal</a><span class="grow"></span><button class="primary" type="submit">Simpan pengumuman</button></div></form>
@endsection