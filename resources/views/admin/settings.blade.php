@extends('layouts.admin')
@php
    $groups = [
        'profile' => ['title' => 'Kelola Profil', 'subtitle' => 'Kelola identitas visual dan informasi profil sekolah.', 'keys' => ['school_name','school_tagline','history','vision','mission']],
        'hero' => ['title' => 'Kelola Hero', 'subtitle' => 'Atur foto latar dan tampilan Hero Beranda.', 'keys' => ['hero_label','hero_title','hero_description','hero_photo_position']],
        'academic' => ['title' => 'Akademik', 'subtitle' => 'Kelola empat kelompok informasi akademik sekolah.', 'keys' => []],
        'contact' => ['title' => 'Kontak', 'subtitle' => 'Kelola informasi kontak yang tampil pada bagian kontak dan footer website.', 'keys' => ['address','phone','email','office_hours']],
    ];
    $activeGroup = $groups[$section ?? 'profile'];
    $textareaKeys = ['vision','history','principal_greeting','academic_intro','curriculum','curriculum_summary','learning_program','learning_system','academic_excellence','academic_support','academic_calendar'];
@endphp
@section('title',$activeGroup['title'])@section('heading',$activeGroup['title'])@section('subtitle',$activeGroup['subtitle'])
@section('content')
@if($section === 'hero')
@php
    $heroMedia = ['hero' => ['label'=>'Foto Latar Hero Beranda','path'=>$photoPaths['hero']]];
@endphp
<section class="panel hero-media-panel">
    <div class="panel-head"><div><h2>Foto Latar Hero Beranda</h2><p>Foto ini hanya digunakan sebagai latar bagian Hero Beranda.</p></div></div>
    <div class="hero-media-grid hero-media-grid--hero">
        @foreach($heroMedia as $placement=>$media)
            <article class="hero-media-card hero-media-card--{{ $placement }}">
                <h3>{{ $media['label'] }}</h3>
                @if(filled($media['path']))<img src="{{ Storage::disk('public')->url($media['path']) }}" alt="Pratinjau {{ strtolower($media['label']) }}" loading="lazy">@else<div class="hero-media-empty">Belum ada foto</div>@endif
                <a class="secondary" href="{{ $panelRoute('admin.photos.edit',$placement) }}">Ganti Foto</a>
            </article>
        @endforeach
    </div>
</section>
<form method="post" action="{{ $panelRoute('admin.settings.update') }}" class="panel profile-form hero-settings-form">@csrf @method('PUT')<input type="hidden" name="section" value="hero">
<fieldset class="hero-settings-group"><legend>Konten Hero Beranda</legend><p>Label, judul, deskripsi, dan posisi foto ini langsung digunakan pada halaman utama.</p>
@foreach(['hero_label','hero_title','hero_description'] as $key)
@php $label = $fields[$key]; @endphp
<label>{{ $label }}
    @if($key === 'hero_description')
        <textarea name="settings[{{ $key }}]" rows="3" maxlength="500">{{ old('settings.'.$key,$settings[$key]??'') }}</textarea>
    @else
        <input name="settings[{{ $key }}]" maxlength="{{ $key === 'hero_title' ? 180 : 80 }}" value="{{ old('settings.'.$key,$settings[$key]??'') }}">
    @endif
</label>
@endforeach
<label>{{ $fields['hero_photo_position'] }}<select name="settings[hero_photo_position]">@foreach(['center'=>'Tengah','left'=>'Kiri','right'=>'Kanan','top'=>'Atas','bottom'=>'Bawah'] as $value=>$label)<option value="{{ $value }}" @selected(old('settings.hero_photo_position',$settings['hero_photo_position']??'center')===$value)>{{ $label }}</option>@endforeach</select></label>
</fieldset>
<div class="actions"><a class="secondary" href="{{ $panelRoute('admin.settings.edit',['section'=>'hero']) }}">Batal</a><button class="primary">Simpan Pengaturan Hero</button></div></form>
@endif
@if($section === 'profile')
@php
    $profileMedia = [
        'logo' => ['label'=>'Logo Sekolah','path'=>$photoPaths['logo']],
        'profile' => ['label'=>'Foto Profil Sekolah','path'=>$photoPaths['profile']],
    ];
@endphp
<section class="panel hero-media-panel profile-photos-panel">
    <div class="panel-head"><div><h2>Identitas Visual Sekolah</h2><p>Setiap foto tersimpan terpisah dan digunakan hanya pada lokasi yang sesuai.</p></div></div>
    <div class="hero-media-grid hero-media-grid--profile">
        @foreach($profileMedia as $placement=>$media)
            <article class="hero-media-card hero-media-card--{{ $placement }}">
                <h3>{{ $media['label'] }}</h3>
                @if(filled($media['path']))<img src="{{ Storage::disk('public')->url($media['path']) }}" alt="Pratinjau {{ strtolower($media['label']) }}" loading="lazy">@else<div class="hero-media-empty">Belum ada foto</div>@endif
                <a class="secondary" href="{{ $panelRoute('admin.photos.edit',$placement) }}">Ganti Foto</a>
            </article>
        @endforeach
    </div>
</section>
@endif
@if($section === 'profile')
@php
    $principalPhotoPath = filled($photoPaths['principal'] ?? null) && Storage::disk('public')->exists($photoPaths['principal']) ? $photoPaths['principal'] : null;
    $principalBannerPath = filled($photoPaths['hero'] ?? null) && Storage::disk('public')->exists($photoPaths['hero']) ? $photoPaths['hero'] : null;
    $principalGreetingMedia = [
        [
            'title' => 'Foto Kepala Sekolah',
            'path' => $principalPhotoPath,
            'alt' => 'Pratinjau foto kepala sekolah',
            'preview_class' => 'principal-greeting-media-preview--portrait',
            'help' => 'Foto yang mendampingi sambutan kepala sekolah.',
            'action' => 'Kelola Foto Kepala Sekolah',
            'href' => $panelRoute('admin.photos.edit','principal'),
            'empty' => 'Foto belum tersedia',
        ],
        [
            'title' => 'Gambar Sampul Sambutan',
            'path' => $principalBannerPath,
            'alt' => 'Pratinjau gambar sampul sambutan',
            'preview_class' => 'principal-greeting-media-preview--banner',
            'help' => 'Menggunakan foto Hero yang dikelola di Kelola Hero.',
            'action' => 'Kelola Foto Hero',
            'href' => $panelRoute('admin.settings.edit',['section'=>'hero']),
            'empty' => 'Foto Hero belum tersedia',
        ],
    ];
@endphp
<form method="post" action="{{ $panelRoute('admin.settings.update') }}" class="panel profile-form profile-settings-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="section" value="profile">

    <section class="profile-settings-section">
        <header class="profile-settings-heading">
            <h2>Identitas Sekolah</h2>
            <p>Nama dan ringkasan identitas yang tampil pada website.</p>
        </header>
        <div class="profile-settings-identity-grid">
            <label>{{ $fields['school_name'] }}
                <input name="settings[school_name]" value="{{ old('settings.school_name',$settings['school_name']??'') }}">
            </label>
            <label>{{ $fields['school_tagline'] }}
                <input name="settings[school_tagline]" value="{{ old('settings.school_tagline',$settings['school_tagline']??'') }}">
            </label>
        </div>
    </section>

    <section class="profile-settings-section">
        <header class="profile-settings-heading">
            <h2>Profil, Visi, dan Misi</h2>
            <p>Lengkapi informasi utama sekolah.</p>
        </header>
        <label>{{ $fields['history'] }}
            <textarea name="settings[history]" rows="6">{{ old('settings.history',$settings['history']??'') }}</textarea>
        </label>
        <div class="profile-settings-vision-mission-grid">
            <label>{{ $fields['vision'] }}
                <textarea name="settings[vision]" rows="7">{{ old('settings.vision',$settings['vision']??'') }}</textarea>
            </label>
            <label>{{ $fields['mission'] }}
                <textarea name="settings[mission]" rows="7" placeholder="Tulis tiap poin misi pada baris terpisah.">{{ old('settings.mission',is_array($settings['mission'] ?? null) ? implode("\n",$settings['mission']) : ($settings['mission'] ?? '')) }}</textarea>
                <small class="profile-settings-hint">Setiap baris akan ditampilkan sebagai satu poin misi.</small>
            </label>
        </div>
    </section>

    <section class="profile-settings-section principal-greeting-settings" aria-labelledby="principalGreetingHeading">
        <header class="profile-settings-heading">
            <h2 id="principalGreetingHeading">Kepala Sekolah &amp; Sambutan</h2>
            <p>Kelola nama kepala sekolah, foto, dan sambutan yang tampil pada halaman Profil.</p>
        </header>
        <label>{{ $fields['principal'] }}
            <input name="settings[principal]" maxlength="3000" value="{{ old('settings.principal',$settings['principal']??'') }}">
        </label>
        <div class="principal-greeting-media-grid">
            @foreach($principalGreetingMedia as $media)
                <article class="principal-greeting-media-card">
                    <h3>{{ $media['title'] }}</h3>
                    <div class="principal-greeting-media-preview {{ $media['preview_class'] }}">
                        @if($media['path'])
                            <img src="{{ Storage::disk('public')->url($media['path']) }}" alt="{{ $media['alt'] }}">
                        @else
                            <span>{{ $media['empty'] }}</span>
                        @endif
                    </div>
                    <p class="principal-greeting-media-help">{{ $media['help'] }}</p>
                    <a class="secondary" href="{{ $media['href'] }}">{{ $media['action'] }}</a>
                </article>
            @endforeach
        </div>
        <label>{{ $fields['principal_greeting'] }}
            <textarea name="settings[principal_greeting]" rows="10" maxlength="3000">{{ old('settings.principal_greeting',$settings['principal_greeting']??'') }}</textarea>
            <small class="profile-settings-hint">Pisahkan paragraf dengan satu baris kosong. Maksimal 3.000 karakter.</small>
        </label>
    </section>

    <div class="actions profile-settings-actions">
        <a class="secondary" href="{{ $panelRoute('admin.settings.edit',['section'=>'profile']) }}">Batal</a>
        <button class="primary" type="submit">Simpan {{ strtolower($activeGroup['title']) }}</button>
    </div>
</form>
@elseif($section === 'contact')
<form method="post" action="{{ $panelRoute('admin.settings.update') }}" class="panel profile-form">@csrf @method('PUT')<input type="hidden" name="section" value="contact">
@foreach($activeGroup['keys'] as $key)
@php $label = $fields[$key]; @endphp
<label>{{ $label }}@if(in_array($key,$textareaKeys,true) || $key === 'office_hours')<textarea name="settings[{{ $key }}]" rows="{{ $key === 'office_hours' ? 5 : 3 }}">{{ old('settings.'.$key,$settings[$key]??'') }}</textarea>@if($key === 'office_hours')<small class="profile-settings-hint">Satu jadwal per baris, gunakan format Hari | Jam. Contoh: Senin – Kamis | 07.30–13.30.</small>@endif @else<input name="settings[{{ $key }}]" value="{{ old('settings.'.$key,$settings[$key]??'') }}">@endif</label>
@endforeach
<div class="actions"><a class="secondary" href="{{ $panelRoute('admin.settings.edit',['section'=>'contact']) }}">Batal</a><button class="primary">Simpan {{ strtolower($activeGroup['title']) }}</button></div></form>
@endif
@if($section === 'academic')
@php($academicGroups=['learning_activity'=>'Kegiatan Pembelajaran','education_calendar'=>'Kalender Pendidikan','academic_program'=>'Program Ekstrakurikuler','academic_assessment'=>'Asesmen Akademik'])
<div class="academic-management">@foreach($academicGroups as $type=>$title)<section class="panel"><div class="panel-head"><div><h2>{{ $title }}</h2><p>Kelola judul, keterangan, dan foto pendukung opsional.</p></div><a class="primary" href="{{ $panelRoute('admin.content.create',$type) }}">＋ Tambah</a></div><div class="table-wrap"><table><thead><tr><th>Judul / keterangan</th><th>Foto</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>@forelse($academicEntries->get($type,collect()) as $entry)<tr><td class="title-cell">{{ $entry->title }}<span class="subline">{{ Str::limit($entry->excerpt ?: strip_tags($entry->body),140) }}</span></td><td>@if($entry->image_path)<img class="spmb-photo-thumb" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto {{ $title }}">@else — @endif</td><td><span class="pill {{ $entry->published?'published':'draft' }}">{{ $entry->published?'Tayang':'Draf' }}</span></td><td><div class="row-actions"><a class="tiny" href="{{ $panelRoute('admin.content.edit',[$type,$entry]) }}">Edit</a><form method="post" action="{{ $panelRoute('admin.content.destroy',[$type,$entry]) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus data ini?')">Hapus</button></form></div></td></tr>@empty<tr><td colspan="4" class="empty">Belum ada data {{ strtolower($title) }}.</td></tr>@endforelse</tbody></table></div></section>@endforeach</div>
@endif
@endsection
