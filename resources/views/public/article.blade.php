@extends('layouts.public')

@section('title',$article->title.' — '.($settings['school_name']??'Website Sekolah'))
@section('content')
<article class="article-page section">
    <div class="container article-shell">
        <a class="back article-back" href="{{ ($adminMode ?? false) ? $panelRoute('admin.dashboard') : route('information.articles') }}">Kembali ke Informasi</a>
        <header class="article-header">
            <span class="eyebrow">{{ $article->category?->name ?? 'Berita sekolah' }}</span>
            <h1>{{ $article->title }}</h1>
            <p class="article-meta">{{ $article->published_at?->translatedFormat('d F Y') }} <span aria-hidden="true">·</span> {{ $article->category?->name ?? 'Informasi Sekolah' }} <span aria-hidden="true">·</span> {{ $article->author_display_name }}</p>
        </header>
        @if($article->cover_image)
            <figure class="article-cover">
                <img src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="Foto sampul: {{ $article->title }}">
            </figure>
        @endif
        @if($article->excerpt)<p class="article-excerpt">{{ $article->excerpt }}</p>@endif
        <div class="article-content">{!! $article->body !!}</div>
        @if($relatedArticles->isNotEmpty())
            <section class="related-articles" aria-labelledby="relatedArticlesHeading">
                <h2 id="relatedArticlesHeading">Informasi lainnya</h2>
                <div class="related-articles-grid">
                    @foreach($relatedArticles as $related)
                        <a href="{{ route('information.article', $related->slug) }}"><span>{{ $related->published_at?->translatedFormat('d F Y') }}</span><strong>{{ $related->title }}</strong></a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</article>
@endsection
