<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-identity">
                <a class="footer-brand" href="{{ route('home') }}#beranda">
                    @if($footerSettings['school_logo_path'])
                        <img class="footer-logo" src="{{ Storage::disk('public')->url($footerSettings['school_logo_path']) }}" alt="Logo {{ $footerSettings['school_name'] }}">
                    @else
                        <span class="footer-logo-fallback" aria-hidden="true">{{ Str::upper(Str::substr($footerSettings['school_name'], 0, 1)) }}</span>
                    @endif
                    <span class="footer-brand-name">{{ $footerSettings['school_name'] }}<small>{{ $footerSettings['school_tagline'] }}</small></span>
                </a>
                @if(filled($footerSettings['description']))<p>{{ $footerSettings['description'] }}</p>@endif
            </div>
            <div class="footer-navigation">
                <h2>Jelajahi</h2>
                <nav class="footer-links" aria-label="Navigasi footer">
                    @foreach($footerSettings['navigation'] as $link)
                        @if(filled($link['label'] ?? null) && filled($link['url'] ?? null))<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endif
                    @endforeach
                </nav>
            </div>
            <div class="footer-contact">
                <h2>Hubungi Kami</h2>
                <div class="footer-contact-list">
                    @if(filled($footerSettings['address']))<p><span>Alamat</span>{{ $footerSettings['address'] }}</p>@endif
                    @if(filled($footerSettings['email']))<p><span>Email</span><a href="mailto:{{ $footerSettings['email'] }}">{{ $footerSettings['email'] }}</a></p>@endif
                    @if(filled($footerSettings['phone']))<p><span>Telepon</span><a href="tel:{{ preg_replace('/[^+0-9]/', '', $footerSettings['phone']) }}">{{ $footerSettings['phone'] }}</a></p>@endif
                </div>
                @if(count($footerSettings['social']))
                <div class="footer-social" aria-label="Media sosial">
                    @foreach($footerSettings['social'] as $social)
                        @if(filled($social['label'] ?? null) && filled($social['url'] ?? null))<a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">{{ $social['label'] }}</a>@endif
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        <div class="copyright"><span>© {{ $footerSettings['copyright_year'] }} {{ $footerSettings['copyright_name'] }}</span>@if(filled($footerSettings['copyright_note']))<span>{{ $footerSettings['copyright_note'] }}</span>@endif</div>
    </div>
</footer>
<button class="up" id="upBtn" aria-label="Kembali ke atas">Atas</button>
