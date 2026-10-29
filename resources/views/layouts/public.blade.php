<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F0FAF3">
    <meta name="description" content="Website informasi dan profil sekolah. Temukan informasi profil, program, kegiatan, berita, dan layanan sekolah.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $settings['school_name'] ?? 'Nama Sekolah' }} — Website Resmi">
    <meta property="og:description" content="Informasi profil, program, kegiatan, berita, dan layanan sekolah.">
    <title>@yield('title', ($settings['school_name'] ?? 'Nama Sekolah').' — Website Resmi')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/site.css','resources/js/site.js'])
    @if(request()->routeIs('spmb.public','ppdb')) @vite(['resources/css/spmb.css']) @endif
    @if($adminMode ?? false) @vite(['resources/css/site-admin.css','resources/js/admin.js']) @endif
</head>
<body class="{{ ($adminMode ?? false)?'admin-preview':'' }}" @if(!empty($scrollTarget)) data-scroll-target="{{ $scrollTarget }}" @endif>
    @if($adminMode ?? false) @include('public.partials.admin-tools') @endif
    @include('public.partials.nav')
    <main>@yield('content')</main>
    @include('public.partials.footer')
</body>
</html>
