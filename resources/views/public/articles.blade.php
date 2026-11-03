@extends('layouts.public')

@section('title', 'Informasi Sekolah')
@section('meta_title', 'Informasi Sekolah — '.($settings['school_name'] ?? 'SINAGA'))
@section('meta_description', 'Berita, artikel, dan dokumentasi informasi sekolah.')

@section('content')
    <section class="article-index-section section">
        <div class="container">
            <header class="article-index-heading">
                <span class="eyebrow">INFORMASI SEKOLAH</span>
                <h1>Artikel dan Kabar Sekolah</h1>
                <p>Ikuti berita, kegiatan, dan informasi terbaru dari {{ $settings['school_name'] ?? 'sekolah kami' }}.</p>
            </header>

            @if($articles->count())
                <div class="article-card-grid">
                    @foreach($articles as $article)
                        <article class="article-card">
                            <a class="article-card-link" href="{{ route('information.article', $article->slug) }}" aria-label="Baca {{ $article->title }}">
                                <div class="article-card-image">
                                    @if($article->cover_image)
                                        <img src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="Foto: {{ $article->title }}" loading="lazy">
                                    @else
                                        <div class="article-card-placeholder" aria-hidden="true"><span class="article-placeholder-mark">S</span><span>Informasi Sekolah</span></div>
                                    @endif
                                </div>
                                <div class="article-card-body">
                                    <div class="article-card-meta"><time datetime="{{ $article->published_at?->toAtomString() }}">{{ $article->published_at?->translatedFormat('d F Y') }}</time><span>{{ $article->category?->name ?? 'Berita sekolah' }}</span></div>
                                    <h2>{{ $article->title }}</h2>
                                    @if($article->excerpt)<p>{{ $article->excerpt }}</p>@endif
                                    <span class="article-card-read">Baca artikel <span aria-hidden="true">→</span></span>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
                <nav class="article-pagination" aria-label="Halaman artikel">{{ $articles->links() }}</nav>
            @else
                <div class="article-index-empty"><span aria-hidden="true">✳</span><h2>Belum ada informasi yang diterbitkan</h2><p>Artikel sekolah akan tampil di sini setelah diterbitkan.</p></div>
            @endif
        </div>
    </section>
@endsection
