<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\ContentEntry;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(): View
    {
        return view('public.home', $this->publicData());
    }

    public function section(string $section): View
    {
        abort_unless(in_array($section,['profil','akademik','berita','pengumuman','ekskul','ppdb','galeri','kontak'],true),404);
        return view('public.home', [...$this->publicData(),'scrollTarget'=>match($section){'pengumuman','ppdb'=>'layanan','galeri'=>'galeri','ekskul'=>'ekskul','akademik'=>'program','kontak'=>'kontak',default=>$section}]);
    }

    public function article(string $slug): View
    {
        $article=Article::published()->with(['author','category'])->where('slug',$slug)->firstOrFail();
        return view('public.article',compact('article'));
    }

    public function contact(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email','max:254'],'subject'=>['required','string','max:180'],'message'=>['required','string','max:5000']]);
        ContactMessage::create($data);
        return back()->with('success','Pesan Anda sudah diterima. Terima kasih.');
    }

    private function publicData(): array
    {
        $entries=ContentEntry::visible()->orderBy('sort_order')->orderBy('starts_at')->get()->groupBy('type');
        return [
            'articles'=>Article::published()->with(['author','category'])->latest('published_at')->limit(10)->get(),
            'announcements'=>$entries->get('announcement',collect()),'events'=>$entries->get('event',collect()),
            'teachers'=>$entries->get('teacher',collect()),'extracurriculars'=>$entries->get('extracurricular',collect()),
            'achievements'=>$entries->get('achievement',collect()),'gallery'=>$entries->get('gallery',collect()),
            'documents'=>$entries->get('document',collect()),
            'settings'=>SiteSetting::query()->pluck('value','key')->map(fn($v)=>json_decode($v,true)??$v),
            'categories'=>Category::orderBy('name')->get(),
        ];
    }
}
