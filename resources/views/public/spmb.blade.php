@extends('layouts.public')

@section('title',($spmb?->title??'Informasi SPMB').' — '.($spmb?->school_year??'SINAGA'))

@section('content')
@if(!$spmb)
    <section class="section"><div class="container"><p class="spmb-empty">Informasi SPMB belum diterbitkan. Silakan kembali lagi nanti.</p></div></section>
@else
    <section class="spmb-hero">
        <div class="container spmb-hero-grid {{ $spmb->poster_path?'':'spmb-hero-grid--no-poster' }}">
            <div class="spmb-hero-copy">
                <span class="eyebrow">Penerimaan Murid Baru · {{ $spmb->status === 'open' ? 'Dibuka' : ($spmb->status === 'closed' ? 'Ditutup' : 'Persiapan') }}</span>
                <h1>{{ $spmb->title }}</h1>
                @if($spmb->school_year)<p class="spmb-year">Tahun ajaran {{ $spmb->school_year }}</p>@endif
                @if($spmb->introduction)<p>{{ $spmb->introduction }}</p>@endif
                @if($spmb->status === 'open' && $registrationLink)
                    <a class="btn" href="{{ $registrationLink }}" target="_blank" rel="noopener noreferrer">Daftar Sekarang <span aria-hidden="true">→</span></a>
                @endif
            </div>
            @if($spmb->poster_path)
                <figure class="spmb-poster">
                    <a href="{{ Storage::disk('public')->url($spmb->poster_path) }}" target="_blank" rel="noopener noreferrer" aria-label="Lihat poster SPMB ukuran penuh">
                        <img src="{{ Storage::disk('public')->url($spmb->poster_path) }}" alt="Poster {{ $spmb->title }}">
                    </a>
                    @if($spmb->poster_caption)<figcaption>{{ $spmb->poster_caption }}</figcaption>@endif
                </figure>
            @endif
        </div>
    </section>

    @if($spmb->quota !== null || $spmb->fee_information)
        <section class="section spmb-information" aria-label="Informasi tambahan SPMB">
            <div class="container spmb-facts">
                @if($spmb->quota !== null)<article><span>Kuota resmi</span><strong>{{ $spmb->quota }} murid</strong></article>@endif
                @if($spmb->fee_information)<article><span>Informasi biaya</span><strong>{{ $spmb->fee_information }}</strong></article>@endif
            </div>
        </section>
    @endif

    @if(count($spmb->schedule ?? []))
        <section class="section" id="jadwal">
            <div class="container"><div class="section-head"><span class="eyebrow">Tahapan</span><h2>Jadwal pendaftaran</h2></div>
                <ol class="spmb-schedule">
                    @foreach($spmb->schedule as $item)
                        <li><time>{{ $item['date'] ?? '' }}</time><div><h3>{{ $item['stage'] ?? '' }}</h3>@if(!empty($item['details']))<p>{{ $item['details'] }}</p>@endif</div></li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if(count($spmb->requirements ?? []) || count($spmb->steps ?? []))
        <section class="section soft" id="persyaratan">
            <div class="container spmb-columns {{ count($spmb->requirements ?? []) && count($spmb->steps ?? []) ? '' : 'spmb-columns--single' }}">
                @if(count($spmb->requirements ?? []))<div><span class="eyebrow">Ketentuan</span><h2>Persyaratan</h2><ul class="spmb-list">@foreach($spmb->requirements as $item)<li>{{ $item }}</li>@endforeach</ul></div>@endif
                @if(count($spmb->steps ?? []))<div><span class="eyebrow">Langkah pendaftaran</span><h2>Alur pendaftaran</h2><ol class="spmb-list">@foreach($spmb->steps as $item)<li>{{ $item }}</li>@endforeach</ol></div>@endif
            </div>
        </section>
    @endif

    @if(count($spmb->documents ?? []))
        <section class="section" id="berkas"><div class="container"><span class="eyebrow">Persiapan</span><h2>Berkas yang diperlukan</h2><ul class="spmb-list">@foreach($spmb->documents as $item)<li>{{ $item }}</li>@endforeach</ul></div></section>
    @endif

    @if($announcements->isNotEmpty())
        <section class="section soft" id="pengumuman">
            <div class="container"><div class="section-head"><span class="eyebrow">Informasi terbaru</span><h2>Pengumuman SPMB</h2></div>
                <div class="spmb-announcements">
                    @foreach($announcements as $announcement)
                        @php($announcementBody=trim($announcement->body))
                        <article>
                            <time>{{ ($announcement->starts_at ?? $announcement->created_at)?->translatedFormat('d F Y') }}</time>
                            <h3>{{ $announcement->title }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($announcementBody,280,'…') }}</p>
                            @if(\Illuminate\Support\Str::length($announcementBody)>280)
                                <details><summary>Baca pengumuman lengkap</summary><div class="spmb-announcement-full">{{ $announcementBody }}</div></details>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($links->isNotEmpty())
        <section class="section" id="tautan-penting">
            <div class="container"><div class="section-head"><span class="eyebrow">Akses cepat</span><h2>Tautan penting</h2></div>
                <div class="spmb-important-links">
                    @foreach($links as $link)
                        <a class="spmb-important-link" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">
                            <span class="spmb-link-category">{{ $linkCategories[$link['category']??'other'] ?? 'Informasi lainnya' }}</span>
                            <strong>{{ $link['title'] }}</strong>
                            @if(!empty($link['description']))<span>{{ $link['description'] }}</span>@endif
                            <span class="spmb-link-action">Buka tautan <span aria-hidden="true">↗</span></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($photos->isNotEmpty())
        <section class="section soft" id="dokumentasi"><div class="container"><div class="section-head"><span class="eyebrow">Dokumentasi</span><h2>Kegiatan SPMB</h2></div><div class="spmb-gallery">@foreach($photos as $photo)<figure><img src="{{ Storage::disk('public')->url($photo->image_path) }}" alt="{{ $photo->title }}"><figcaption><strong>{{ $photo->title }}</strong>@if($photo->caption)<p>{{ $photo->caption }}</p>@endif</figcaption></figure>@endforeach</div></div></section>
    @endif

    @if($contacts->isNotEmpty())
        <section class="section" id="contact-person"><div class="container"><div class="section-head"><span class="eyebrow">Bantuan pendaftaran</span><h2>Contact person</h2></div><div class="spmb-contacts">
            @foreach($contacts as $contact)
                <article><h3>{{ $contact->name }}</h3>@if($contact->role)<p>{{ $contact->role }}</p>@endif @if($contact->office_hours)<p>{{ $contact->office_hours }}</p>@endif @if($contact->note)<p>{{ $contact->note }}</p>@endif @if($contact->whatsapp_url)<a class="btn btn-outline" href="{{ $contact->whatsapp_url }}" target="_blank" rel="noopener noreferrer">Hubungi via WhatsApp</a>@endif</article>
            @endforeach
        </div></div></section>
    @endif
@endif
@endsection
