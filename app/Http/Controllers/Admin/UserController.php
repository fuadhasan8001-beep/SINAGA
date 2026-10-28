<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function editProfile(): \Illuminate\View\View { return view('admin.profile'); }
    public function index(): View { return view('admin.users.index',['users'=>User::where('role','teacher')->withCount('articles')->orderBy('name')->paginate(30)]); }
    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','min:2','max:120'],'email'=>['required','email','max:254','unique:users,email'],'password'=>['required','string','min:12','max:200']]);
        $user=User::create([...$data,'role'=>'teacher','active'=>true]);
        ActivityLog::create(['user_id'=>$request->user()->id,'action'=>'buat akun guru','detail'=>$user->email]);
        return back()->with('success','Akun Guru dibuat. Sampaikan kata sandi awal dengan aman.');
    }
    public function update(Request $request,User $user): RedirectResponse
    {
        abort_unless($user->role==='teacher',404);
        $data=$request->validate(['name'=>['required','string','min:2','max:120'],'email'=>['required','email','max:254','unique:users,email,'.$user->id],'active'=>['required','boolean'],'password'=>['nullable','string','min:12','max:200']]);
        $user->fill(['name'=>$data['name'],'email'=>$data['email'],'active'=>$data['active']]);if(!empty($data['password']))$user->password=Hash::make($data['password']);$user->save();
        ActivityLog::create(['user_id'=>$request->user()->id,'action'=>$user->active?'perbarui akun guru':'nonaktifkan akun guru','detail'=>$user->email]);
        return back()->with('success','Akun Guru diperbarui.');
    }
    public function profile(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','min:2','max:120'],'current_password'=>['required_with:new_password','current_password'],'new_password'=>['nullable','string','min:12','max:200','confirmed']]);
        $user=$request->user();$user->name=$data['name'];if(!empty($data['new_password']))$user->password=Hash::make($data['new_password']);$user->save();
        return back()->with('success','Profil diperbarui.');
    }
}
