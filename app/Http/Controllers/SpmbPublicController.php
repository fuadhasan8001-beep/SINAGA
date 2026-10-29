<?php

namespace App\Http\Controllers;

use App\Models\SpmbAnnouncement;
use App\Models\SpmbContact;
use App\Models\SpmbSetting;
use App\Models\SiteSetting;
use Illuminate\View\View;

class SpmbPublicController extends Controller
{
    public function show(): View
    {
        return view('public.spmb',[
            'spmb'=>SpmbSetting::where('id',1)->where('published',true)->first(),
            'settings'=>SiteSetting::query()->pluck('value','key')->map(fn($value)=>json_decode($value,true)??$value),
            'contacts'=>SpmbContact::where('published',true)->orderBy('sort_order')->orderBy('name')->get(),
            'announcements'=>SpmbAnnouncement::where('published',true)->latest('starts_at')->latest()->get(),
        ]);
    }
}