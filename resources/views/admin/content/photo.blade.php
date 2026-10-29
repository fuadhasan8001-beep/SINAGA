@extends('layouts.admin')

@section('title',$title)
@section('heading',$title)
@section('subtitle','Pilih foto baru. Foto yang sedang tampil tetap digunakan sampai perubahan berhasil disimpan.')

@section('content')
<form id="photoEditForm" class="photo-edit panel" method="post" enctype="multipart/form-data" action="{{ route('admin.photos.update',$placement) }}">
    @csrf
    @method('PUT')
    <div class="photo-preview-frame">
        @if($imagePath)
            <img id="photoPreview" src="{{ Storage::disk('public')->url($imagePath) }}" alt="{{ $label }} saat ini">
            <div id="photoPreviewEmpty" hidden></div>
        @else
            <img id="photoPreview" alt="Pratinjau foto yang dipilih" hidden>
            <div id="photoPreviewEmpty" class="photo-preview-empty">Belum ada foto untuk area ini.</div>
        @endif
    </div>
    <div class="photo-edit-controls">
        <label for="photoFile">Pilih foto baru</label>
        <input id="photoFile" name="image" type="file" accept="image/jpeg,image/png,image/webp" required>
        <p id="photoFileName" class="hint" role="status">Belum ada file dipilih. JPEG, PNG, atau WebP, maksimal 5 MB.</p>
        @error('image')<p class="error" role="alert">{{ $message }}</p>@enderror
        <div class="actions">
            <a class="secondary" href="{{ route('admin.dashboard') }}">Batal</a>
            <button class="primary" type="submit">Simpan Foto</button>
        </div>
    </div>
</form>
@endsection
