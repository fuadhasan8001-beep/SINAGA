<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\SpmbSetting;
use Illuminate\Http\Response;

class SpmbPublicController extends Controller
{
    public function show(): Response
    {
        return response()->view('public.spmb', [
            'spmb' => SpmbSetting::query()->find(1),
            'settings' => SiteSetting::query()->pluck('value', 'key')->map(fn ($value) => json_decode($value, true) ?? $value),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
