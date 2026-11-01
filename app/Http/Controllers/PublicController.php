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
        return view('public.home', $this->publicViewData());
    }

    public function section(string $section): View
    {
        abort_unless(in_array($section,['profil','akademik','berita','pengumuman','ekskul','ppdb','galeri','kontak'],true),404);
        return view('public.home', [...$this->publicViewData(),'scrollTarget'=>match($section){'pengumuman','ppdb'=>'layanan','galeri'=>'galeri','ekskul'=>'ekskul','akademik'=>'program','kontak'=>'kontak',default=>$section}]);
    }

    public function article(string $slug): View
    {
        $article=Article::published()->with(['author','category'])->where('slug',$slug)->firstOrFail();
        $adminMode = request()->routeIs('admin.website.article') && request()->user()?->isAdmin();

        return view('public.article',['article'=>$article,'adminMode'=>$adminMode]);
    }

    public function contact(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email','max:254'],'subject'=>['required','string','max:180'],'message'=>['required','string','max:5000']]);
        ContactMessage::create($data);
        return back()->with('success','Pesan Anda sudah diterima. Terima kasih.');
    }

    public function publicData(): array
    {
        $entries=ContentEntry::visible()->orderBy('sort_order')->orderBy('starts_at')->get()->groupBy('type');
        $settings=SiteSetting::query()->pluck('value','key')->map(fn($v)=>json_decode($v,true)??$v);
        $settings['principal_greeting_title']='Sambutan Kepala Sekolah';
        return [
            'articles'=>Article::published()->with(['author','category'])->latest('published_at')->get(),
            'announcements'=>$entries->get('announcement',collect()),'events'=>$entries->get('event',collect()),
            'teachers'=>$entries->get('teacher',collect()),'extracurriculars'=>$entries->get('extracurricular',collect()),
            'learningActivities'=>$entries->get('learning_activity',collect()),'educationCalendar'=>$entries->get('education_calendar',collect()),
            'academicPrograms'=>$entries->get('academic_program',collect()),'academicAssessments'=>$entries->get('academic_assessment',collect()),
            'achievements'=>$entries->get('achievement',collect()),'gallery'=>$entries->get('gallery',collect()),
            'documents'=>$entries->get('document',collect()),
            'settings'=>$settings,
            'categories'=>Category::orderBy('name')->get(),
        ];
    }

    private function publicViewData(): array
    {
        return [...$this->publicData(),'adminMode'=>false];
    }
}
