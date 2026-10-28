<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#166534">
    <meta name="description" content="Website informasi dan profil sekolah. Temukan informasi profil, program, kegiatan, berita, dan layanan sekolah.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $settings['school_name'] ?? 'Nama Sekolah' }} — Website Resmi">
    <meta property="og:description" content="Informasi profil, program, kegiatan, berita, dan layanan sekolah.">
    <title>@yield('title', ($settings['school_name'] ?? 'Nama Sekolah').' — Website Resmi')</title>
    @vite(['resources/css/site.css','resources/js/site.js'])
</head>
<body @if(!empty($scrollTarget)) data-scroll-target="{{ $scrollTarget }}" @endif>
    @include('public.partials.nav')
    <main>@yield('content')</main>
    @include('public.partials.footer')
</body>
</html>
