@extends('layouts.public')
@php
    $spmbTitle = $spmb?->title ?: 'Penerimaan Murid Baru';
    $design = is_array($spmb?->design_settings) ? $spmb->design_settings : [];
    $fontFamilies = ['jakarta' => '"Plus Jakarta Sans", system-ui, sans-serif', 'system' => 'system-ui, sans-serif', 'serif' => 'Georgia, serif'];
    $fontFamily = $fontFamilies[$design['font_family'] ?? 'jakarta'] ?? $fontFamilies['jakarta'];
    $fontSizeValue = (string)($design['font_size'] ?? '16');
    $fontSize = in_array($fontSizeValue, ['14','15','16','17','18','20'], true) ? (int)$fontSizeValue : 16;
    $validColor = static fn (mixed $color, string $fallback): string => is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : $fallback;
    $textColor = $validColor($design['text_color'] ?? null, '#43564a');
    $backgroundColor = $validColor($design['background_color'] ?? null, '#f6faf7');
    $cardColor = $validColor($design['card_color'] ?? null, '#ffffff');
    $accentColor = $validColor($design['accent_color'] ?? null, '#166534');
    $cardRadiusValue = (int)($design['card_radius'] ?? 16);
    $cardRadius = in_array($cardRadiusValue, [8,12,16,20,24,28], true) ? $cardRadiusValue : 16;
    $posterWidthValue = (int)($design['poster_width'] ?? 680);
    $posterWidth = in_array($posterWidthValue, [420,520,620,680,760,900], true) ? $posterWidthValue : 680;
    $posterFitValue = $design['poster_fit'] ?? 'contain';
    $posterFit = in_array($posterFitValue, ['contain','cover'], true) ? $posterFitValue : 'contain';
    $whatsapp = preg_replace('/\D+/', '', (string)($spmb?->contact_whatsapp ?? ''));
    $validWhatsapp = preg_match('/^62[0-9]{8,13}$/', $whatsapp) === 1;
    $announcementBlocks = [];
    foreach (preg_split('/\R\s*\R/u', trim((string)($spmb?->introduction ?? '')), -1, PREG_SPLIT_NO_EMPTY) as $block) {
        $lines = array_values(array_filter(preg_split('/\R/u', trim($block)), fn ($line) => trim($line) !== ''));
        $isList = count($lines) > 0 && collect($lines)->every(fn ($line) => preg_match('/^\s*(?:[-*•▪◦✓✅]|\d+[.)])\s+\S/u', $line) === 1);
        $announcementBlocks[] = [
            'type' => $isList ? (preg_match('/^\s*\d+[.)]/u', $lines[0]) === 1 ? 'ordered' : 'unordered') : 'paragraph',
            'text' => trim($block),
            'items' => $isList ? array_map(fn ($line) => preg_replace('/^\s*(?:[-*•▪◦✓✅]|\d+[.)])\s+/u', '', trim($line)), $lines) : [],
        ];
    }
@endphp
@section('title', $spmbTitle.' — '.($settings['school_name'] ?? 'SINAGA'))
@section('content')
<div class="spmb-visitor container" style="--spmb-font:{{ $fontFamily }};--spmb-body-size:{{ $fontSize }}px;--spmb-text:{{ $textColor }};--spmb-page-bg:{{ $backgroundColor }};--spmb-card-bg:{{ $cardColor }};--spmb-accent:{{ $accentColor }};--spmb-radius:{{ $cardRadius }}px;--spmb-poster-width:{{ $posterWidth }}px;--spmb-poster-fit:{{ $posterFit }}">
    <div class="spmb-visitor-main">
        <section class="spmb-visitor-card spmb-visitor-poster-card" aria-label="Poster SPMB">
            <figure class="spmb-visitor-poster">
                @if($spmb?->poster_path)
                    <img src="{{ Storage::disk('public')->url($spmb->poster_path) }}" alt="Poster {{ $spmbTitle }}">
                @else
                    <div class="spmb-visitor-empty-poster"><span aria-hidden="true">▧</span><strong>Poster SPMB belum tersedia</strong></div>
                @endif
            </figure>
            <p class="spmb-visitor-poster-caption">Poster SPMB</p>
        </section>

        <section class="spmb-visitor-card spmb-visitor-announcement" aria-labelledby="spmbAnnouncementTitle">
            <div class="spmb-visitor-card-heading"><h1 id="spmbAnnouncementTitle">📢 {{ $spmbTitle }}</h1></div>
            @if(filled($spmb?->school_year))<p class="spmb-visitor-year">Tahun ajaran {{ $spmb->school_year }}</p>@endif
            <div class="spmb-visitor-announcement-body">
                @forelse($announcementBlocks as $block)
                    @if($block['type'] === 'ordered')
                        <ol class="spmb-visitor-announcement-list">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ol>
                    @elseif($block['type'] === 'unordered')
                        <ul class="spmb-visitor-announcement-list">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @else
                        <p>{!! nl2br(e($block['text'])) !!}</p>
                    @endif
                @empty
                    <p>Informasi SPMB belum tersedia.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="spmb-visitor-card spmb-visitor-contact" aria-labelledby="spmbContactTitle">
        <div class="spmb-visitor-card-heading"><h2 id="spmbContactTitle">Contact Person</h2></div>
        <p>Hubungi panitia SPMB untuk mendapatkan informasi lebih lanjut.</p>
        <div class="spmb-visitor-contact-details">
            @if(filled($spmb?->contact_name))<strong>{{ $spmb->contact_name }}</strong>@endif
            @if(filled($spmb?->contact_role))<span>{{ $spmb->contact_role }}</span>@endif
            @if($validWhatsapp)<span>+{{ $whatsapp }}</span>@endif
            @if(!filled($spmb?->contact_name) && !filled($spmb?->contact_role) && ! $validWhatsapp)<span class="spmb-visitor-contact-empty">Informasi nomor contact person belum tersedia.</span>@endif
        </div>
        @if($validWhatsapp)
            <a class="spmb-whatsapp-button" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M12 3a8.8 8.8 0 0 0-7.56 13.31L3.3 20.7l4.52-1.09A8.8 8.8 0 1 0 12 3Zm0 15.8a7 7 0 0 1-3.57-.98l-.26-.16-2.68.65.67-2.61-.17-.27A7 7 0 1 1 12 18.8Zm3.84-5.26c-.21-.1-1.26-.62-1.45-.69-.2-.07-.34-.1-.48.1-.14.21-.55.69-.67.83-.13.14-.25.16-.46.05-.21-.1-.89-.33-1.69-1.05-.62-.55-1.04-1.23-1.16-1.44-.12-.2-.01-.32.09-.42.09-.09.2-.24.31-.36.1-.12.13-.2.2-.34.07-.14.03-.26-.02-.36-.05-.1-.48-1.15-.66-1.58-.18-.41-.35-.35-.48-.35h-.41c-.14 0-.36.05-.55.26-.19.21-.73.72-.73 1.76s.75 2.04.85 2.18c.11.14 1.47 2.24 3.56 3.14.5.22.9.35 1.2.45.5.16.96.14 1.32.08.4-.06 1.26-.51 1.44-1 .18-.5.18-.92.13-1-.06-.09-.2-.14-.42-.25Z"/></svg>
                <span>Hubungi Panitia</span>
            </a>
        @endif
    </section>
</div>
@endsection
