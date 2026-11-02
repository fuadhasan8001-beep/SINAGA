<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicController;
use App\Models\ActivityLog;
use App\Models\ContentEntry;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user=request()->user();
        if($user->isAdmin()) {
            return view('public.home', [
                ...app(PublicController::class)->publicData(),
                'adminMode' => true,
            ]);
        }
        $counts=['teachers'=>User::where('role','teacher')->where('active',true)->count(),'content'=>ContentEntry::count()];
        $activity=ActivityLog::with('user')->latest()->limit(12)->get();
        return view('admin.dashboard',compact('counts','activity'));
    }
}
