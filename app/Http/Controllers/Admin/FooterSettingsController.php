<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Support\FooterDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FooterSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = SiteSetting::query()->pluck('value', 'key')->map(function ($value) {
            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        });
        $settings['footer_navigation'] = $this->decodeList($settings->get('footer_navigation'), FooterDefaults::navigation());
        $settings['footer_social_configured'] = (bool) $settings->get('footer_social_configured', false);
        $social = $this->decodeList($settings->get('footer_social'), []);
        $settings['footer_social'] = !$settings['footer_social_configured'] && $social === [] ? FooterDefaults::social() : $social;
        $settings['footer_copyright_year'] = $settings->get('footer_copyright_year') ?: now()->year;
        $settings['footer_copyright_name'] = $settings->get('footer_copyright_name') ?: ($settings->get('school_name') ?: 'SMPN 1 Gondang');
        $settings['footer_description'] = $settings->get('footer_description') ?: 'Sekolah yang mendampingi peserta didik untuk belajar, tumbuh, dan berprestasi.';
        $settings['footer_city'] = $settings->get('address_city', '');
        $settings['footer_region'] = $settings->get('address_region', '');
        $settings['footer_postal_code'] = $settings->get('address_postal_code', '');
        $settings['address_map_url'] = $settings->get('address_map_url', '');
        $settings['phone_alternative'] = $settings->get('phone_alternative', '');

        return view('admin.footer-settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'min:2', 'max:180'],
            'school_tagline' => ['nullable', 'string', 'max:180'],
            'footer_description' => ['required', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_region' => ['nullable', 'string', 'max:120'],
            'address_postal_code' => ['nullable', 'string', 'max:20'],
            'address_map_url' => ['nullable', 'url:http,https', 'max:2048'],
            'email' => ['nullable', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^(?=.*\d)[0-9+().\-\s]+$/'],
            'phone_alternative' => ['nullable', 'string', 'max:50', 'regex:/^(?=.*\d)[0-9+().\-\s]+$/'],
            'footer_navigation' => ['required', 'array', 'max:20'],
            'footer_navigation.*.label' => ['required', 'string', 'max:80'],
            'footer_navigation.*.url' => ['required', 'string', 'max:500', 'regex:/^(https?:\/\/|\/(?!\/)|#|mailto:|tel:)/i'],
            'footer_social' => ['nullable', 'array', 'max:20'],
            'footer_social.*.label' => ['nullable', 'string', 'max:80'],
            'footer_social.*.url' => ['nullable', 'url', 'max:500', 'regex:/^https?:\/\//i'],
            'footer_social.*.username' => ['nullable', 'string', 'max:120'],
            'footer_copyright_year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'footer_copyright_name' => ['required', 'string', 'max:180'],
            'footer_copyright_note' => ['nullable', 'string', 'max:240'],
        ]);
        $data['address_map_url'] = $data['address_map_url'] ?? null;

        $data['footer_navigation'] = array_values(array_filter($data['footer_navigation'], fn ($item) => filled($item['label'] ?? null) && filled($item['url'] ?? null)));
        $data['footer_social'] = array_values(array_filter($data['footer_social'] ?? [], fn ($item) => filled($item['label'] ?? null) && filled($item['url'] ?? null)));
        $data['footer_social_configured'] = true;

        DB::transaction(function () use ($data, $request): void {
            foreach ($data as $key => $value) {
                SiteSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                );
            }

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'perbarui pengaturan footer',
                'detail' => 'Identitas, navigasi, kontak, dan media sosial',
            ]);
        });

        return redirect()->route('admin.footer.edit')->with('success', 'Pengaturan footer berhasil disimpan.');
    }

    private function decodeList(mixed $value, array $fallback): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values($decoded);
            }
        }

        return $fallback;
    }
}
