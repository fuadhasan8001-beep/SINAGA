@php
    $footerWhatsappNumber = preg_replace('/\D+/', '', (string) ($footerSettings['phone'] ?? ''));
    if (str_starts_with($footerWhatsappNumber, '0')) {
        $footerWhatsappNumber = '62'.substr($footerWhatsappNumber, 1);
    } elseif (str_starts_with($footerWhatsappNumber, '8')) {
        $footerWhatsappNumber = '62'.$footerWhatsappNumber;
    }
@endphp
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-identity">
                <a class="footer-brand" href="{{ route('home') }}">
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
                <section class="footer-social-column" aria-labelledby="footer-social-title">
                    <h2 id="footer-social-title">Media Sosial</h2>
                    <nav class="footer-social-list" aria-label="Media sosial SMPN 1 Gondang">
                        <div class="footer-social-item"><div>Instagram</div><a href="https://www.instagram.com/smp1gondang/" target="_blank" rel="noopener noreferrer">@smp1gondang</a></div>
                        <div class="footer-social-item"><div>TikTok</div><a href="https://www.tiktok.com/@s1nagagondang" target="_blank" rel="noopener noreferrer">@s1nagagondang</a></div>
                        <div class="footer-social-item"><div>Facebook</div><a href="https://www.facebook.com/share/1HpHaBebbb/" target="_blank" rel="noopener noreferrer">Sinaga Gondang</a></div>
                        <div class="footer-social-item"><div>YouTube</div><a href="https://youtube.com/@smpn1gondang432" target="_blank" rel="noopener noreferrer">SMPN 1 GONDANG</a></div>
                    </nav>
                </section>
                <section class="footer-explore-column" aria-labelledby="footer-explore-title">
                    <h2 id="footer-explore-title">Jelajahi</h2>
                    <nav class="footer-links" aria-label="Navigasi footer">
                        @foreach($footerSettings['navigation'] as $link)
                            @if(filled($link['label'] ?? null) && filled($link['url'] ?? null))<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endif
                        @endforeach
                    </nav>
                </section>
            </div>
            <div class="footer-contact">
                <h2>Hubungi Kami</h2>
                <div class="footer-contact-list">
                    @if(filled($footerSettings['address']))<p><span>Alamat</span>{{ $footerSettings['address'] }}</p>@endif
                    @if(filled($footerSettings['email']))<p class="footer-contact-item footer-contact-item--email"><span class="footer-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg></span><span class="footer-contact-copy"><span>Email</span><a href="mailto:{{ $footerSettings['email'] }}">{{ $footerSettings['email'] }}</a></span></p>@endif
                    @if(filled($footerSettings['phone']))<p class="footer-contact-item footer-contact-item--whatsapp"><span class="footer-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M20 11.6a8 8 0 0 1-11.8 7L4 20l1.4-4A8 8 0 1 1 20 11.6Z"/><path d="M9 8.5c.3-.6.6-.6.9-.6h.4c.2 0 .4.1.5.4l.7 1.6c.1.2.1.4-.1.6l-.5.6c-.2.2-.2.4 0 .6.6 1 1.4 1.7 2.4 2.2.2.1.4.1.6-.1l.7-.8c.2-.2.4-.2.6-.1l1.5.7c.3.1.4.3.4.5 0 .5-.2 1-.6 1.3-.5.4-1.1.6-1.8.5-1.2-.2-2.5-.8-3.8-2-1.2-1.1-2-2.4-2.2-3.5-.1-.7.1-1.4.5-1.9Z"/></svg></span><span class="footer-contact-copy"><span>WhatsApp</span>@if(strlen($footerWhatsappNumber) >= 9 && strlen($footerWhatsappNumber) <= 15)<a class="footer-whatsapp-link" href="https://wa.me/{{ $footerWhatsappNumber }}" target="_blank" rel="noopener noreferrer"><span>{{ $footerSettings['phone'] }}</span><span class="footer-whatsapp-cta">Chat sekarang <span aria-hidden="true">&gt;&gt;</span></span></a>@else<a href="tel:{{ preg_replace('/[^+0-9]/', '', $footerSettings['phone']) }}">{{ $footerSettings['phone'] }}</a>@endif</span></p>@endif
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
        <div class="copyright"><div class="copyright-primary"><span>© {{ $footerSettings['copyright_year'] }} {{ $footerSettings['copyright_name'] }}</span><span class="footer-watermark" aria-label="by ESEMKITA 2026">by ESEMKITA 2026</span></div>@if(filled($footerSettings['copyright_note']))<span>{{ $footerSettings['copyright_note'] }}</span>@endif</div>
    </div>
</footer>
<button class="up" id="upBtn" aria-label="Kembali ke atas">Atas</button>
