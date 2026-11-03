@extends('layouts.public')

@php
    $schoolName = filled($settings['school_name'] ?? null) ? $settings['school_name'] : 'SMPN 1 Gondang';
    $principalName = filled($settings['principal'] ?? null) ? $settings['principal'] : 'Kepala Sekolah';
    $greeting = trim((string) ($settings['principal_greeting'] ?? ''));
    $paragraphs = $greeting !== '' ? (preg_split('/\R\s*\R/u', $greeting) ?: []) : [];
    $portraitPath = filled($settings['principal_photo_path'] ?? null)
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['principal_photo_path'])
        ? $settings['principal_photo_path']
        : null;
    $bannerPath = null;
    foreach (['hero_photo_path', 'profile_photo_path'] as $bannerSetting) {
        $candidatePath = $settings[$bannerSetting] ?? null;
        if (filled($candidatePath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($candidatePath)) {
            $bannerPath = $candidatePath;
            break;
        }
    }
    $nameParts = preg_split('/\s+/u', trim($principalName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $principalInitials = mb_strtoupper(mb_substr($nameParts[0] ?? 'K', 0, 1).(isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));
@endphp

@section('title', 'Sambutan Kepala Sekolah — '.$schoolName)
@section('meta_title', 'Sambutan Kepala Sekolah — '.$schoolName)
@section('meta_description', 'Sambutan Kepala Sekolah '.$schoolName.'.')

@section('content')
    <section class="section principal-greeting-page" id="profil">
        <div class="container principal-greeting-layout">
            <a class="principal-greeting-back" href="{{ route('home') }}#profil">← Kembali ke Profil</a>

            <article class="principal-greeting-card">
                <div class="principal-greeting-banner">
                    @if($bannerPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($bannerPath) }}" alt="Lingkungan {{ $schoolName }}" fetchpriority="high">
                    @else
                        <div class="principal-greeting-banner-placeholder" aria-hidden="true"><span>{{ $schoolName }}</span></div>
                    @endif
                </div>

                <div class="principal-greeting-portrait">
                    @if($portraitPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($portraitPath) }}" alt="Foto {{ $principalName }}">
                    @else
                        <span role="img" aria-label="Foto kepala sekolah belum tersedia">{{ $principalInitials }}</span>
                    @endif
                </div>

                <header class="principal-greeting-header">
                    <span class="eyebrow">Pesan dari sekolah</span>
                    <h1>Sambutan Kepala Sekolah</h1>
                    <p>{{ $principalName }}</p>
                </header>

                <div class="principal-greeting-body">
                    @forelse($paragraphs as $paragraph)
                        @if(filled(trim($paragraph)))<p>{!! nl2br(e(trim($paragraph))) !!}</p>@endif
                    @empty
                        <p class="principal-greeting-empty">Sambutan kepala sekolah sedang disiapkan.</p>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
@endsection
