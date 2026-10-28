<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View { return view('admin.categories.index',['categories'=>Category::withCount('articles')->orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','min:2','max:80','unique:categories,name']]);
        $category=Category::create(['name'=>$data['name'],'slug'=>Str::slug($data['name'])]);
        ActivityLog::create(['user_id'=>$request->user()->id,'action'=>'buat kategori','detail'=>$category->name]);
        return back()->with('success','Kategori ditambahkan.');
    }
    public function update(Request $request,Category $category): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','min:2','max:80','unique:categories,name,'.$category->id]]);
        $category->update(['name'=>$data['name'],'slug'=>Str::slug($data['name'])]);
        return back()->with('success','Kategori diperbarui.');
    }
    public function destroy(Category $category): RedirectResponse { $category->delete();return back()->with('success','Kategori dihapus. Artikel tetap tersimpan tanpa kategori.'); }
}
