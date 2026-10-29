@extends('layouts.admin')
@section('title','Pratinjau artikel')@section('heading','Pratinjau Artikel')@section('subtitle','Artikel ini belum terlihat oleh pengunjung website.')
@section('content')
<article class="panel article-preview">
    <div class="form-body">
        <span class="pill {{ $article->status }}">{{ $article->status==='pending'?'Menunggu persetujuan':$article->status }}</span>
        <h1>{{ $article->title }}</h1>
        <p class="muted">{{ $article->category?->name??'Tanpa kategori' }} · {{ $article->author->name }}</p>
        @if($article->cover_image)
            <img class="cover-preview" src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}">
        @endif
        @if($article->excerpt)
            <p><strong>{{ $article->excerpt }}</strong></p>
        @endif
        <div class="article-content">{!! $article->body !!}</div>
        <div class="actions"><a class="secondary" href="{{ route('admin.articles.reviews') }}">Kembali ke persetujuan</a><a class="primary" href="{{ route('admin.articles.edit',$article) }}">Edit artikel</a></div>
    </div>
</article>
@endsection
