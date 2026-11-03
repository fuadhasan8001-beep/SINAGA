@extends('layouts.admin')
@php
    $groups = [
        'profile' => ['title' => 'Kelola Profil', 'subtitle' => 'Kelola identitas dan informasi profil sekolah.', 'keys' => ['school_name','principal','history','vision','mission','principal_greeting']],
        'hero' => ['title' => 'Kelola Hero', 'subtitle' => 'Atur tampilan Hero Beranda, foto latar, dan foto identitas sekolah.', 'keys' => ['hero_label','hero_title','hero_description','hero_photo_position']],
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
    $heroMedia = [
        'hero' => ['label'=>'Foto latar Hero Beranda','path'=>$photoPaths['hero']],
        'logo' => ['label'=>'Logo sekolah','path'=>$photoPaths['logo']],
        'profile' => ['label'=>'Foto profil sekolah','path'=>$photoPaths['profile']],
        'principal' => ['label'=>'Foto kepala sekolah','path'=>$photoPaths['principal']],
    ];
@endphp
<section class="panel hero-media-panel">
    <div class="panel-head"><div><h2>Foto Hero dan Identitas Visual</h2><p>Pratinjau dan ganti foto yang dipakai di Beranda serta identitas visual sekolah.</p></div></div>
    <div class="hero-media-grid">
        @foreach($heroMedia as $placement=>$media)
            <article class="hero-media-card hero-media-card--{{ $placement }}">
                <h3>{{ $media['label'] }}</h3>
                @if(filled($media['path']))<img src="{{ Storage::disk('public')->url($media['path']) }}" alt="Pratinjau {{ strtolower($media['label']) }}" loading="lazy">@else<div class="hero-media-empty">Belum ada foto</div>@endif
                <a class="secondary" href="{{ route('admin.photos.edit',$placement) }}">{{ filled($media['path']) ? 'Ganti foto' : 'Pilih foto' }}</a>
            </article>
        @endforeach
    </div>
</section>
<form method="post" action="{{ route('admin.settings.update') }}" class="panel profile-form hero-settings-form">@csrf @method('PUT')<input type="hidden" name="section" value="hero">
<fieldset class="hero-settings-group"><legend>Konten Hero Beranda</legend><p>Label, judul, deskripsi, dan posisi foto ini langsung digunakan pada halaman utama.</p>
@foreach(['hero_label','hero_title','hero_description'] as $key) @php($label = $fields[$key])
<label>{{ $label }}@if($key === 'hero_description')<textarea name="settings[{{ $key }}]" rows="3" maxlength="500">{{ old('settings.'.$key,$settings[$key]??'') }}</textarea>@else<input name="settings[{{ $key }}]" maxlength="{{ $key === 'hero_title' ? 180 : 80 }}" value="{{ old('settings.'.$key,$settings[$key]??'') }}">@endif</label>
@endforeach
<label>{{ $fields['hero_photo_position'] }}<select name="settings[hero_photo_position]">@foreach(['center'=>'Tengah','left'=>'Kiri','right'=>'Kanan','top'=>'Atas','bottom'=>'Bawah'] as $value=>$label)<option value="{{ $value }}" @selected(old('settings.hero_photo_position',$settings['hero_photo_position']??'center')===$value)>{{ $label }}</option>@endforeach</select></label>
</fieldset>
<div class="actions"><a class="secondary" href="{{ route('admin.settings.edit',['section'=>'hero']) }}">Batal</a><button class="primary">Simpan Pengaturan Hero</button></div></form>
@elseif(in_array($section,['profile','contact'],true))
<form method="post" action="{{ route('admin.settings.update') }}" class="panel profile-form">@csrf @method('PUT')<input type="hidden" name="section" value="{{ $section }}">
@foreach($activeGroup['keys'] as $key) @php($label = $fields[$key])
@if($key === 'mission')
    <label>{{ $label }}<textarea name="settings[mission]" rows="5" placeholder="Tulis tiap poin misi pada baris terpisah agar tampil rapi di website.">{{ old('settings.mission',is_array($settings['mission'] ?? null) ? implode("\n",$settings['mission']) : ($settings['mission'] ?? '')) }}</textarea></label>
@else
<label>{{ $label }}@if(in_array($key,$textareaKeys,true))<textarea name="settings[{{ $key }}]" rows="{{ $key === 'principal_greeting' ? 7 : 3 }}">{{ old('settings.'.$key,$settings[$key]??'') }}</textarea>@else<input name="settings[{{ $key }}]" value="{{ old('settings.'.$key,$settings[$key]??'') }}">@endif</label>
@endif
@endforeach
<div class="actions"><a class="secondary" href="{{ route('admin.settings.edit',['section'=>$section]) }}">Batal</a><button class="primary">Simpan {{ strtolower($activeGroup['title']) }}</button></div></form>
@endif
@if($section === 'academic')
@php($academicGroups=['learning_activity'=>'Kegiatan Pembelajaran','education_calendar'=>'Kalender Pendidikan','academic_program'=>'Program Ekstrakurikuler','academic_assessment'=>'Asesmen Akademik'])
<div class="academic-management">@foreach($academicGroups as $type=>$title)<section class="panel"><div class="panel-head"><div><h2>{{ $title }}</h2><p>Kelola judul, keterangan, dan foto pendukung opsional.</p></div><a class="primary" href="{{ route('admin.content.create',$type) }}">＋ Tambah</a></div><div class="table-wrap"><table><thead><tr><th>Judul / keterangan</th><th>Foto</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>@forelse($academicEntries->get($type,collect()) as $entry)<tr><td class="title-cell">{{ $entry->title }}<span class="subline">{{ Str::limit($entry->excerpt ?: strip_tags($entry->body),140) }}</span></td><td>@if($entry->image_path)<img class="spmb-photo-thumb" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto {{ $title }}">@else — @endif</td><td><span class="pill {{ $entry->published?'published':'draft' }}">{{ $entry->published?'Tayang':'Draf' }}</span></td><td><div class="row-actions"><a class="tiny" href="{{ route('admin.content.edit',[$type,$entry]) }}">Edit</a><form method="post" action="{{ route('admin.content.destroy',[$type,$entry]) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus data ini?')">Hapus</button></form></div></td></tr>@empty<tr><td colspan="4" class="empty">Belum ada data {{ strtolower($title) }}.</td></tr>@endforelse</tbody></table></div></section>@endforeach</div>
@endif
@endsection
