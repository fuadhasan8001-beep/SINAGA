@extends('layouts.admin')
@section('title','Jadwal SPMB')
@section('heading','Jadwal Pendaftaran')
@section('subtitle','Satu tahapan per baris dengan format tanggal | tahap | keterangan. Tanggal tidak diisi otomatis.')
@section('content')
<form class="panel profile-form spmb-form" method="post" action="{{ route('admin.spmb.schedule.update') }}">@csrf @method('PUT')
<label>Jadwal pendaftaran<textarea name="schedule_text" rows="10" placeholder="Contoh format: tanggal | tahap | keterangan">{{ old('schedule_text',collect($settings->schedule??[])->map(fn($row)=>implode(' | ',[$row['date']??'',$row['stage']??'',$row['details']??'']))->implode("\n")) }}</textarea>@error('schedule_text')<small class="error">{{ $message }}</small>@enderror</label>
<div class="actions"><a class="secondary" href="{{ route('admin.spmb.dashboard') }}">Kembali</a><span class="grow"></span><button class="primary" type="submit">Simpan jadwal</button></div></form>
@endsection