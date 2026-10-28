<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ContentEntry;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user=request()->user();$query=Article::query();if(!$user->isAdmin())$query->where('author_id',$user->id);
        $counts=['total'=>(clone $query)->count(),'draft'=>(clone $query)->where('status','draft')->count(),'pending'=>(clone $query)->where('status','pending')->count(),'revision'=>(clone $query)->where('status','revision')->count(),'published'=>(clone $query)->where('status','published')->count(),'teachers'=>User::where('role','teacher')->where('active',true)->count(),'content'=>ContentEntry::count()];
        $articles=(clone $query)->with(['author','category'])->latest('updated_at')->limit(8)->get();
        $activity=ActivityLog::with('user')->latest()->limit(12)->get();
        return view('admin.dashboard',compact('counts','articles','activity'));
    }
}
