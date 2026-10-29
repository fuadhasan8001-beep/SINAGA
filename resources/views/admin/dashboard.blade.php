@extends('layouts.admin')

@section('title','Ringkasan')
@section('heading','Ringkasan')
@section('subtitle','Pantau konten sekolah dan lanjutkan pekerjaan penting dari satu tempat.')

@section('content')
<div class="dashboard-intro">
    <div>
        <span class="eyebrow">Ringkasan konten</span>
        <h2>Selamat datang, {{ Str::before(auth()->user()->name,' ') }}.</h2>
        <p>Kelola informasi sekolah yang tampil untuk siswa, orang tua, dan masyarakat.</p>
    </div>
    <div class="quick-actions">
        <a class="primary" href="{{ route('admin.articles.create') }}">＋ Tulis Artikel</a>
        @if(auth()->user()->isAdmin())
            <a class="secondary" href="{{ route('admin.content.create','announcement') }}">＋ Buat Pengumuman</a>
        @endif
    </div>
</div>

<div class="cards">
    <div class="stat stat-feature"><span>Artikel</span><strong>{{ $counts['total'] }}</strong><small>{{ $counts['published'] }} telah diterbitkan</small></div>
    <div class="stat"><span>Draf</span><strong>{{ $counts['draft'] }}</strong><small>Siap untuk dilanjutkan</small></div>
    <div class="stat"><span>Menunggu persetujuan</span><strong>{{ $counts['pending'] }}</strong><small>Perlu ditinjau</small></div>
    @if(auth()->user()->isAdmin())
        <div class="stat"><span>Pengumuman</span><strong>{{ $counts['announcement'] }}</strong><small>Konten informasi</small></div>
        <div class="stat"><span>Galeri</span><strong>{{ $counts['gallery'] }}</strong><small>Foto & dokumentasi</small></div>
        <div class="stat"><span>Ekstrakurikuler</span><strong>{{ $counts['extracurricular'] }}</strong><small>Program kegiatan</small></div>
    @endif
</div>

<div class="panel">
    <div class="panel-head"><h2>Artikel terbaru</h2><a class="secondary" href="{{ route('admin.articles.index') }}">Lihat semua</a></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Artikel</th>
                    @if(auth()->user()->isAdmin())<th>Penulis</th>@endif
                    <th>Status</th><th>Diperbarui</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td class="title-cell">
                            {{ $article->title }}
                            <span class="subline">{{ $article->category?->name??'Tanpa kategori' }}
                                @if($article->revision_note) · Catatan Admin: {{ $article->revision_note }} @endif
                            </span>
                        </td>
                        @if(auth()->user()->isAdmin())<td>{{ $article->author->name }}</td>@endif
                        <td><span class="pill {{ $article->status }}">{{ ['draft'=>'Draf','pending'=>'Menunggu persetujuan','revision'=>'Perlu revisi','published'=>'Diterbitkan','rejected'=>'Ditolak'][$article->status] }}</span></td>
                        <td>{{ $article->updated_at->format('d M Y H:i') }}</td>
                        <td>
                            @if(auth()->user()->isAdmin()||in_array($article->status,['draft','revision','rejected']))
                                <a class="tiny" href="{{ route('admin.articles.edit',$article) }}">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Belum ada artikel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(auth()->user()->isAdmin())
    <div class="panel">
        <div class="panel-head"><h2>Aktivitas terbaru</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Aktivitas</th><th>Pengguna</th><th>Waktu</th></tr></thead>
                <tbody>
                    @forelse($activity as $item)
                        <tr><td>{{ $item->action }} · {{ $item->detail }}</td><td>{{ $item->user?->name??'Pengguna dihapus' }}</td><td>{{ $item->created_at->format('d M Y H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if(!auth()->user()->isAdmin())
    <a class="primary" href="{{ route('admin.articles.create') }}">＋ Tulis Artikel</a>
@endif
@endsection
