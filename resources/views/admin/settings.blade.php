@extends('layouts.admin')
@php
    $groups = [
        'profile' => ['title' => 'Profil Sekolah', 'subtitle' => 'Kelola identitas, informasi pimpinan, visi misi, dan sambutan yang tampil pada bagian profil.', 'keys' => ['school_name','principal','history','vision','mission','principal_greeting']],
        'academic' => ['title' => 'Akademik', 'subtitle' => 'Kelola empat kelompok informasi akademik sekolah.', 'keys' => []],
        'contact' => ['title' => 'Kontak', 'subtitle' => 'Kelola informasi kontak yang tampil pada bagian kontak dan footer website.', 'keys' => ['address','phone','email','office_hours']],
    ];
    $activeGroup = $groups[$section ?? 'profile'];
    $textareaKeys = ['vision','history','principal_greeting','academic_intro','curriculum','curriculum_summary','learning_program','learning_system','academic_excellence','academic_support','academic_calendar'];
@endphp
@section('title',$activeGroup['title'])@section('heading',$activeGroup['title'])@section('subtitle',$activeGroup['subtitle'])
@section('content')
@if($section === 'profile' && $editing)
<div class="panel profile-photos"><div class="panel-head"><div><h2>Foto dan Logo</h2><p>Logo, foto profil, dan foto kepala sekolah dikelola secara terpisah.</p></div></div><div class="profile-photo-actions"><a class="secondary" href="{{ route('admin.photos.edit','logo') }}">Kelola Logo Sekolah</a><a class="secondary" href="{{ route('admin.photos.edit','profile') }}">Kelola Foto Profil Sekolah</a><a class="secondary" href="{{ route('admin.photos.edit','principal') }}">Kelola Foto Kepala Sekolah</a></div></div>
@endif
@if($section !== 'academic')
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
@php($academicGroups=['learning_activity'=>'Kegiatan Pembelajaran','education_calendar'=>'Kalender Pendidikan','academic_program'=>'Program Kokurikuler','academic_assessment'=>'Asesmen Akademik'])
<div class="academic-management">@foreach($academicGroups as $type=>$title)<section class="panel"><div class="panel-head"><div><h2>{{ $title }}</h2><p>Kelola keterangan dan foto pendukung opsional.</p></div><a class="primary" href="{{ route('admin.content.create',$type) }}">＋ Tambah</a></div><div class="table-wrap"><table><thead><tr><th>Keterangan</th><th>Foto</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>@forelse($academicEntries->get($type,collect()) as $entry)<tr><td class="title-cell">{{ Str::limit(strip_tags($entry->body),140) }}</td><td>@if($entry->image_path)<img class="spmb-photo-thumb" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto {{ $title }}">@else — @endif</td><td><span class="pill {{ $entry->published?'published':'draft' }}">{{ $entry->published?'Tayang':'Draf' }}</span></td><td><div class="row-actions"><a class="tiny" href="{{ route('admin.content.edit',[$type,$entry]) }}">Edit</a><form method="post" action="{{ route('admin.content.destroy',[$type,$entry]) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus data ini?')">Hapus</button></form></div></td></tr>@empty<tr><td colspan="4" class="empty">Belum ada data {{ strtolower($title) }}.</td></tr>@endforelse</tbody></table></div></section>@endforeach</div>
@endif
@endsection
