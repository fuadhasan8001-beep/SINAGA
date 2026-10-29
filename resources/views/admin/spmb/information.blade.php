@extends('layouts.admin')
@section('title','Informasi SPMB')
@section('heading','Informasi SPMB')
@section('subtitle','Isi hanya data penerimaan resmi yang telah dikonfirmasi sekolah.')
@section('content')
<form class="panel profile-form spmb-form" method="post" action="{{ route('admin.spmb.information.update') }}">@csrf @method('PUT')
<label>Judul informasi<input name="title" maxlength="180" required value="{{ old('title',$settings->title) }}">@error('title')<small class="error">{{ $message }}</small>@enderror</label>
<label>Tahun ajaran<input name="school_year" maxlength="30" value="{{ old('school_year',$settings->school_year) }}" placeholder="Isi setelah ditetapkan"></label>
<label>Pengantar<textarea name="introduction" rows="5" maxlength="5000">{{ old('introduction',$settings->introduction) }}</textarea></label>
<label>Status penerimaan<select name="status"><option value="preparation" @selected(old('status',$settings->status ?? 'preparation') === 'preparation')>Persiapan</option><option value="open" @selected(old('status',$settings->status ?? 'preparation') === 'open')>Dibuka</option><option value="closed" @selected(old('status',$settings->status ?? 'preparation') === 'closed')>Ditutup</option></select></label>
<div class="two"><label>Kuota resmi (opsional)<input type="number" name="quota" min="0" max="10000" value="{{ old('quota',$settings->quota) }}"></label><label>Informasi biaya (opsional)<input name="fee_information" maxlength="2000" value="{{ old('fee_information',$settings->fee_information) }}"></label></div>
<label>Tautan pendaftaran resmi (opsional)<input type="url" name="registration_url" maxlength="2048" value="{{ old('registration_url',$settings->registration_url) }}" placeholder="https://"></label>
<label class="remember"><input type="checkbox" name="published" value="1" @checked(old('published',$settings->published))> Tampilkan informasi SPMB pada halaman publik</label>
<div class="actions"><a class="secondary" href="{{ route('admin.spmb.dashboard') }}">Kembali</a><span class="grow"></span><button class="primary" type="submit">Simpan informasi</button></div></form>
@endsection
