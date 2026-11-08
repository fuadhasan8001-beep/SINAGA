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
            $settings = SiteSetting::query()->pluck('value', 'key')->map(function ($value) {
                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            });
            $navigation = $settings->get('footer_navigation');
            $social = $settings->get('footer_social');
            $navigation = is_array($navigation) ? $navigation : [];
            $social = is_array($social) ? $social : [];
            $navigation = array_values(array_filter($navigation, fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null)));
            $social = array_values(array_filter($social, fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null)));
            $address = collect([
                $settings->get('address'),
                $settings->get('address_city'),
                $settings->get('address_region'),
                $settings->get('address_postal_code'),
            ])->filter(fn ($part) => filled($part))->join(', ');
            $view->with('footerSettings', [
                'school_name' => $settings->get('school_name'),
                'school_tagline' => $settings->get('school_tagline'),
                'school_logo_path' => $settings->get('school_logo_path'),
                'description' => $settings->get('footer_description'),
                'address' => $address,
                'phone_alternative' => $settings->get('phone_alternative'),
                'email' => $settings->get('email'),
                'phone' => $settings->get('phone'),
                'navigation' => $navigation,
                'social' => $social,
                'copyright_year' => $settings->get('footer_copyright_year'),
                'copyright_name' => $settings->get('footer_copyright_name'),
                'copyright_note' => $settings->get('footer_copyright_note'),
            ]);
        });
    }
}
