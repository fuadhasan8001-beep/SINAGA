<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Support\FooterDefaults;
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
            if (! (bool) $settings->get('footer_social_configured', false) && $social === []) {
                $social = FooterDefaults::social();
            }
            $cleanText = static function (mixed $value): ?string {
                if (! is_string($value)) {
                    return filled($value) ? (string) $value : null;
                }

                $value = trim($value);

                return $value === '' || in_array(strtolower($value), ['null', 'undefined'], true) ? null : $value;
            };
            $navigation = array_values(array_filter(array_map(function ($link) use ($cleanText): ?array {
                if (! is_array($link)) return null;
                $label = $cleanText($link['label'] ?? null);
                $url = $cleanText($link['url'] ?? null);
                $validUrl = $url !== null && (str_starts_with($url, '/') && ! str_starts_with($url, '//')
                    || (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)));

                return $label && $validUrl ? [...$link, 'label' => $label, 'url' => $url] : null;
            }, $navigation)));
            $social = array_values(array_filter(array_map(function ($link) use ($cleanText): ?array {
                if (! is_array($link)) return null;
                $label = $cleanText($link['label'] ?? null);
                $url = $cleanText($link['url'] ?? null);
                $validUrl = $url !== null && filter_var($url, FILTER_VALIDATE_URL)
                    && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
                    && filled(parse_url($url, PHP_URL_HOST));

                return $label && $validUrl ? [...$link, 'label' => $label, 'url' => $url] : null;
            }, $social)));
            $address = collect([
                $settings->get('address'),
                $settings->get('address_city'),
                $settings->get('address_region'),
                $settings->get('address_postal_code'),
            ])->map($cleanText)->filter()->join(', ');
            $mapsUrl = $cleanText($settings->get('address_map_url'));
            if (! $mapsUrl || ! filter_var($mapsUrl, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($mapsUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $mapsUrl = null;
            }
            $view->with('footerSettings', [
                'school_name' => $cleanText($settings->get('school_name')),
                'school_tagline' => $cleanText($settings->get('school_tagline')),
                'school_logo_path' => $cleanText($settings->get('school_logo_path')),
                'description' => $cleanText($settings->get('footer_description')),
                'address' => $address,
                'maps_url' => $mapsUrl,
                'phone_alternative' => $cleanText($settings->get('phone_alternative')),
                'email' => $cleanText($settings->get('email')),
                'phone' => $cleanText($settings->get('phone')),
                'navigation' => $navigation,
                'social' => $social,
                'copyright_year' => $cleanText($settings->get('footer_copyright_year')),
                'copyright_name' => $cleanText($settings->get('footer_copyright_name')),
                'copyright_note' => $cleanText($settings->get('footer_copyright_note')),
            ]);
        });
    }
}
