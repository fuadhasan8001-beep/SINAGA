@extends('layouts.admin')
@section('title',$entry->exists?'Edit '.$label:'Tambah '.$label)
@section('heading',$entry->exists?'Edit '.$label:'Tambah '.$label)
@section('subtitle',$type === 'gallery' ? 'Simpan judul, foto, dan posisi tampil dokumentasi sekolah.' : ($type === 'extracurricular' ? 'Lengkapi foto, nama, dan ringkasan. Data akan ditampilkan di website setelah disimpan.' : ($type === 'announcement' ? 'Pengumuman yang disimpan langsung ditampilkan pada website.' : 'Konten baru hanya tampil jika status Tayang diaktifkan.')))
@section('content')
@php
    $academicTypes = ['learning_activity','education_calendar','academic_program','academic_assessment'];
    if ($type === 'announcement') {
        $announcementBodyText = trim(html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</p>'], "\n", $entry->body ?? ''))));
        $announcementDescription = old('description', $entry->exists ? ($announcementBodyText !== '' ? $announcementBodyText : $entry->excerpt) : '');
        $announcementPublished = old('published', $entry->exists ? (int) $entry->published : 1);
    }
@endphp
<form class="panel profile-form content-form{{ $type === 'extracurricular' ? ' extracurricular-form' : '' }}{{ $type === 'announcement' ? ' announcement-form' : '' }}{{ $type === 'gallery' ? ' gallery-form' : '' }}" method="post" enctype="multipart/form-data" action="{{ $entry->exists?$panelRoute('admin.content.update',[$type,$entry]):$panelRoute('admin.content.store',$type) }}">
    @csrf @if($entry->exists)@method('PUT')@endif
    @if($type === 'gallery')
        <label for="galleryTitle">Judul
            <input id="galleryTitle" type="text" name="title" required minlength="2" maxlength="180" value="{{ old('title',$entry->title) }}" placeholder="Masukkan judul foto">
            @error('title')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label class="gallery-image-field" for="galleryImage">Foto
            <span class="gallery-upload-zone" id="galleryUploadZone" tabindex="0">
                <span class="gallery-upload-preview" id="galleryUploadPreviewWrap">
                    @if($entry->image_path)
                        <img id="galleryUploadPreview" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Preview {{ $entry->title }}">
                        <span class="gallery-upload-placeholder" id="galleryUploadPlaceholder" hidden><span aria-hidden="true">▧</span><strong>Pilih atau unggah foto</strong></span>
                    @else
                        <img id="galleryUploadPreview" src="" alt="Preview foto galeri" hidden>
                        <span class="gallery-upload-placeholder" id="galleryUploadPlaceholder"><span aria-hidden="true">▧</span><strong>Pilih atau unggah foto</strong></span>
                    @endif
                </span>
                <span class="gallery-upload-copy"><strong>{{ $entry->image_path ? 'Ganti foto' : 'Pilih atau unggah foto' }}</strong><small>JPG, JPEG, PNG, atau WebP · maksimal 5 MB</small></span>
            </span>
            <input id="galleryImage" type="file" name="image" accept="image/jpeg,image/png,image/webp" @required(!$entry->exists)>
            @error('image')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="gallerySortOrder">Urutan Tampil
            <input id="gallerySortOrder" type="number" name="sort_order" required min="0" max="99999" value="{{ old('sort_order',$entry->sort_order ?? 0) }}">
            <span class="hint">Nomor yang lebih kecil ditampilkan lebih dahulu.</span>
            @error('sort_order')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="galleryPostedAt">Tanggal Posting
            <input id="galleryPostedAt" type="date" name="starts_at" value="{{ old('starts_at',($entry->starts_at ?? $entry->created_at ?? now())->format('Y-m-d')) }}">
            <span class="hint">Kosongkan untuk menggunakan tanggal penyimpanan hari ini.</span>
            @error('starts_at')<span class="error">{{ $message }}</span>@enderror
        </label>
    @elseif($type === 'extracurricular')
        <label class="extracurricular-title-field" for="extracurricularTitle">Nama Ekstrakurikuler
            <input id="extracurricularTitle" type="text" name="title" required minlength="2" maxlength="180" value="{{ old('title',$entry->title) }}" placeholder="Masukkan nama ekstrakurikuler">
            @error('title')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label class="extracurricular-excerpt-field" for="extracurricularExcerpt">Ringkasan Ekstrakurikuler
            <textarea id="extracurricularExcerpt" name="excerpt" required maxlength="240" rows="4" placeholder="Tuliskan ringkasan singkat mengenai kegiatan ekstrakurikuler ini">{{ old('excerpt',$entry->excerpt) }}</textarea>
            <span class="hint">Maksimal 240 karakter agar tetap nyaman dibaca pada kartu website.</span>
            @error('excerpt')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label class="extracurricular-description-field" for="extracurricularDescription">Deskripsi Lengkap (opsional)
            <textarea id="extracurricularDescription" name="body" rows="7" placeholder="Tambahkan informasi yang sudah terverifikasi tentang kegiatan ini">{{ old('body',$entry->body) }}</textarea>
            @error('body')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label class="extracurricular-image-field" for="extracurricularImage">Foto Ekstrakurikuler
            <span class="extracurricular-upload">
                <span class="extracurricular-preview" id="extracurricularPreviewWrap">
                    @if($entry->image_path)
                        <img id="extracurricularPreview" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Preview foto {{ $entry->title }}">
                        <span class="extracurricular-placeholder" id="extracurricularPlaceholder" hidden><span aria-hidden="true">▧</span><strong>Unggah Foto Ekstrakurikuler</strong></span>
                    @else
                        <img id="extracurricularPreview" src="" alt="Preview foto ekstrakurikuler" hidden>
                        <span class="extracurricular-placeholder" id="extracurricularPlaceholder"><span aria-hidden="true">▧</span><strong>Unggah Foto Ekstrakurikuler</strong></span>
                    @endif
                </span>
                <span class="extracurricular-upload-copy"><strong>{{ $entry->image_path ? 'Ganti foto' : 'Pilih foto' }}</strong><small>JPG, JPEG, PNG, atau WebP · maksimal 5 MB</small></span>
            </span>
            <input id="extracurricularImage" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            @error('image')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label class="extracurricular-image-field" for="extracurricularPhotos">Foto kegiatan tambahan
            <input id="extracurricularPhotos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp">
            <span class="hint">Pilih hingga 12 foto. Kamu dapat memilih lagi untuk menambahkan foto, atau menghapus foto dari preview sebelum menyimpan (JPG, JPEG, PNG, atau WebP; maksimal 5 MB per foto).</span>
            <span class="hint" id="extracurricularPhotosCount" aria-live="polite">Belum ada foto baru dipilih.</span>
            <div class="extracurricular-upload-preview-list" id="extracurricularPhotosPreview" aria-live="polite"></div>
            @error('photos')<span class="error">{{ $message }}</span>@enderror
            @error('photos.*')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="extracurricularOrder">Urutan Tampil
            <input id="extracurricularOrder" type="number" name="sort_order" min="0" max="99999" value="{{ old('sort_order',$entry->sort_order ?? 0) }}">
            @error('sort_order')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="extracurricularPublished">Status Publikasi
            <select id="extracurricularPublished" name="published"><option value="1" @selected((string) old('published',$entry->exists ? (int) $entry->published : 1) === '1')>Tayang</option><option value="0" @selected((string) old('published',$entry->exists ? (int) $entry->published : 1) === '0')>Draf</option></select>
            @error('published')<span class="error">{{ $message }}</span>@enderror
        </label>
    @elseif($type === 'learning_activity')
        <label for="learningActivityTitle">Judul Kegiatan Pembelajaran
            <input id="learningActivityTitle" type="text" name="title" required minlength="2" maxlength="180" value="{{ old('title',$entry->title) }}" placeholder="Nama kegiatan">
            @error('title')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="learningActivityExcerpt">Ringkasan
            <textarea id="learningActivityExcerpt" name="excerpt" maxlength="500" rows="3" placeholder="Ringkasan singkat yang tampil pada kartu">{{ old('excerpt',$entry->excerpt) }}</textarea>
            @error('excerpt')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="learningActivityBody">Deskripsi Lengkap (opsional)
            <textarea id="learningActivityBody" name="body" rows="7" placeholder="Informasi kegiatan yang telah dikonfirmasi sekolah">{{ old('body',$entry->body) }}</textarea>
            @error('body')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="learningActivityImage">Foto Kegiatan (opsional)
            <input id="learningActivityImage" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <span class="hint">JPEG, PNG, atau WebP · maksimal 5 MB.</span>
            @if($entry->image_path)<img class="spmb-photo-thumb" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto kegiatan {{ $entry->title }} saat ini">@endif
            @error('image')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="learningActivityDate">Tanggal Kegiatan (opsional)
            <input id="learningActivityDate" type="date" name="starts_at" value="{{ old('starts_at',$entry->starts_at?->format('Y-m-d')) }}">
            @error('starts_at')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="learningActivityLocation">Lokasi (opsional)
            <input id="learningActivityLocation" name="location" maxlength="180" value="{{ old('location',$entry->location) }}">
            @error('location')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="learningActivityOrder">Urutan Tampil
            <input id="learningActivityOrder" type="number" name="sort_order" min="0" max="99999" value="{{ old('sort_order',$entry->sort_order ?? 0) }}">
            @error('sort_order')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="learningActivityPublished">Status Publikasi
            <select id="learningActivityPublished" name="published"><option value="1" @selected((string) old('published',$entry->exists ? (int) $entry->published : 1) === '1')>Tayang</option><option value="0" @selected((string) old('published',$entry->exists ? (int) $entry->published : 1) === '0')>Draf</option></select>
            @error('published')<span class="error">{{ $message }}</span>@enderror
        </label>
    @elseif($type === 'announcement')
        <label for="announcementTitle">Judul Pengumuman
            <input id="announcementTitle" type="text" name="title" required minlength="2" maxlength="180" value="{{ old('title',$entry->title) }}" placeholder="Tulis judul pengumuman">
            @error('title')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="announcementDescription">Deskripsi Pengumuman
            <textarea id="announcementDescription" name="description" required maxlength="5000" rows="7" placeholder="Tuliskan ringkasan dan informasi pengumuman">{{ $announcementDescription }}</textarea>
            <span class="hint">Deskripsi ini digunakan sebagai ringkasan di halaman utama. Maksimal 5.000 karakter.</span>
            @error('description')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="announcementDate">Tanggal Pengumuman
            <input id="announcementDate" type="date" name="starts_at" value="{{ old('starts_at',$entry->starts_at?->format('Y-m-d')) }}">
            @error('starts_at')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="announcementStatus">Status Publikasi
            <select id="announcementStatus" name="published" required>
                <option value="1" @selected((string) $announcementPublished === '1')>Tayang</option>
                <option value="0" @selected((string) $announcementPublished === '0')>Draf</option>
            </select>
            @error('published')<span class="error">{{ $message }}</span>@enderror
        </label>
        <label for="announcementOrder">Urutan Tampil
            <input id="announcementOrder" type="number" name="sort_order" required min="0" max="99999" value="{{ old('sort_order',$entry->sort_order ?? 1) }}">
            <span class="hint">Nomor lebih kecil tampil lebih dahulu. Urutan saat ini: {{ $entry->exists ? $entry->sort_order : 'posisi berikutnya' }}.</span>
            @error('sort_order')<span class="error">{{ $message }}</span>@enderror
        </label>
    @else
        @unless(in_array($type,$academicTypes,true))<label>Judul<input name="title" required maxlength="180" value="{{ old('title',$entry->title) }}"></label><label>Ringkasan<textarea name="excerpt" maxlength="500" rows="3">{{ old('excerpt',$entry->excerpt) }}</textarea></label>@endunless
        @if(!in_array($type,['teacher','document']))<label>Isi/keterangan<textarea name="body" rows="6">{{ old('body',$entry->body) }}</textarea></label>@endif
        @if(in_array($type,['event','announcement']))<label>Waktu kegiatan<input type="{{ $type === 'announcement' ? 'date' : 'datetime-local' }}" name="starts_at" value="{{ old('starts_at',$entry->starts_at?->format($type === 'announcement' ? 'Y-m-d' : 'Y-m-d\TH:i')) }}"></label><label>Lokasi<input name="location" maxlength="180" value="{{ old('location',$entry->location) }}"></label>@endif
        @if($type==='teacher')<label>Jabatan / posisi<input name="location" maxlength="180" value="{{ old('location',$entry->location) }}"></label>@endif
        @if(in_array($type,['teacher','gallery','achievement','learning_activity','education_calendar','academic_program','academic_assessment','announcement']))<label>Foto pendukung (opsional · JPEG, PNG, WebP · maksimal 5 MB)<input type="file" name="image" accept="image/png,image/jpeg,image/webp"></label>@if($entry->image_path)<img class="spmb-photo-thumb" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto saat ini">@endif @endif
        @if($type==='document')<label>File dokumen (PDF, Word, Excel, PowerPoint · maksimal 10 MB)<input type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"></label>@if($entry->document_path)<a href="{{ Storage::disk('public')->url($entry->document_path) }}" target="_blank" rel="noopener">Lihat dokumen saat ini</a>@endif @endif
        @unless(in_array($type,$academicTypes,true))<label>Urutan tampil<input type="number" name="sort_order" min="0" max="99999" value="{{ old('sort_order',$entry->sort_order??0) }}"></label><label>Metadata tambahan (JSON, opsional)<textarea name="metadata_json" rows="3" placeholder='{"alt":"Teks alternatif foto","kelas":"..."}'>{{ old('metadata_json',json_encode($entry->metadata??[],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea></label>@endunless @if($type !== 'announcement' && !in_array($type,$academicTypes,true))<label class="remember"><input type="checkbox" name="published" value="1" @checked(old('published',$entry->published))> Tayang di website</label>@endif
    @endif
    <div class="actions"><a class="secondary" href="{{ $type === 'extracurricular' ? $panelRoute('admin.dashboard').'#ekstrakurikuler' : (in_array($type,['learning_activity','education_calendar','academic_program','academic_assessment'],true) ? $panelRoute('admin.settings.edit',['section'=>'academic','edit'=>1]) : $panelRoute('admin.content.index',$type)) }}">{{ $type === 'extracurricular' ? 'Kembali ke Ekstrakurikuler' : 'Kembali' }}</a><button class="primary">Simpan</button></div>
</form>
@if($type === 'extracurricular')
    @if($entry->exists && $entry->photos->isNotEmpty())
        <div class="extracurricular-photo-list" aria-label="Foto kegiatan tersimpan">
            @foreach($entry->photos as $photo)
                <figure>
                    <img src="{{ Storage::disk('public')->url($photo->path) }}" alt="Foto kegiatan {{ $entry->title }}">
                    <form method="post" action="{{ $panelRoute('admin.content.photos.destroy',[$entry,$photo]) }}">
                        @csrf @method('DELETE')
                        <button class="tiny deny" type="submit" aria-label="Hapus foto kegiatan {{ $loop->iteration }}">Hapus foto</button>
                    </form>
                </figure>
            @endforeach
        </div>
    @endif
<script>
(() => {
    const input = document.getElementById('extracurricularPhotos');
    const preview = document.getElementById('extracurricularPhotosPreview');
    const count = document.getElementById('extracurricularPhotosCount');
    if (!input || !preview || !count) return;

    let selectedFiles = [];
    const render = () => {
        preview.querySelectorAll('img').forEach((image) => URL.revokeObjectURL(image.src));
        preview.replaceChildren();
        const transfer = new DataTransfer();
        selectedFiles.forEach((file, index) => {
            transfer.items.add(file);
            const figure = document.createElement('figure');
            const image = document.createElement('img');
            const remove = document.createElement('button');
            image.src = URL.createObjectURL(file);
            image.alt = `Preview ${file.name}`;
            remove.type = 'button';
            remove.className = 'tiny deny';
            remove.textContent = 'Hapus';
            remove.setAttribute('aria-label', `Hapus ${file.name} dari pilihan`);
            remove.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                render();
            });
            figure.append(image, remove);
            preview.append(figure);
        });
        input.files = transfer.files;
        count.textContent = selectedFiles.length
            ? `${selectedFiles.length} foto dipilih.`
            : 'Belum ada foto baru dipilih.';
    };

    input.addEventListener('change', () => {
        const keys = new Set(selectedFiles.map((file) => `${file.name}:${file.size}:${file.lastModified}`));
        for (const file of input.files) {
            const key = `${file.name}:${file.size}:${file.lastModified}`;
            if (!keys.has(key) && selectedFiles.length < 12) selectedFiles.push(file);
            keys.add(key);
        }
        render();
    });
})();
</script>
@endif
@endsection
