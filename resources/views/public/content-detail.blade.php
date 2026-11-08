@extends('layouts.public')

@section('title', $entry->title.' — '.($settings['school_name'] ?? 'SMPN 1 Gondang'))

@section('content')
@php
    $detailPhotos = collect();
    if (filled($entry->image_path)) {
        $detailPhotos->push((object) ['path' => $entry->image_path]);
    }
    foreach ($entry->photos as $photo) {
        if (! $detailPhotos->contains(fn ($existing) => $existing->path === $photo->path)) {
            $detailPhotos->push($photo);
        }
    }
@endphp
<article class="activity-detail section">
    <div class="container activity-detail-container">
        <nav class="activity-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ ($homeUrl ?? route('home')) }}#beranda">Beranda</a><span aria-hidden="true">/</span><a href="{{ $backUrl }}">{{ $sectionTitle }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ $entry->title }}</span>
        </nav>
        <header class="activity-detail-header">
            <span class="eyebrow">{{ $sectionTitle }}</span>
            <h1>{{ $entry->title }}</h1>
            @if($entry->starts_at || filled($entry->location))
                <div class="activity-detail-meta">
                    @if($entry->starts_at)<span>{{ $entry->starts_at->translatedFormat('d F Y') }}</span>@endif
                    @if(filled($entry->location))<span>{{ $entry->location }}</span>@endif
                </div>
            @endif
        </header>
        @if($entry->type === 'extracurricular')
            <div class="extracurricular-detail-layout">
                <div class="extracurricular-detail-media">
                    @if($detailPhotos->isNotEmpty())
                        <figure class="activity-detail-image extracurricular-detail-main-image">
                            <a href="{{ Storage::disk('public')->url($detailPhotos->first()->path) }}" target="_blank" rel="noopener noreferrer" aria-label="Lihat foto {{ $entry->title }} ukuran penuh">
                                <img src="{{ Storage::disk('public')->url($detailPhotos->first()->path) }}" alt="Foto ekstrakurikuler: {{ $entry->title }}" fetchpriority="high" decoding="async">
                            </a>
                        </figure>
                        @if($detailPhotos->count() > 1)
                            <div class="extracurricular-detail-gallery" aria-label="Foto lainnya dari {{ $entry->title }}">
                                @foreach($detailPhotos->skip(1) as $photo)
                                    <a href="{{ Storage::disk('public')->url($photo->path) }}" target="_blank" rel="noopener noreferrer" aria-label="Lihat foto {{ $loop->iteration + 1 }} {{ $entry->title }} ukuran penuh">
                                        <img src="{{ Storage::disk('public')->url($photo->path) }}" alt="Dokumentasi {{ $entry->title }} {{ $loop->iteration + 1 }}" loading="lazy" decoding="async">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="extracurricular-detail-image-placeholder" role="img" aria-label="Foto ekstrakurikuler belum tersedia"><span aria-hidden="true">▧</span><span>Foto belum tersedia</span></div>
                    @endif
                </div>
                <div class="activity-detail-copy activity-detail-copy--extracurricular">
                    @if(filled($entry->excerpt))<p class="activity-detail-excerpt">{{ $entry->excerpt }}</p>@endif
                    @if(filled(strip_tags($entry->body ?? '')))<div class="article-content">{!! $entry->body !!}</div>@elseif(!filled($entry->excerpt))<p class="activity-detail-empty">Informasi kegiatan belum ditambahkan.</p>@endif
                </div>
            </div>
        @else
        @if($entry->image_path)
            <figure class="activity-detail-image"><img src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto {{ strtolower($sectionTitle) }}: {{ $entry->title }}" fetchpriority="high" decoding="async"></figure>
        @endif
        <div class="activity-detail-copy">
            @if(filled($entry->excerpt))<p class="activity-detail-excerpt">{{ $entry->excerpt }}</p>@endif
            @if(filled(strip_tags($entry->body ?? '')))<div class="article-content">{!! $entry->body !!}</div>@elseif(!filled($entry->excerpt))<p class="activity-detail-empty">Informasi kegiatan belum ditambahkan.</p>@endif
        </div>
        @endif
        <a class="activity-back-link navigation-back-link" href="{{ $backUrl }}">Kembali ke {{ strtolower($sectionTitle) }}</a>
    </div>
</article>
@endsection
