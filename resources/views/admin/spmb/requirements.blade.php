@extends('layouts.admin')
@section('title','Persyaratan SPMB')
@section('heading','Persyaratan dan Alur')
@section('subtitle','Satu poin per baris. Bagian yang belum resmi dapat dibiarkan kosong.')
@section('content')
<form class="panel profile-form spmb-form" method="post" action="{{ route('admin.spmb.requirements.update') }}">@csrf @method('PUT')
<label>Persyaratan<textarea name="requirements_text" rows="6">{{ old('requirements_text',implode("\n",$settings->requirements??[])) }}</textarea></label>
<label>Tahapan pendaftaran<textarea name="steps_text" rows="6">{{ old('steps_text',implode("\n",$settings->steps??[])) }}</textarea></label>
<label>Berkas yang perlu disiapkan<textarea name="documents_text" rows="6">{{ old('documents_text',implode("\n",$settings->documents??[])) }}</textarea></label>
<div class="actions"><a class="secondary" href="{{ route('admin.spmb.dashboard') }}">Kembali</a><span class="grow"></span><button class="primary" type="submit">Simpan persyaratan</button></div></form>
@endsection