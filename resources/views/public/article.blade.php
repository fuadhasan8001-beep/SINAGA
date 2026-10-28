@extends('layouts.public')

@section('title',$article->title.' — '.($settings['school_name']??'Website Sekolah'))
@section('content')
<article class="section"><div class="container" style="max-width:820px"><a class="back" href="{{ route('news') }}">← Kembali ke berita</a><span class="eyebrow">{{ $article->category?->name ?? 'Berita sekolah' }}</span><h1>{{ $article->title }}</h1><p class="muted">{{ $article->published_at?->translatedFormat('d F Y') }} · {{ $article->author->name }}</p>@if($article->cover_image)<img src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="Foto sampul: {{ $article->title }}" style="width:100%;max-height:460px;object-fit:cover">@endif @if($article->excerpt)<p><strong>{{ $article->excerpt }}</strong></p>@endif<div class="article-content">{!! $article->body !!}</div></div></article>
@endsection
