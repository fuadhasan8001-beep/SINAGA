@extends('layouts.admin')
@section('title','Tautan Penting SPMB')
@section('heading','Tautan Penting SPMB')
@section('subtitle','Kelola tautan pendaftaran, panduan, hasil seleksi, dan informasi resmi lainnya.')
@php($linkCategories=['registration'=>'Pendaftaran','form'=>'Formulir','results'=>'Hasil seleksi','guide'=>'Panduan','document'=>'Berkas','other'=>'Informasi lainnya'])
@section('content')
@include('admin.spmb._back-dashboard')
<form class="panel profile-form spmb-form spmb-link-form" method="post" action="{{ $panelRoute('admin.spmb.links.store') }}">
    @csrf
    <div class="panel-head"><h2>Tambah tautan</h2></div>
    <div class="spmb-link-fields">
        <label>Nama tautan<input name="title" required maxlength="120" value="{{ old('title') }}" placeholder="Daftar Sekarang">@error('title')<small class="error">{{ $message }}</small>@enderror</label>
        <label>Jenis tautan<select name="category" required>@foreach($linkCategories as $value=>$label)<option value="{{ $value }}" @selected(old('category')===$value)>{{ $label }}</option>@endforeach</select>@error('category')<small class="error">{{ $message }}</small>@enderror</label>
        <label class="spmb-link-url">URL tujuan<input type="url" name="url" required maxlength="2048" value="{{ old('url') }}" placeholder="https://">@error('url')<small class="error">{{ $message }}</small>@enderror</label>
        <label>Deskripsi singkat<textarea name="description" rows="2" maxlength="300">{{ old('description') }}</textarea></label>
        <label>Urutan tampil<input type="number" name="sort_order" min="0" max="99999" value="{{ old('sort_order',0) }}"></label>
    </div>
    <label class="remember"><input type="checkbox" name="published" value="1" @checked(old('published'))> Tampilkan pada halaman publik</label>
    <div class="actions"><span class="grow"></span><button class="primary" type="submit">Tambah tautan</button></div>
</form>

<div class="spmb-link-list">
    @forelse($links as $link)
        <form class="panel profile-form spmb-form spmb-link-form" method="post" action="{{ $panelRoute('admin.spmb.links.update',$link['id']) }}">
            @csrf @method('PUT')
            <div class="panel-head"><h2>{{ $link['title'] }}</h2><span class="pill {{ $link['published']?'published':'draft' }}">{{ $link['published']?'Tayang':'Draf' }}</span></div>
            <div class="spmb-link-fields">
                <label>Nama tautan<input name="title" required maxlength="120" value="{{ $link['title'] }}"></label>
                <label>Jenis tautan<select name="category" required>@foreach($linkCategories as $value=>$label)<option value="{{ $value }}" @selected(($link['category']??'other')===$value)>{{ $label }}</option>@endforeach</select></label>
                <label class="spmb-link-url">URL tujuan<input type="url" name="url" required maxlength="2048" value="{{ $link['url'] }}"></label>
                <label>Deskripsi singkat<textarea name="description" rows="2" maxlength="300">{{ $link['description']??'' }}</textarea></label>
                <label>Urutan tampil<input type="number" name="sort_order" min="0" max="99999" value="{{ $link['sort_order']??0 }}"></label>
            </div>
            <label class="remember"><input type="checkbox" name="published" value="1" @checked($link['published'])> Tampilkan pada halaman publik</label>
            <div class="actions"><span class="grow"></span><button class="primary" type="submit">Simpan perubahan</button></div>
        </form>
        <form class="spmb-link-delete" method="post" action="{{ $panelRoute('admin.spmb.links.destroy',$link['id']) }}" onsubmit="return confirm('Hapus tautan ini?')">@csrf @method('DELETE')<button class="tiny deny" type="submit">Hapus tautan {{ $link['title'] }}</button></form>
    @empty
        <div class="panel spmb-link-empty"><h2>Belum ada tautan</h2><p>Tambahkan tautan resmi agar pengunjung dapat menuju pendaftaran, panduan, atau informasi lain.</p></div>
    @endforelse
</div>
@endsection
