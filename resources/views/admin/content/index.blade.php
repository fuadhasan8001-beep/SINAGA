@extends('layouts.admin')
@section('title',$type === 'gallery' ? 'Kelola Galeri' : $label)
@section('heading',$type === 'gallery' ? 'Kelola Galeri' : $label)
@section('subtitle',$type === 'gallery' ? 'Atur foto dokumentasi dan urutan tampilnya. Seret kartu atau gunakan tombol pindah.' : 'Kelola informasi yang tampil pada website sekolah.')
@section('content')
@if($type === 'gallery')
    <section class="gallery-admin" id="galleryAdmin" data-order-url="{{ route('admin.content.gallery.reorder') }}">
        <header class="gallery-admin-head">
            <div><h2>Foto Galeri ({{ $entries->count() }})</h2><p>Kelola foto, judul, dan urutan kartu yang tampil pada carousel Galeri Sekolah.</p></div>
            <div class="gallery-admin-head-actions">
                <a class="secondary" href="{{ route('home') }}#galeri" target="_blank" rel="noopener">Lihat Galeri</a>
                <a class="primary" href="{{ route('admin.content.create',$type) }}">＋ Tambah Foto</a>
            </div>
        </header>
        <p class="gallery-order-status" id="galleryOrderStatus" role="status" aria-live="polite">Urutan ini digunakan pada carousel publik. Seret kartu, atau gunakan tombol Naik/Turun pada layar sentuh.</p>
        <div class="gallery-admin-grid" id="galleryAdminGrid">
            @forelse($entries as $entry)
                <article class="gallery-admin-card" draggable="true" data-gallery-card data-entry-id="{{ $entry->id }}">
                    <div class="gallery-admin-photo" data-gallery-drag title="Seret untuk memindahkan foto">
                        @if($entry->image_path)<img src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="{{ $entry->title }}" draggable="false">@else<div class="gallery-admin-empty" aria-label="Foto belum tersedia"><span aria-hidden="true">▧</span></div>@endif
                        <span class="gallery-order-number" data-order-number>{{ $loop->iteration }}</span>
                        <span class="gallery-drag-hint" aria-hidden="true">⠿ Seret</span>
                    </div>
                    <div class="gallery-admin-card-body">
                        <h3>{{ $entry->title }}</h3>
                        <div class="gallery-admin-card-actions">
                            <div class="gallery-move-actions" aria-label="Atur posisi {{ $entry->title }}"><button type="button" data-order-move="up" aria-label="Pindahkan {{ $entry->title }} ke atas">Naik</button><button type="button" data-order-move="down" aria-label="Pindahkan {{ $entry->title }} ke bawah">Turun</button></div>
                            <a class="tiny" href="{{ route('admin.content.edit',[$type,$entry]) }}">Edit</a>
                            <form method="post" action="{{ route('admin.content.destroy',[$type,$entry]) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus foto ini dari galeri?')">Hapus</button></form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="gallery-admin-empty-state"><span aria-hidden="true">▧</span><strong>Galeri belum memiliki foto</strong><p>Tambahkan dokumentasi sekolah untuk ditampilkan kepada pengunjung.</p></div>
            @endforelse
        </div>
    </section>
@else
    <div class="panel content-list-panel content-list-panel--{{ $type }}">
        <div class="panel-head content-list-head">
            <h2>{{ $label }} ({{ $entries->total() }})</h2>
            <a class="primary" href="{{ route('admin.content.create',$type) }}">＋ Tambah {{ $label }}</a>
        </div>
        @if($type === 'extracurricular')
            <div class="responsive-record-list extracurricular-record-list">
                @forelse($entries as $entry)
                    <article class="responsive-record-card extracurricular-record-card">
                        <div class="record-card-heading">
                            <h3>{{ $entry->title }}</h3>
                            <span class="pill {{ $entry->published?'published':'draft' }}">{{ $entry->published?'Tayang':'Draf' }}</span>
                        </div>
                        <p class="record-order"><span>Urutan tampil</span><strong>{{ $entry->sort_order }}</strong></p>
                        <div class="record-card-actions">
                            <a class="tiny" href="{{ route('admin.content.edit',[$type,$entry]) }}">Edit</a>
                            <form method="post" action="{{ route('admin.content.destroy',[$type,$entry]) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus ekstrakurikuler ini?')">Hapus</button></form>
                        </div>
                    </article>
                @empty
                    <p class="responsive-record-empty">Belum ada ekstrakurikuler. Data belum dimasukkan.</p>
                @endforelse
            </div>
        @elseif($type === 'announcement')
            <div class="responsive-record-list announcement-record-list">
                @forelse($entries as $entry)
                    <article class="responsive-record-card announcement-record-card">
                        <div class="record-card-heading">
                            <h3>{{ $entry->title }}</h3>
                            <span class="pill {{ $entry->published?'published':'draft' }}">{{ $entry->published?'Tayang':'Draf' }}</span>
                        </div>
                        <p class="announcement-record-description">{{ Str::limit($entry->excerpt ?: strip_tags($entry->body ?? ''),140) }}</p>
                        <div class="announcement-record-meta">
                            <span>{{ $entry->starts_at?->format('d M Y') ?? 'Tanpa tanggal' }}</span>
                            <span>Urutan {{ $entry->sort_order }}</span>
                        </div>
                        <div class="record-card-actions">
                            <a class="tiny" href="{{ route('admin.content.edit',[$type,$entry]) }}">Edit</a>
                            <form method="post" action="{{ route('admin.content.destroy',[$type,$entry]) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus pengumuman ini?')">Hapus</button></form>
                        </div>
                    </article>
                @empty
                    <p class="responsive-record-empty">Belum ada pengumuman. Tambahkan pengumuman pertama.</p>
                @endforelse
            </div>
        @endif
        <div class="table-wrap content-table-wrap"><table><thead><tr><th>{{ $type === 'announcement' ? 'Pengumuman' : 'Judul' }}</th>@if($type !== 'announcement')<th>Status</th>@endif<th>{{ $type === 'announcement' ? 'Tanggal / Urutan' : 'Jadwal/Urutan' }}</th><th>Tindakan</th></tr></thead><tbody>@forelse($entries as $entry)<tr><td class="title-cell">{{ $entry->title }}<span class="subline">{{ Str::limit($entry->excerpt ?: strip_tags($entry->body ?? ''),120) }}</span></td>@if($type !== 'announcement')<td><span class="pill {{ $entry->published?'published':'draft' }}">{{ $entry->published?'Tayang':'Draf' }}</span></td>@endif<td>@if($type === 'announcement')<span class="pill {{ $entry->published?'published':'draft' }}">{{ $entry->published?'Tayang':'Draf' }}</span><span class="subline">{{ $entry->starts_at?->format('d M Y') ?? 'Tanpa tanggal' }} · Urutan {{ $entry->sort_order }}</span>@else{{ $entry->starts_at?->format('d M Y') ?? '—' }}<span class="subline">Urutan {{ $entry->sort_order }}</span>@endif</td><td><div class="row-actions"><a class="tiny" href="{{ route('admin.content.edit',[$type,$entry]) }}">Lihat / Edit</a><form method="post" action="{{ route('admin.content.destroy',[$type,$entry]) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('{{ $type === 'announcement' ? 'Hapus pengumuman ini?' : 'Hapus konten ini?' }}')">Hapus</button></form></div></td></tr>@empty<tr><td colspan="{{ $type === 'announcement' ? 3 : 4 }}" class="empty">Belum ada {{ strtolower($label) }}. Data belum dimasukkan.</td></tr>@endforelse</tbody></table></div>
    </div>
    {{ $entries->links() }}
@endif
@endsection
