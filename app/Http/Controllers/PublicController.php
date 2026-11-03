<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\ContentEntry;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(): View
    {
        return view('public.home', $this->publicViewData());
    }

    public function section(string $section): View
    {
        abort_unless(in_array($section,['profil','akademik','pengumuman','ekskul','ppdb','galeri','kontak'],true),404);
        return view('public.home', [...$this->publicViewData(),'scrollTarget'=>match($section){'pengumuman','ppdb'=>'pengumuman','galeri'=>'galeri','ekskul'=>'ekstrakurikuler','akademik'=>'akademik','kontak'=>'kontak',default=>$section}]);
    }

    public function principalGreeting(): View
    {
        $adminMode = request()->routeIs('admin.principal-greeting') && request()->user()?->isAdmin();

        return view('public.principal-greeting', [...$this->publicData(), 'adminMode' => $adminMode]);
    }

    public function article(string $slug): View
    {
        $article = Article::published()->with(['author', 'category'])->where('slug', $slug)->firstOrFail();
        $adminMode = request()->routeIs('admin.website.article') && request()->user()?->isAdmin();
        $relatedArticles = Article::published()
            ->with(['author', 'category'])
            ->whereKeyNot($article->id)
            ->when($article->category_id, fn ($query) => $query->where('category_id', $article->category_id))
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('public.article', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'settings' => $this->websiteSettings(),
            'adminMode' => $adminMode,
        ]);
    }

    public function articles(): View
    {
        return view('public.articles', [
            'articles' => Article::published()
                ->with(['author', 'category'])
                ->latest('published_at')
                ->paginate(9),
            'settings' => $this->websiteSettings(),
            'adminMode' => false,
        ]);
    }

    public function extracurricular(string $slug): View
    {
        return $this->contentDetail('extracurricular', $slug, 'Ekstrakurikuler', route('home').'#ekstrakurikuler');
    }

    public function learningActivity(string $slug): View
    {
        return $this->contentDetail('learning_activity', $slug, 'Kegiatan Pembelajaran', route('home').'#akademik');
    }

    public function contact(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email','max:254'],'subject'=>['required','string','max:180'],'message'=>['required','string','max:5000']]);
        ContactMessage::create($data);
        return back()->with('success','Pesan Anda sudah diterima. Terima kasih.');
    }

    public function publicData(): array
    {
        $announcements = ContentEntry::visible()
            ->where('type', 'announcement')
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->each(function (ContentEntry $announcement): void {
                if (! filled($announcement->excerpt)) {
                    $announcement->excerpt = Str::limit(strip_tags($announcement->body ?? ''), 500, '');
                }
            });
        $entries=ContentEntry::visible()->whereNotIn('type',['announcement','gallery'])->orderBy('sort_order')->orderBy('starts_at')->orderBy('id')->get()->groupBy('type');
        $gallery=ContentEntry::visible()->where('type','gallery')->orderBy('sort_order')->orderBy('id')->get();
        $settings=SiteSetting::query()->pluck('value','key')->map(fn($v)=>json_decode($v,true)??$v);
        $settings['principal_greeting_title']='Sambutan Kepala Sekolah';
        return [
            'announcements'=>$announcements,'events'=>$entries->get('event',collect()),
            'teachers'=>$entries->get('teacher',collect()),'extracurriculars'=>$entries->get('extracurricular',collect()),
            'learningActivities'=>$entries->get('learning_activity',collect()),'educationCalendar'=>$entries->get('education_calendar',collect()),
            'academicPrograms'=>$entries->get('academic_program',collect()),'academicAssessments'=>$entries->get('academic_assessment',collect()),
            'achievements'=>$entries->get('achievement',collect()),'gallery'=>$gallery,
            'documents'=>$entries->get('document',collect()),
            'settings'=>$settings,
        ];
    }

    private function publicViewData(): array
    {
        return [...$this->publicData(),'adminMode'=>false];
    }

    private function websiteSettings()
    {
        return SiteSetting::query()->pluck('value', 'key')->map(fn ($value) => json_decode($value, true) ?? $value);
    }

    private function contentDetail(string $type, string $slug, string $sectionTitle, string $backUrl): View
    {
        $entry = ContentEntry::visible()->where('type', $type)->where('slug', $slug)->firstOrFail();
        $settings = SiteSetting::query()->pluck('value', 'key')->map(fn ($value) => json_decode($value, true) ?? $value);

        return view('public.content-detail', [
            'entry' => $entry,
            'settings' => $settings,
            'adminMode' => false,
            'sectionTitle' => $sectionTitle,
            'backUrl' => $backUrl,
        ]);
    }
}
