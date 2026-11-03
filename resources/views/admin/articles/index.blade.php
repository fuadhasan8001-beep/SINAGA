@extends('layouts.admin')
@section('title', 'Artikel')
@section('heading', 'Artikel')
@section('subtitle', 'Kelola artikel dan pantau status publikasi.')

@section('content')
    <div class="panel">
        <div class="panel-head"><h2>Semua artikel</h2><a class="primary" href="{{ $panelRoute('admin.articles.create') }}">＋ Tulis Artikel</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Artikel</th><th>Tanggal terbit</th><th>Diunggah oleh</th><th>Status</th><th>Diperbarui</th><th>Tindakan</th></tr></thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td class="title-cell">{{ $article->title }}<span class="subline">{{ $article->category?->name ?? 'Tanpa kategori' }} @if($article->revision_note) · Catatan Admin: {{ $article->revision_note }} @endif</span></td>
                            <td>{{ $article->published_at?->translatedFormat('d F Y') ?? 'Belum ditetapkan' }}</td>
                            <td>{{ $article->author_display_name }}@if($article->last_editor_display_name && $article->last_editor_display_name !== $article->author_display_name)<span class="subline">Terakhir diedit: {{ $article->last_editor_display_name }}</span>@endif</td>
                            <td><span class="pill {{ $article->status }}">{{ ['draft'=>'Draf','pending'=>'Menunggu persetujuan','revision'=>'Perlu revisi','published'=>'Diterbitkan','rejected'=>'Ditolak'][$article->status] }}</span></td>
                            <td>{{ $article->updated_at->translatedFormat('d M Y H:i') }}</td>
                            <td>
                                <div class="row-actions">
                                    @can('update', $article)<a class="tiny" href="{{ $panelRoute('admin.articles.edit', $article) }}">Edit</a>@endcan
                                    @if($article->status === 'published')<a class="tiny" href="{{ route('information.article', $article->slug) }}" target="_blank" rel="noopener">Lihat</a>@if(auth()->user()->isAdmin())<form method="post" action="{{ $panelRoute('admin.articles.unpublish', $article) }}">@csrf<button class="tiny" type="button" data-confirm-action data-confirm-title="Batalkan publikasi?" data-confirm-description="Artikel ini tidak lagi tampil pada halaman Informasi publik." data-confirm-label="Ya, Batalkan">Batalkan publikasi</button></form>@endif @endif
                                    @can('delete', $article)<form method="post" action="{{ $panelRoute('admin.articles.destroy', $article) }}">@csrf @method('DELETE')<button class="tiny deny" type="button" data-confirm-action data-confirm-title="Hapus artikel ini?" data-confirm-description="Artikel dan foto sampul yang tidak digunakan konten lain akan dihapus. Tindakan ini tidak dapat dibatalkan." data-confirm-label="Ya, Hapus Artikel">Hapus</button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Belum ada artikel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $articles->links() }}
@endsection
