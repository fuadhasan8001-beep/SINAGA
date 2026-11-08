<?php

namespace App\Http\Controllers;

use App\Models\SpmbAnnouncement;
use App\Models\SpmbContact;
use App\Models\SpmbSetting;
use App\Models\SpmbPhoto;
use App\Models\SiteSetting;
use Illuminate\View\View;

class SpmbPublicController extends Controller
{
    public function show(): View
    {
        $spmb=SpmbSetting::where('id',1)->where('published',true)->first();
        $links=collect($spmb?->important_links??[])
            ->filter(fn($link)=>is_array($link)&&!empty($link['published'])&&!empty($link['title'])&&filter_var($link['url']??null,FILTER_VALIDATE_URL)&&in_array(parse_url($link['url'],PHP_URL_SCHEME),['http','https'],true))
            ->sortBy(fn($link)=>(int)($link['sort_order']??0))
            ->values();
        $configuredRegistrationLink=$spmb?->registration_url;
        $registrationLink=filter_var($configuredRegistrationLink,FILTER_VALIDATE_URL)
            &&in_array(parse_url($configuredRegistrationLink,PHP_URL_SCHEME),['http','https'],true)
                ?$configuredRegistrationLink
                :($links->firstWhere('category','registration')['url']??null);

        return view('public.spmb',[
            'spmb'=>$spmb,
            'settings'=>SiteSetting::query()->pluck('value','key')->map(fn($value)=>json_decode($value,true)??$value),
            'contacts'=>SpmbContact::where('published',true)->orderBy('sort_order')->orderBy('name')->get(),
            'announcements'=>SpmbAnnouncement::where('published',true)
                ->orderByRaw('COALESCE(starts_at, created_at) DESC')->get(),
            'photos'=>SpmbPhoto::where('published',true)->orderBy('sort_order')->latest()->get(),
            'links'=>$links,
            'linkCategories'=>['registration'=>'Pendaftaran','form'=>'Formulir','results'=>'Hasil seleksi','guide'=>'Panduan','document'=>'Berkas','other'=>'Informasi lainnya'],
            'registrationLink'=>$registrationLink,
        ]);
    }
}
