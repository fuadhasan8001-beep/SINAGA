@extends('layouts.admin')
@section('title','Dashboard SPMB')
@section('heading','Dashboard SPMB')
@section('subtitle','Kelola informasi penerimaan murid baru website sekolah.')
@section('content')
<nav class="spmb-dashboard-cards" aria-label="Pengelolaan SPMB">
    <a class="spmb-dashboard-card" href="{{ $panelRoute('admin.spmb.poster.edit') }}">
        <span class="spmb-dashboard-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 3.75h14a1.25 1.25 0 0 1 1.25 1.25v14A1.25 1.25 0 0 1 19 20.25H5A1.25 1.25 0 0 1 3.75 19V5A1.25 1.25 0 0 1 5 3.75Z"/><path d="M7.5 8h9m-9 4h9m-9 4h5"/></svg></span>
        <span class="spmb-dashboard-card-copy"><strong>Poster SPMB</strong><span>Kelola poster informasi penerimaan murid baru.</span></span>
        <span class="spmb-dashboard-arrow" aria-hidden="true">→</span>
    </a>
    <a class="spmb-dashboard-card" href="{{ $panelRoute('admin.spmb.announcements.index') }}">
        <span class="spmb-dashboard-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 10v4h3l9 4V6l-9 4H4Z"/><path d="m7 14 1.5 5h3L10 15m9-6a4 4 0 0 1 0 6"/></svg></span>
        <span class="spmb-dashboard-card-copy"><strong>Pengumuman SPMB</strong><span>Tambah, edit, dan kelola pengumuman SPMB.</span></span>
        <span class="spmb-dashboard-arrow" aria-hidden="true">→</span>
    </a>
    <a class="spmb-dashboard-card" href="{{ $panelRoute('admin.spmb.links.index') }}">
        <span class="spmb-dashboard-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 13.5 14 9.5m-6.5 7H6a4 4 0 0 1 0-8h4m4-1h4a4 4 0 0 1 0 8h-4"/></svg></span>
        <span class="spmb-dashboard-card-copy"><strong>Tautan Penting</strong><span>Kelola link pendaftaran, panduan, dan informasi penting lainnya.</span></span>
        <span class="spmb-dashboard-arrow" aria-hidden="true">→</span>
    </a>
    <a class="spmb-dashboard-card" href="{{ $panelRoute('admin.spmb.contacts.index') }}">
        <span class="spmb-dashboard-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19a5.25 5.25 0 0 1 10.5 0m3-8.5a3 3 0 0 1 0 5.8m.75 1.2a4 4 0 0 1 2.25 3.5"/></svg></span>
        <span class="spmb-dashboard-card-copy"><strong>Contact Person</strong><span>Kelola informasi kontak dan WhatsApp petugas SPMB.</span></span>
        <span class="spmb-dashboard-arrow" aria-hidden="true">→</span>
    </a>
</nav>
@endsection
