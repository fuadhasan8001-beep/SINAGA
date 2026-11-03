<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class PanelRoute
{
    public static function name(string $name): string
    {
        $user = Auth::user();

        if ($user?->role === 'teacher' && str_starts_with($name, 'admin.')) {
            $teacherRoute = 'guru.'.substr($name, strlen('admin.'));

            if (Route::has($teacherRoute)) {
                return $teacherRoute;
            }
        }

        return $name;
    }
}
