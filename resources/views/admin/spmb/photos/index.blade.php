@extends('layouts.admin')
@section('title','Dokumentasi SPMB')
@section('heading','Dokumentasi SPMB')
@section('subtitle','Kelola foto dokumentasi dan keterangan yang boleh ditampilkan kepada pengunjung.')
@section('content')
@include('admin.spmb._back-dashboard')
<div class="panel"><div class="panel-head"><h2>Foto dokumentasi</h2><a class="primary" href="{{ $panelRoute('admin.spmb.photos.create') }}">Tambah foto</a></div><div class="table-wrap"><table><thead><tr><th>Foto</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($photos as $photo)<tr><td><img class="spmb-photo-thumb" src="{{ Storage::disk('public')->url($photo->image_path) }}" alt="{{ $photo->title }}"><strong>{{ $photo->title }}</strong></td><td>{{ $photo->caption ?: '—' }}</td><td><span class="pill {{ $photo->published?'published':'draft' }}">{{ $photo->published?'Tayang':'Draf' }}</span></td><td class="row-actions"><a class="tiny" href="{{ $panelRoute('admin.spmb.photos.edit',$photo) }}">Edit</a><form method="post" action="{{ $panelRoute('admin.spmb.photos.destroy',$photo) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="tiny deny">Hapus</button></form></td></tr>@empty<tr><td colspan="4" class="empty">Belum ada foto dokumentasi SPMB.</td></tr>@endforelse</tbody></table></div>{{ $photos->links() }}</div>
@endsection
