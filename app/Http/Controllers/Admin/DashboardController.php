<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicController;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ContentEntry;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user=request()->user();
        if($user->isAdmin()) {
            $section = request()->query('section');
            $scrollTarget = is_string($section) && in_array($section, ['profil', 'program', 'berita', 'ekskul', 'galeri', 'kontak', 'layanan'], true)
                ? $section
                : null;

            return view('public.home', [
                ...app(PublicController::class)->publicData(),
                'adminMode' => true,
                'scrollTarget' => $scrollTarget,
            ]);
        }
        $query=Article::query();
        $counts=['total'=>(clone $query)->count(),'draft'=>(clone $query)->where('status','draft')->count(),'pending'=>(clone $query)->where('status','pending')->count(),'revision'=>(clone $query)->where('status','revision')->count(),'published'=>(clone $query)->where('status','published')->count(),'teachers'=>User::where('role','teacher')->where('active',true)->count(),'content'=>ContentEntry::count()];
        if($user->isAdmin()){
            $contentCounts=ContentEntry::selectRaw('type, count(*) as aggregate')->groupBy('type')->pluck('aggregate','type');
            foreach(['announcement','gallery','extracurricular'] as $type)$counts[$type]=(int)($contentCounts[$type]??0);
        }
        $articles=(clone $query)->with(['author','category'])->latest('updated_at')->limit(8)->get();
        $activity=ActivityLog::with('user')->latest()->limit(12)->get();
        return view('admin.dashboard',compact('counts','articles','activity'));
    }
}
