@extends('layouts.admin')
@section('title','Pesan Kontak')
@section('heading','Pesan Kontak')
@section('subtitle','Pesan yang dikirim dari halaman publik tersimpan di database.')
@section('content')
<div class="panel message-list-panel">
    <div class="table-wrap message-table-wrap">
        <table>
            <thead><tr><th>Pengirim</th><th>Subjek & pesan</th><th>Diterima</th><th>Status</th><th>Tindakan</th></tr></thead>
            <tbody>
                @forelse($messages as $item)
                    <tr>
                        <td><strong>{{ $item->name }}</strong><span class="subline"><a href="mailto:{{ $item->email }}">{{ $item->email }}</a></span></td>
                        <td><strong>{{ $item->subject }}</strong><p>{{ Str::limit($item->message,180) }}</p></td>
                        <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                        <td><span class="pill {{ $item->read_at?'published':'draft' }}">{{ $item->read_at?'Sudah dibaca':'Belum dibaca' }}</span></td>
                        <td><div class="row-actions">
                            @if(!$item->read_at)<form method="post" action="{{ route('admin.messages.read',$item) }}">@csrf @method('PATCH')<button class="tiny">Tandai dibaca</button></form>@endif
                            <form method="post" action="{{ route('admin.messages.destroy',$item) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus pesan ini?')">Hapus</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Belum ada pesan masuk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="responsive-record-list message-card-list">
        @forelse($messages as $item)
            <article class="responsive-record-card message-card">
                <header class="message-card-header">
                    <div class="message-sender"><h2>{{ $item->name }}</h2><a href="mailto:{{ $item->email }}">{{ $item->email }}</a></div>
                    <span class="pill {{ $item->read_at?'published':'draft' }}">{{ $item->read_at?'Sudah dibaca':'Belum dibaca' }}</span>
                </header>
                <div class="message-card-copy">
                    <h3>{{ $item->subject }}</h3>
                    <p>{{ Str::limit($item->message,180) }}</p>
                </div>
                <p class="message-received"><span>Diterima</span><time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->format('d M Y H:i') }}</time></p>
                <div class="record-card-actions message-card-actions">
                    @if(!$item->read_at)<form method="post" action="{{ route('admin.messages.read',$item) }}">@csrf @method('PATCH')<button class="tiny">Tandai dibaca</button></form>@endif
                    <form method="post" action="{{ route('admin.messages.destroy',$item) }}">@csrf @method('DELETE')<button class="tiny deny" onclick="return confirm('Hapus pesan ini?')">Hapus</button></form>
                </div>
            </article>
        @empty
            <p class="responsive-record-empty">Belum ada pesan masuk.</p>
        @endforelse
    </div>
</div>
{{ $messages->links() }}
@endsection
