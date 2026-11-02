@extends('layouts.public')

@section('title',$article->title.' — '.($settings['school_name']??'Website Sekolah'))
@section('content')
<article class="article-page section">
    <div class="container article-shell">
        <a class="back article-back" href="{{ ($adminMode ?? false) ? route('admin.dashboard') : route('home') }}">Kembali ke beranda</a>
        <header class="article-header">
            <span class="eyebrow">{{ $article->category?->name ?? 'Berita sekolah' }}</span>
            <h1>{{ $article->title }}</h1>
            <p class="article-meta">{{ $article->published_at?->translatedFormat('d F Y') }} <span aria-hidden="true">·</span> {{ $article->author->name }}</p>
        </header>
        @if($article->cover_image)
            <figure class="article-cover">
                <img src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="Foto sampul: {{ $article->title }}">
            </figure>
        @endif
        @if($article->excerpt)<p class="article-excerpt">{{ $article->excerpt }}</p>@endif
        <div class="article-content">{!! $article->body !!}</div>
    </div>
</article>
@endsection
