@php
    $footerWhatsappNumber = preg_replace('/\D+/', '', (string) ($footerSettings['phone'] ?? ''));
    if (str_starts_with($footerWhatsappNumber, '0')) {
        $footerWhatsappNumber = '62'.substr($footerWhatsappNumber, 1);
    } elseif (str_starts_with($footerWhatsappNumber, '8')) {
        $footerWhatsappNumber = '62'.$footerWhatsappNumber;
    }
    $footerWhatsappMessage = rawurlencode('Halo Admin SINAGA, saya ingin bertanya mengenai informasi SINAGA.');
    $hasFooterBrand = filled($footerSettings['school_name']) || filled($footerSettings['school_tagline']) || filled($footerSettings['school_logo_path']);
    $hasFooterIdentity = $hasFooterBrand || filled($footerSettings['description']);
    $hasFooterSocial = count($footerSettings['social']) > 0;
    $hasFooterNavigation = count($footerSettings['navigation']) > 0;
    $hasFooterNavigationColumn = $hasFooterSocial || $hasFooterNavigation;
    $hasFooterContact = filled($footerSettings['address']) || filled($footerSettings['email']) || filled($footerSettings['phone']) || filled($footerSettings['phone_alternative']);
    $footerGridColumns = (int) $hasFooterIdentity + (int) $hasFooterNavigationColumn + (int) $hasFooterContact;
    $footerCopyright = collect([$footerSettings['copyright_year'], $footerSettings['copyright_name']])
        ->filter(fn ($value) => filled($value))
        ->join(' ');
@endphp
<footer class="footer">
    <div class="container">
        <div class="footer-grid footer-grid--{{ max(1, $footerGridColumns) }}">
            @if($hasFooterIdentity)
            <div class="footer-identity">
                @if($hasFooterBrand)
                <a class="footer-brand" href="{{ route('home') }}">
                    @if(filled($footerSettings['school_logo_path']))
                        <img class="footer-logo" src="{{ Storage::disk('public')->url($footerSettings['school_logo_path']) }}" alt="{{ filled($footerSettings['school_name']) ? 'Logo '.$footerSettings['school_name'] : 'Logo sekolah' }}">
                    @elseif(filled($footerSettings['school_name']))
                        <span class="footer-logo-fallback" aria-hidden="true">{{ Str::upper(Str::substr($footerSettings['school_name'], 0, 1)) }}</span>
                    @endif
                    @if(filled($footerSettings['school_name']) || filled($footerSettings['school_tagline']))
                        <span class="footer-brand-name">@if(filled($footerSettings['school_name'])){{ $footerSettings['school_name'] }}@endif @if(filled($footerSettings['school_tagline']))<small>{{ $footerSettings['school_tagline'] }}</small>@endif</span>
                    @endif
                </a>
                @endif
                @if(filled($footerSettings['description']))<p>{{ $footerSettings['description'] }}</p>@endif
            </div>
            @endif
            @if($hasFooterNavigationColumn)
            <div class="footer-navigation">
                @if($hasFooterNavigation)
                <section class="footer-explore-column" aria-labelledby="footer-explore-title">
                    <h2 id="footer-explore-title">Jelajahi</h2>
                    <nav class="footer-links" aria-label="Navigasi footer">
                        @foreach($footerSettings['navigation'] as $link)
                            <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </nav>
                </section>
                @endif
                @if($hasFooterSocial)<section class="footer-social-column" aria-labelledby="footer-social-title">
                    <h2 id="footer-social-title">Media Sosial</h2>
                    <nav class="footer-social-list" aria-label="Media sosial {{ filled($footerSettings['school_name']) ? $footerSettings['school_name'] : 'sekolah' }}">
                        @foreach($footerSettings['social'] as $social)
                            @php($socialLabel = $social['label'])
                            @php($socialUrl = $social['url'])
                                @php($socialPlatform = \Illuminate\Support\Str::lower($socialLabel))
                                @php($socialPath = trim((string) parse_url($socialUrl, PHP_URL_PATH), '/'))
                                @php($socialPathPart = \Illuminate\Support\Str::before($socialPath, '/'))
                                @php($socialHandle = $socialPathPart !== '' && !in_array(\Illuminate\Support\Str::lower($socialPathPart), ['share', 'pages', 'watch', 'channel', 'c', 'p', 'reel', 'profile.php'], true) ? (\Illuminate\Support\Str::startsWith($socialPathPart, '@') ? $socialPathPart : '@'.$socialPathPart) : null)
                                <a class="footer-social-card footer-social-card--{{ \Illuminate\Support\Str::slug($socialLabel) }}" href="{{ $socialUrl }}" target="_blank" rel="noopener noreferrer">
                                    <span class="footer-social-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false">
                                        @if($socialPlatform === 'instagram')<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.8" r="1"/>
                                        @elseif($socialPlatform === 'facebook')<path d="M13.7 21v-8h2.7l.4-3.1h-3.1V7.8c0-.9.3-1.5 1.6-1.5H17V3.5c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2v2.3H7.8V13h2.7v8h3.2Z"/>
                                        @elseif(in_array($socialPlatform, ['youtube', 'you tube'], true))<path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2A29.3 29.3 0 0 0 2 12a29.3 29.3 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2A29.3 29.3 0 0 0 22 12a29.3 29.3 0 0 0-.4-4.8ZM10.1 15.1V8.9l5.3 3.1-5.3 3.1Z"/>
                                        @elseif($socialPlatform === 'tiktok')<path d="M14.5 3c.3 2.3 1.6 3.8 3.9 4.1v3.1c-1.4 0-2.7-.4-3.9-1.1v6.4a5.5 5.5 0 1 1-4.8-5.5v3.2a2.4 2.4 0 1 0 1.7 2.3V3h3.1Z"/>
                                        @else<path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6m-1-5-8 8m2-5h-4a5 5 0 0 0 0 10h2m3-2h4a5 5 0 0 0 0-10h-2"/>
                                        @endif
                                    </svg></span>
                                    <span class="footer-social-copy"><strong>{{ $socialLabel }}</strong>@if(filled($socialHandle))<span>{{ $socialHandle }}</span>@endif</span>
                                </a>
                        @endforeach
                    </nav>
                </section>@endif
            </div>
            @endif
            @if($hasFooterContact)
            <div class="footer-contact">
                <h2>Hubungi Kami</h2>
                <div class="footer-contact-list">
                    @if(filled($footerSettings['address']))
                        @if(filled($footerSettings['maps_url']))
                            <a class="footer-contact-item footer-contact-item--card footer-contact-item--location" href="{{ $footerSettings['maps_url'] }}" target="_blank" rel="noopener noreferrer"><span class="footer-contact-icon" aria-hidden="true">⌖</span><span class="footer-contact-copy"><span>Lokasi Sekolah</span><span class="footer-contact-value">{{ $footerSettings['address'] }}</span></span></a>
                        @else
                            <p class="footer-contact-item footer-contact-item--card footer-contact-item--location"><span class="footer-contact-icon" aria-hidden="true">⌖</span><span class="footer-contact-copy"><span>Lokasi Sekolah</span><span class="footer-contact-value">{{ $footerSettings['address'] }}</span></span></p>
                        @endif
                    @endif
                    @if(filled($footerSettings['email']))<p class="footer-contact-item footer-contact-item--card footer-contact-item--email"><span class="footer-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg></span><span class="footer-contact-copy"><span>Email</span><a href="mailto:{{ $footerSettings['email'] }}">{{ $footerSettings['email'] }}</a></span></p>@endif
                    @if(filled($footerSettings['phone']))<p class="footer-contact-item footer-contact-item--whatsapp"><span class="footer-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M20 11.6a8 8 0 0 1-11.8 7L4 20l1.4-4A8 8 0 1 1 20 11.6Z"/><path d="M9 8.5c.3-.6.6-.6.9-.6h.4c.2 0 .4.1.5.4l.7 1.6c.1.2.1.4-.1.6l-.5.6c-.2.2-.2.4 0 .6.6 1 1.4 1.7 2.4 2.2.2.1.4.1.6-.1l.7-.8c.2-.2.4-.2.6-.1l1.5.7c.3.1.4.3.4.5 0 .5-.2 1-.6 1.3-.5.4-1.1.6-1.8.5-1.2-.2-2.5-.8-3.8-2-1.2-1.1-2-2.4-2.2-3.5-.1-.7.1-1.4.5-1.9Z"/></svg></span><span class="footer-contact-copy"><span>WhatsApp</span>@if(strlen($footerWhatsappNumber) >= 9 && strlen($footerWhatsappNumber) <= 15)<a class="footer-whatsapp-link" href="https://wa.me/{{ $footerWhatsappNumber }}?text={{ $footerWhatsappMessage }}" target="_blank" rel="noopener noreferrer"><span>{{ $footerSettings['phone'] }}</span><span class="footer-whatsapp-cta">Chat sekarang <span aria-hidden="true">&gt;&gt;</span></span></a>@else<a href="tel:{{ preg_replace('/[^+0-9]/', '', $footerSettings['phone']) }}">{{ $footerSettings['phone'] }}</a>@endif</span></p>@endif
                    @if(filled($footerSettings['phone_alternative']))<p class="footer-contact-item footer-contact-item--card footer-contact-item--phone"><span class="footer-contact-icon" aria-hidden="true">☎</span><span class="footer-contact-copy"><span>Telepon</span><a href="tel:{{ preg_replace('/[^+0-9]/', '', $footerSettings['phone_alternative']) }}">{{ $footerSettings['phone_alternative'] }}</a></span></p>@endif
                </div>
            </div>
            @endif
        </div>
        <div class="copyright"><div class="copyright-primary">@if(filled($footerCopyright))<span>© {{ $footerCopyright }}</span>@endif<span class="footer-watermark" aria-label="by ESEMKITA 2026">by ESEMKITA 2026</span></div>@if(filled($footerSettings['copyright_note']))<span>{{ $footerSettings['copyright_note'] }}</span>@endif</div>
    </div>
</footer>
<button class="up" id="upBtn" aria-label="Kembali ke atas">Atas</button>
