@extends('layouts.public')

@section('title', $entry->title.' — '.($settings['school_name'] ?? 'SMPN 1 Gondang'))

@section('content')
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
        @if($entry->image_path)
            <figure class="activity-detail-image"><img src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto {{ strtolower($sectionTitle) }}: {{ $entry->title }}" fetchpriority="high" decoding="async"></figure>
        @endif
        <div class="activity-detail-copy">
            @if(filled($entry->excerpt))<p class="activity-detail-excerpt">{{ $entry->excerpt }}</p>@endif
            @if(filled(strip_tags($entry->body ?? '')))<div class="article-content">{!! $entry->body !!}</div>@elseif(!filled($entry->excerpt))<p class="activity-detail-empty">Informasi kegiatan belum ditambahkan.</p>@endif
        </div>
        <a class="activity-back-link navigation-back-link" href="{{ $backUrl }}">Kembali ke {{ strtolower($sectionTitle) }}</a>
    </div>
</article>
@endsection
