@extends('layouts.public')
@section('title','Informasi SPMB — '.($settings['school_name'] ?? 'SINAGA'))
@section('content')
<div class="spmb-visitor container">
    <h1 class="spmb-visually-hidden">Informasi SPMB</h1>

    <section class="spmb-visitor-poster" aria-label="Poster utama SPMB">
        @if($spmb?->poster_path)
            <img src="{{ Storage::disk('public')->url($spmb->poster_path) }}" alt="Poster utama SPMB">
        @else
            <div class="spmb-visitor-empty-poster"><span aria-hidden="true">▧</span><strong>Poster SPMB belum tersedia</strong></div>
        @endif
    </section>

    <section class="spmb-visitor-card spmb-visitor-description" aria-labelledby="spmbDescriptionTitle">
        <div class="spmb-visitor-card-heading"><h2 id="spmbDescriptionTitle">Keterangan tambahan</h2></div>
        <div class="spmb-visitor-card-body">{{ filled($spmb?->introduction) ? $spmb->introduction : 'Informasi tambahan SPMB belum tersedia.' }}</div>
    </section>

    <section class="spmb-visitor-card spmb-visitor-contact" aria-labelledby="spmbContactTitle">
        <div class="spmb-visitor-card-heading"><h2 id="spmbContactTitle">Contact Person</h2></div>
        @if(filled($spmb?->contact_whatsapp))
            <p>Hubungi kami melalui WhatsApp jika membutuhkan informasi lebih lanjut.</p>
            <a class="spmb-whatsapp-button" href="https://wa.me/{{ $spmb->contact_whatsapp }}" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">☏</span><span>Hubungi Contact Person</span></a>
        @else
            <p class="spmb-visitor-contact-empty">Nomor WhatsApp Contact Person belum tersedia.</p>
        @endif
    </section>
</div>
@endsection
