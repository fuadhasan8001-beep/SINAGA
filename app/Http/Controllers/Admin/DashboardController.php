<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicController;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            ...app(PublicController::class)->publicData(),
            'adminMode' => true,
        ]);
    }
}
