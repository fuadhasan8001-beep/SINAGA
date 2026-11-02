@extends('layouts.admin')
@section('title','Aktivitas Pengelolaan')
@section('heading','Aktivitas Pengelolaan')
@section('subtitle','Ringkasan akun dan perubahan terbaru di website sekolah.')
@section('content')
<div class="stats-grid">
    <div class="stat"><span>Konten website</span><strong>{{ $counts['content'] }}</strong><small>Data konten tersimpan</small></div>
    <div class="stat"><span>Akun Guru aktif</span><strong>{{ $counts['teachers'] }}</strong><small>Akun yang dapat masuk ke panel</small></div>
</div>
<div class="panel">
    <div class="panel-head"><h2>Perubahan terbaru</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Aktivitas</th><th>Pengguna</th><th>Waktu</th></tr></thead><tbody>
        @forelse($activity as $item)
            <tr><td class="title-cell">{{ $item->action }}<span class="subline">{{ $item->detail }}</span></td><td>{{ $item->user?->name ?? 'Pengguna dihapus' }}</td><td>{{ $item->created_at->format('d M Y H:i') }}</td></tr>
        @empty
            <tr><td colspan="3" class="empty">Belum ada aktivitas yang tercatat.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endsection
