<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Support\PanelRoute;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('panelRoute', fn (string $name, mixed $parameters = [], bool $absolute = true): string => route(PanelRoute::name($name), $parameters, $absolute));

        View::composer('public.partials.footer', function ($view): void {
            $settings = SiteSetting::query()->pluck('value', 'key')->map(fn ($value) => json_decode($value, true) ?? $value);
            $navigation = $settings->get('footer_navigation');
            $social = $settings->get('footer_social');
            $view->with('footerSettings', [
                'school_name' => $settings->get('school_name', 'SMPN 1 Gondang'),
                'school_tagline' => $settings->get('school_tagline', 'Sekolah yang unggul dan berkarakter'),
                'school_logo_path' => $settings->get('school_logo_path'),
                'description' => $settings->get('footer_description', 'Sekolah yang mendampingi peserta didik untuk belajar, tumbuh, dan berprestasi.'),
                'address' => $settings->get('address'),
                'email' => $settings->get('email'),
                'phone' => $settings->get('phone'),
                'navigation' => is_array($navigation) ? $navigation : [
                    ['label' => 'Beranda', 'url' => '/#beranda'], ['label' => 'Profil Sekolah', 'url' => '/#profil'],
                    ['label' => 'Akademik', 'url' => '/#akademik'], ['label' => 'Pengumuman', 'url' => '/#pengumuman'],
                    ['label' => 'Ekstrakurikuler', 'url' => '/#ekstrakurikuler'], ['label' => 'Galeri', 'url' => '/#galeri'],
                ],
                'social' => is_array($social) ? $social : [],
                'copyright_year' => $settings->get('footer_copyright_year', now()->year),
                'copyright_name' => $settings->get('footer_copyright_name', $settings->get('school_name', 'SMPN 1 Gondang')),
                'copyright_note' => $settings->get('footer_copyright_note', 'Informasi ditampilkan sesuai data yang disediakan sekolah.'),
            ]);
        });
    }
}
