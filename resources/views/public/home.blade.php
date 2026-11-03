@extends('layouts.public')

@section('content')
@php
    $heroPhotoPath = filled($settings['hero_photo_path'] ?? null) ? $settings['hero_photo_path'] : null;
    $heroTitle = filled($settings['hero_title'] ?? null) ? $settings['hero_title'] : 'Selamat datang di '.($settings['school_name'] ?? 'SMPN 1 Gondang');
    $heroDescription = filled($settings['hero_description'] ?? null) ? $settings['hero_description'] : 'Ruang informasi sekolah untuk mengenal lingkungan, program, kegiatan, dan layanan SMPN 1 Gondang.';
    $heroLabel = filled($settings['hero_label'] ?? null) ? $settings['hero_label'] : 'Website resmi sekolah';
    $whatsappNumber = preg_replace('/\D+/', '', (string) ($settings['phone'] ?? ''));
    if (str_starts_with($whatsappNumber, '0')) {
        $whatsappNumber = '62'.substr($whatsappNumber, 1);
    } elseif (str_starts_with($whatsappNumber, '8')) {
        $whatsappNumber = '62'.$whatsappNumber;
    }
    $heroPosition = ['center'=>'center center','left'=>'left center','right'=>'right center','top'=>'center top','bottom'=>'center bottom'][$settings['hero_photo_position'] ?? 'center'] ?? 'center center';
    $galleryPhotos = $gallery->filter(fn ($photo) => filled($photo->image_path))->values();
    $profilePhotoEntry = $teachers->first();
    $profilePhotoPath = filled($settings['profile_photo_path'] ?? null) ? $settings['profile_photo_path'] : $profilePhotoEntry?->image_path;
    $profileUsesSchoolLogo = filled($profilePhotoPath) && $profilePhotoPath === ($settings['school_logo_path'] ?? null);
    $principalGreeting = trim((string) ($settings['principal_greeting'] ?? ''));
    $principalGreetingText = preg_replace('/\s+/u', ' ', $principalGreeting) ?? $principalGreeting;
    $principalGreetingExcerpt = $principalGreetingText;
    if (mb_strlen($principalGreetingText) > 200) {
        $excerptCandidate = mb_substr($principalGreetingText, 0, 200);
        $lastSpace = mb_strrpos($excerptCandidate, ' ');
        $principalGreetingExcerpt = rtrim(mb_substr($excerptCandidate, 0, $lastSpace ?: 200)).'…';
    }
    $principalPhotoPath = filled($settings['principal_photo_path'] ?? null) ? $settings['principal_photo_path'] : null;
    $profilePhotoAlt = $profilePhotoEntry && $profilePhotoEntry->image_path === $profilePhotoPath
        ? ($profilePhotoEntry->metadata['alt'] ?? $profilePhotoEntry->title)
        : 'Foto profil sekolah';
@endphp
<section class="hero home-hero" id="beranda" style="--hero-photo-position: {{ $heroPosition }}">
    @if($heroPhotoPath)<img class="home-hero-image" src="{{ Storage::disk('public')->url($heroPhotoPath) }}" alt="" aria-hidden="true" width="1920" height="1080" fetchpriority="high" decoding="async">@endif
    <div class="container home-hero-content">
        <div class="home-hero-copy">
            <span class="eyebrow">{{ $heroLabel }}</span>
            <h1>{{ $heroTitle }}</h1>
            <p>{{ $heroDescription }}</p>
            <div class="hero-actions">
                @if($adminMode ?? false)<a class="btn" href="{{ route('admin.content.index','announcement') }}">Kelola Pengumuman</a>@else<a class="btn" href="#profil">Profil Sekolah</a>@endif
                <a class="btn btn-outline" href="#galeri">Jelajahi Galeri</a>
            </div>
        </div>
    </div>
    @if($adminMode ?? false)
        <details class="home-hero-admin-menu">
            <summary title="Kelola Hero"><span aria-hidden="true">⚙</span><span>Kelola Hero</span></summary>
            <div class="home-hero-admin-menu-items">
                <a href="{{ route('admin.settings.edit',['section'=>'hero']) }}"><span aria-hidden="true">✎</span>Edit teks Hero</a>
                <a href="{{ route('admin.photos.edit','hero') }}"><span aria-hidden="true">▧</span>Ganti foto Hero</a>
            </div>
        </details>
    @endif
</section>

<section class="section" id="profil"><div class="container welcome-grid"><div class="welcome-image profile-image-stack">@if($profilePhotoPath)<img class="{{ $profileUsesSchoolLogo ? 'profile-logo-image' : '' }}" src="{{ Storage::disk('public')->url($profilePhotoPath) }}" alt="{{ $profilePhotoAlt }}" style="max-width:100%;max-height:250px;object-fit:cover">@else<div class="photo-placeholder"><strong>Tempat foto sekolah atau kegiatan resmi</strong><span>Dokumentasi profil sekolah dapat ditambahkan oleh pengelola.</span></div>@endif @if($principalPhotoPath)<figure class="principal-photo"><img src="{{ Storage::disk('public')->url($principalPhotoPath) }}" alt="Foto kepala sekolah"><figcaption>{{ $settings['principal'] ?? 'Kepala Sekolah' }}</figcaption></figure>@endif @if($adminMode ?? false)<a class="photo-admin-action" href="{{ route('admin.settings.edit',['section'=>'profile']) }}">Kelola Profil</a>@endif</div><div class="welcome-copy"><span class="eyebrow">Profil sekolah</span><h2>Mengenal sekolah lebih dekat</h2><p>{{ $settings['history'] ?? 'Tambahkan ringkasan sejarah dan identitas sekolah setelah informasi resmi tersedia.' }}</p>@if(filled($settings['vision'] ?? null))<h3>Visi Sekolah</h3><p>{{ $settings['vision'] }}</p>@endif @if(!empty($settings['mission']))<h3>Misi Sekolah</h3><ol class="mission-list">@foreach(is_array($settings['mission']) ? $settings['mission'] : preg_split('/\\r?\\n/', $settings['mission']) as $mission)@if(filled($mission))<li>{{ $mission }}</li>@endif @endforeach</ol>@endif<div class="person"><b>Kepala Sekolah</b><small>{{ $settings['principal'] ?? 'Nama kepala sekolah — perbarui dengan data resmi' }}</small></div>@if($principalGreeting !== '')<article class="greeting-card"><span class="eyebrow">Sambutan Kepala Sekolah</span><h3>{{ $settings['principal'] ?? 'Kepala Sekolah' }}</h3><p class="greeting-excerpt">{{ $principalGreetingExcerpt }}</p><a class="greeting-more" href="{{ route('principal-greeting') }}">Lihat Selengkapnya</a></article>@else<article class="greeting-card greeting-card--empty"><span class="eyebrow">Sambutan Kepala Sekolah</span><p>Sambutan kepala sekolah sedang disiapkan.</p></article>@endif</div></div></section>

@php
    $academicGroups = [
        'Kegiatan Pembelajaran' => $learningActivities,
        'Kalender Pendidikan' => $educationCalendar,
        'Program Ekstrakurikuler' => $academicPrograms,
        'Informasi Asesmen Akademik' => $academicAssessments,
    ];
    $academicCarouselEntries = collect();
    foreach ($academicGroups as $categoryName => $entries) {
        foreach ($entries as $entry) {
            $entry->academic_category = $categoryName;
            $academicCarouselEntries->push($entry);
        }
    }
    $academicSettings = [
        'Kurikulum' => $settings['curriculum'] ?? null,
        'Penerapan kurikulum' => $settings['curriculum_summary'] ?? null,
        'Program pembelajaran' => $settings['learning_program'] ?? null,
        'Sistem pembelajaran' => $settings['learning_system'] ?? null,
        'Program unggulan' => $settings['academic_excellence'] ?? null,
        'Informasi pendukung' => $settings['academic_support'] ?? null,
        'Kalender akademik' => $settings['academic_calendar'] ?? null,
    ];
    $hasAcademicSettings = collect($academicSettings)->contains(fn ($description) => filled($description));
@endphp
<section class="section soft" id="akademik">
    <div class="container">
        <div class="section-head activity-section-head">
            <div><span class="eyebrow">Akademik</span><h2>Kegiatan Pembelajaran</h2><p>Berbagai kegiatan pembelajaran untuk mendukung potensi dan prestasi siswa.</p></div>
            @if($academicCarouselEntries->isNotEmpty())
                <div class="activity-carousel-controls" data-academic-carousel-controls>
                    <button type="button" data-academic-prev aria-label="Kategori akademik sebelumnya" aria-controls="academicCarousel" @disabled($academicCarouselEntries->count() < 2)>←</button>
                    <span data-academic-status aria-live="polite">1 / {{ $academicCarouselEntries->count() }}</span>
                    <button type="button" data-academic-next aria-label="Kategori akademik berikutnya" aria-controls="academicCarousel" @disabled($academicCarouselEntries->count() < 2)>→</button>
                </div>
            @endif
        </div>
        @if($academicCarouselEntries->isNotEmpty())
            <div class="activity-carousel" data-activity-carousel>
                <div class="activity-carousel-viewport" id="academicCarousel" role="region" aria-label="Kategori akademik" tabindex="0">
                    <div class="activity-carousel-track" data-academic-track>
                        @foreach($academicCarouselEntries as $entry)
                            @php
                                $cardTitle = $entry->title;
                            @endphp
                            <div class="activity-slide">
                                <button class="learning-activity-card" type="button" data-academic-detail="academic-detail-{{ $entry->id }}" aria-haspopup="dialog" aria-controls="academicDetailModal" aria-label="Buka informasi {{ $cardTitle }}">
                                    <span class="learning-activity-panel"><span class="learning-activity-title">{{ $cardTitle }}</span></span>
                                    <span class="learning-activity-photo">@if($entry->image_path)<img src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto {{ $cardTitle }}" loading="lazy" decoding="async">@else<span class="activity-photo-placeholder" role="img" aria-label="Gambar {{ $cardTitle }} belum tersedia"><span aria-hidden="true">▧</span></span>@endif</span>
                                </button>
                            </div>
                            <template id="academic-detail-{{ $entry->id }}">
                                <article class="academic-detail-view">
                                    <span class="eyebrow">{{ $entry->academic_category }}</span>
                                    <h2 id="academicDetailTitle">{{ $cardTitle }}</h2>
                                    @if($entry->image_path)<img class="academic-detail-image" src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto {{ $cardTitle }}">@endif
                                    @if(filled($entry->excerpt))<p class="academic-detail-excerpt">{{ $entry->excerpt }}</p>@endif
                                    @if(filled(strip_tags($entry->body ?? '')) && trim(strip_tags($entry->body)) !== trim($entry->excerpt ?? ''))<div class="academic-detail-body">{!! $entry->body !!}</div>@endif
                                    @if($entry->starts_at || filled($entry->location))<div class="activity-detail-meta">@if($entry->starts_at)<span>{{ $entry->starts_at->translatedFormat('d F Y') }}</span>@endif @if(filled($entry->location))<span>{{ $entry->location }}</span>@endif</div>@endif
                                    @unless(filled($entry->excerpt) || filled(strip_tags($entry->body ?? '')))<p class="activity-detail-empty">Belum ada ringkasan kegiatan yang ditambahkan.</p>@endunless
                                </article>
                            </template>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="academic-empty"><strong>Belum ada kategori Akademik yang ditayangkan.</strong><span>Konten akan muncul setelah dipublikasikan oleh sekolah.</span></div>
        @endif
        @if($hasAcademicSettings)<div class="program-list academic-settings-list">@foreach($academicSettings as $title => $description)@if(filled($description))<div class="program-row"><h3>{{ $title }}</h3><p>{{ $description }}</p></div>@endif @endforeach</div>@endif
    </div>
</section>
<dialog class="academic-detail-dialog" id="academicDetailModal" aria-labelledby="academicDetailTitle">
    <button class="academic-detail-close" type="button" data-academic-close aria-label="Tutup detail">×</button>
    <div class="academic-detail-dialog-content" data-academic-detail-content></div>
</dialog>
@php
    $featuredAnnouncement = $events->isEmpty() ? $announcements->first() : null;
    $listedAnnouncements = $featuredAnnouncement ? $announcements->skip(1) : $announcements;
    $hasNoticeListItems = $listedAnnouncements->isNotEmpty() || $documents->isNotEmpty();
@endphp
<section class="section warm" id="pengumuman"><div class="container"><div class="section-head"><span class="eyebrow">Pengumuman</span><h2>Pengumuman dan agenda</h2><p>Informasi kegiatan untuk siswa, orang tua, dan warga sekolah.</p></div><div class="notice-grid{{ $hasNoticeListItems ? '' : ' notice-grid--single' }}">
    <article class="notice-feature">
        @if($events->first())<span class="pill">Agenda sekolah</span><h3>{{ $events->first()->title }}</h3><p>{{ $events->first()->excerpt }}</p><p class="detail">{{ $events->first()->starts_at?->translatedFormat('l, d F Y H:i') }} @if($events->first()->location) · {{ $events->first()->location }} @endif</p>
        @elseif($featuredAnnouncement)<span class="pill">Pengumuman terbaru</span><h3>{{ $featuredAnnouncement->title }}</h3><p>{{ $featuredAnnouncement->excerpt }}</p><p class="detail">{{ $featuredAnnouncement->starts_at?->translatedFormat('d F Y') ?? $featuredAnnouncement->created_at?->translatedFormat('d F Y') }}</p>
        @else<span class="notice-empty-icon" aria-hidden="true">▤</span><span class="pill">Informasi sekolah</span><h3>Belum ada pengumuman terbaru</h3><p>Pengumuman dan agenda akan ditampilkan setelah diterbitkan oleh pengelola sekolah.</p>@endif
    </article>
    @if($hasNoticeListItems)<div class="notice-list">
        @foreach($listedAnnouncements as $item)<article class="notice-item"><span class="datebox">{{ $item->starts_at?->translatedFormat('d M Y') ?? $item->created_at?->translatedFormat('d M Y') ?? 'Pengumuman' }}</span><h3>{{ $item->title }}</h3><p>{{ $item->excerpt }}</p></article>@endforeach
        @foreach($documents as $document)<article class="notice-item"><span class="datebox">Dokumen</span><h3>{{ $document->title }}</h3><p>{{ $document->excerpt }}</p>@if($document->document_path)<a href="{{ Storage::disk('public')->url($document->document_path) }}">Unduh dokumen</a>@endif</article>@endforeach
    </div>@endif
</div></div></section>

<section class="section" id="ekstrakurikuler">
    <div class="container">
        <div class="section-head extracurricular-section-head">
            <div><span class="eyebrow">Kegiatan siswa</span><h2>Ekstrakurikuler</h2><p>Beragam kegiatan untuk mengembangkan bakat, minat, kreativitas, dan karakter siswa.</p></div>
            @if($extracurriculars->isNotEmpty())
                <div class="activity-carousel-controls extracurricular-carousel-controls" data-extracurricular-controls>
                    <button type="button" data-extracurricular-prev aria-label="Ekstrakurikuler sebelumnya" aria-controls="extracurricularCarousel" @disabled($extracurriculars->count() < 2)>←</button>
                    <span data-extracurricular-status aria-live="polite">1 / {{ $extracurriculars->count() }}</span>
                    <button type="button" data-extracurricular-next aria-label="Ekstrakurikuler berikutnya" aria-controls="extracurricularCarousel" @disabled($extracurriculars->count() < 2)>→</button>
                </div>
            @endif
        </div>
        @if($extracurriculars->isNotEmpty())
            <div class="extracurricular-carousel" data-extracurricular-carousel>
                <div class="extracurricular-carousel-viewport" id="extracurricularCarousel" role="region" aria-label="Galeri ekstrakurikuler" tabindex="0">
                    <div class="extracurricular-track">
                        @foreach($extracurriculars as $entry)
                            <a class="club-card extracurricular-card" href="{{ ($adminMode ?? false) ? route('admin.content.edit', ['extracurricular', $entry]) : route('extracurriculars.show', $entry->slug) }}" aria-label="{{ ($adminMode ?? false) ? 'Kelola' : 'Buka detail' }} ekstrakurikuler {{ $entry->title }}">
                                @if($entry->image_path)<img src="{{ Storage::disk('public')->url($entry->image_path) }}" alt="Foto ekstrakurikuler {{ $entry->title }}" loading="lazy" decoding="async">@else<div class="club-photo-placeholder" role="img" aria-label="Foto {{ $entry->title }} belum tersedia"><span aria-hidden="true">▧</span></div>@endif
                                <h3>{{ $entry->title }}</h3>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="extracurricular-empty"><strong>Belum ada ekstrakurikuler yang ditayangkan.</strong><span>Informasi akan muncul setelah dipublikasikan oleh sekolah.</span></div>
        @endif
    </div>
</section>

<section class="section" id="galeri"><div class="container"><div class="gallery-section-head"><div class="section-head"><span class="eyebrow">Galeri Sekolah</span><h2>Dokumentasi Kegiatan Sekolah</h2><p>Foto dokumentasi yang telah diizinkan untuk dipublikasikan.</p></div></div>
@if($galleryPhotos->isNotEmpty())
    <div class="gallery-carousel" data-gallery-carousel>
        <button class="gallery-carousel-arrow gallery-carousel-prev" type="button" data-gallery-carousel-prev aria-label="Geser galeri ke kiri">←</button>
        <div class="gallery-carousel-viewport" id="publicGalleryViewport" role="region" aria-label="Dokumentasi kegiatan sekolah" tabindex="0">
        <div class="gallery-track" id="publicGalleryGrid">
        @foreach($galleryPhotos as $photo)
            <article class="gallery-item" data-gallery-item>
                <button class="gallery-photo-button" type="button" data-gallery-open data-photo-src="{{ Storage::disk('public')->url($photo->image_path) }}" data-photo-title="{{ $photo->title }}" aria-label="Perbesar foto: {{ $photo->title }}">
                    <img src="{{ Storage::disk('public')->url($photo->image_path) }}" alt="{{ $photo->title }}" loading="lazy" decoding="async">
                </button>
                <h3>{{ $photo->title }}</h3>
            </article>
        @endforeach
        </div>
        </div>
        <button class="gallery-carousel-arrow gallery-carousel-next" type="button" data-gallery-carousel-next aria-label="Geser galeri ke kanan">→</button>
    </div>
    <dialog class="gallery-lightbox" id="galleryLightbox" aria-label="Galeri foto">
        <button class="gallery-lightbox-close" type="button" data-gallery-close aria-label="Tutup galeri">×</button>
        <button class="gallery-lightbox-nav gallery-lightbox-prev" type="button" data-gallery-prev aria-label="Foto sebelumnya">Sebelumnya</button>
        <figure><img id="galleryLightboxImage" alt=""><figcaption id="galleryLightboxTitle"></figcaption></figure>
        <button class="gallery-lightbox-nav gallery-lightbox-next" type="button" data-gallery-next aria-label="Foto berikutnya">Berikutnya</button>
    </dialog>
@else
    <div class="gallery-placeholder"><div class="gallery-placeholder-icon" aria-hidden="true">▧</div><strong>Galeri belum memiliki dokumentasi</strong><p>Foto kegiatan akan tampil di sini setelah diunggah oleh administrator.</p></div>
@endif
</div></section>

<section class="section" id="kontak"><div class="container contact-grid"><div class="contact-panel"><span class="eyebrow">Kontak</span><h2>Informasi sekolah</h2><p>Hubungi sekolah melalui informasi resmi berikut.</p><div class="contact-row"><b>Alamat</b><span>{{ $settings['address'] ?? 'Alamat sekolah — perbarui' }}</span></div><div class="contact-row"><b>WhatsApp</b>@if(strlen($whatsappNumber) >= 9 && strlen($whatsappNumber) <= 15)<a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noopener noreferrer" aria-label="Chat WhatsApp ke {{ $settings['phone'] }}">{{ $settings['phone'] }}</a>@else<span>{{ $settings['phone'] ?? 'Nomor WhatsApp resmi — perbarui' }}</span>@endif</div><div class="contact-row"><b>Email</b><span>{{ $settings['email'] ?? 'Email resmi — perbarui' }}</span></div><div class="contact-row"><b>Jam pelayanan</b><span>{{ $settings['office_hours'] ?? 'Jadwal layanan — perbarui' }}</span></div></div><div class="form-panel"><h3>Kirim pesan atau saran</h3><p>Pesan Anda diteruskan kepada pengelola website sekolah.</p>@if(session('success'))<p class="form-note" role="status">{{ session('success') }}</p>@endif @if($errors->any())<p class="form-note form-note--error" role="alert">{{ $errors->first() }}</p>@endif<form id="contactForm" method="post" action="{{ route('contact.store') }}">@csrf<div class="form-grid"><div class="field"><label for="name">Nama</label><input id="name" name="name" autocomplete="name" required value="{{ old('name') }}" placeholder="Nama Anda">@error('name')<small>{{ $message }}</small>@enderror</div><div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" placeholder="nama@email.com">@error('email')<small>{{ $message }}</small>@enderror</div><div class="field full"><label for="subject">Subjek</label><input id="subject" name="subject" required value="{{ old('subject') }}" placeholder="Topik pesan">@error('subject')<small>{{ $message }}</small>@enderror</div><div class="field full"><label for="message">Pesan</label><textarea id="message" name="message" required placeholder="Tulis pesan atau saran Anda">{{ old('message') }}</textarea>@error('message')<small>{{ $message }}</small>@enderror</div><div class="field full"><button class="btn" type="submit">Kirim pesan</button></div></div></form></div></div></section>
@endsection
