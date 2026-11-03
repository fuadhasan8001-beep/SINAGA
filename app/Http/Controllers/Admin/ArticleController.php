<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Services\ArticleHtml;
use App\Services\DocxImporter;
use App\Support\PanelRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ArticleController extends Controller
{
    public function index(): View
    {
        $query=Article::with(['author','lastEditor','category'])->latest('updated_at');
        return view('admin.articles.index',['articles'=>$query->paginate(20)]);
    }
    public function reviews(): View { return view('admin.articles.reviews',['articles'=>Article::with(['author','category'])->where('status','pending')->latest()->paginate(20)]); }
    public function preview(Article $article): View { abort_unless(request()->user()->isAdmin(),403);return view('admin.articles.preview',compact('article')); }
    public function create(): View { return view('admin.articles.form',['article'=>new Article(),'categories'=>Category::orderBy('name')->get()]); }
    public function edit(Article $article): View { Gate::authorize('update',$article);return view('admin.articles.form',compact('article')+['categories'=>Category::orderBy('name')->get()]); }

    public function store(Request $request, ArticleHtml $sanitizer): RedirectResponse
    {
        $data=$this->validated($request);$user=$request->user();$submit=$request->input('intent','draft');
        $status=$submit==='review'?'published':'draft';
        $requestedPublication = $data['published_at'] ?? null;
        $article=Article::create([...$data,'author_id'=>$user->id,'author_name'=>$user->name,'slug'=>$this->uniqueSlug(filled($data['slug']??null)?$data['slug']:$data['title']),'status'=>$status,'published_at'=>$status==='published'?($requestedPublication??now()):$requestedPublication]);
        $this->log($article,$submit==='review'?'kirim artikel':'buat draf');
        $destination = PanelRoute::name($status === 'published' ? 'admin.dashboard' : 'admin.articles.edit');
        return redirect()->route($destination, $status === 'published' ? [] : [$article])->with('success',$status==='pending'?'Artikel dikirim untuk persetujuan.':($status==='published'?'Artikel diterbitkan.':'Draf disimpan.'));
    }

    public function update(Request $request, Article $article, ArticleHtml $sanitizer): RedirectResponse
    {
        Gate::authorize('update',$article);$data=$this->validated($request);$user=$request->user();$submit=$request->input('intent','draft');
        $status=$submit==='review'?'published':($request->input('status')==='published'?'published':'draft');
        $requestedPublication = $data['published_at'] ?? $article->published_at;
        $article->update([...$data,'slug'=>$this->uniqueSlug(filled($data['slug']??null)?$data['slug']:$data['title'],$article->id),'updated_by'=>$user->id,'updated_by_name'=>$user->name,'status'=>$status,'revision_note'=>null,'published_at'=>$status==='published'?($requestedPublication??now()):$requestedPublication]);
        $this->log($article,$status==='pending'?'kirim ulang artikel':($status==='published'?'perbarui artikel terbit':'perbarui artikel'));
        $destination = PanelRoute::name($status === 'published' ? 'admin.dashboard' : 'admin.articles.edit');
        return redirect()->route($destination, $status === 'published' ? [] : [$article])->with('success',$status==='pending'?'Artikel dikirim ulang untuk persetujuan.':($status==='published'?'Artikel diterbitkan.':'Perubahan disimpan sebagai draf.'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete',$article);$title=$article->title;$cover=$article->cover_image;$article->delete();if($cover)$this->deleteUploadIfUnused($cover);
        ActivityLog::create(['user_id'=>request()->user()->id,'action'=>'hapus artikel','detail'=>$title]);
        return redirect()->route(PanelRoute::name('admin.articles.index'))->with('success','Artikel dihapus.');
    }

    public function review(Request $request, Article $article): RedirectResponse
    {
        Gate::authorize('review',$article);
        $data=$request->validate(['action'=>['required','in:publish,revision,reject'],'note'=>['nullable','string','max:1000']]);
        if($data['action']==='revision'&&blank($data['note']??null))throw ValidationException::withMessages(['note'=>'Catatan revisi wajib diisi.']);
        $status=match($data['action']){'publish'=>'published','revision'=>'revision','reject'=>'rejected'};
        $reviewer = $request->user();
        $article->update(['status'=>$status,'updated_by'=>$reviewer->id,'updated_by_name'=>$reviewer->name,'revision_note'=>in_array($status,['revision','rejected'],true)?$data['note']:null,'published_at'=>$status==='published'?($article->published_at??now()):null]);
        $this->log($article,match($status){'published'=>'setujui dan terbitkan','revision'=>'minta revisi','rejected'=>'tolak artikel'},$data['note']??'');
        $response = $status === 'published' ? redirect()->route(PanelRoute::name('admin.dashboard')) : back();
        return $response->with('success',match($status){'published'=>'Artikel diterbitkan.','revision'=>'Catatan revisi dikirim.','rejected'=>'Artikel ditolak.'});
    }

    public function unpublish(Article $article): RedirectResponse
    {
        abort_unless(request()->user()->isAdmin(),403);abort_unless($article->status==='published',422);
        $editor = request()->user();
        $article->update(['status'=>'draft','updated_by'=>$editor->id,'updated_by_name'=>$editor->name,'published_at'=>null]);$this->log($article,'batalkan publikasi');
        return redirect()->route(PanelRoute::name('admin.dashboard'))->with('success','Publikasi dibatalkan.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['file'=>['required','image','mimes:jpg,jpeg,png,webp,gif','max:5120'],'alt'=>['nullable','string','max:250'],'caption'=>['nullable','string','max:250']]);
        $file=$request->file('file');$path=$file->storePublicly('media','public');
        MediaAsset::create(['uploaded_by'=>$request->user()->id,'path'=>$path,'mime_type'=>$file->getMimeType(),'size'=>$file->getSize(),'alt_text'=>$request->input('alt',''),'caption'=>$request->input('caption','')]);
        return response()->json(['url'=>Storage::disk('public')->url($path),'path'=>$path],201);
    }

    public function importDocx(Request $request, DocxImporter $importer): JsonResponse
    {
        $request->validate(['file'=>['required','file','mimes:docx','max:10240']]);
        try{return response()->json($importer->import($request->file('file'),$request->user()->id));}
        catch(Throwable $e){report($e);return response()->json(['message'=>'Dokumen Word tidak dapat dibaca. Pastikan file .docx valid dan tidak rusak.'],422);}
    }

    private function validated(Request $request): array
    {
        $data=$request->validate(['title'=>['required','string','min:3','max:180'],'slug'=>['nullable','string','max:200','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],'published_at'=>['nullable','date'],'excerpt'=>['nullable','string','max:500'],'category_id'=>['nullable','integer','exists:categories,id'],'body'=>['required','string','max:2000000'],'cover'=>['nullable','image','mimes:jpg,jpeg,png,webp,gif','max:5120'],'remove_cover'=>['nullable','boolean']]);
        $data['excerpt']=$data['excerpt']??'';$data['body']=app(ArticleHtml::class)->clean($data['body']);
        if(trim(strip_tags($data['body']))===''&& !str_contains($data['body'],'<img'))throw ValidationException::withMessages(['body'=>'Isi artikel wajib diisi.']);
        $article=$request->route('article');$cover=$article instanceof Article?$article->cover_image:null;
        if(($data['remove_cover']??false)&&$cover){$this->deleteUploadIfUnused($cover,$article instanceof Article?$article:null);$cover=null;}
        if($request->hasFile('cover')){$file=$request->file('cover');$new=$file->storePublicly('media','public');MediaAsset::create(['uploaded_by'=>$request->user()->id,'path'=>$new,'mime_type'=>$file->getMimeType(),'size'=>$file->getSize(),'alt_text'=>$data['title']]);if($cover)$this->deleteUploadIfUnused($cover,$article instanceof Article?$article:null);$cover=$new;}
        unset($data['cover'],$data['remove_cover']);$data['cover_image']=$cover;
        return $data;
    }

    private function uniqueSlug(string $title, ?int $ignore=null): string
    {
        $base=Str::slug($title)?:'artikel';$slug=$base;$i=2;
        while(Article::where('slug',$slug)->when($ignore,fn($q)=>$q->whereKeyNot($ignore))->exists())$slug=$base.'-'.$i++;
        return $slug;
    }
    private function deleteUploadIfUnused(string $path, ?Article $except = null): void
    {
        $coverUse = Article::where('cover_image', $path)
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->exists();
        $inlineArticleUse = Article::where('body', 'like', '%'.$path.'%')->exists();
        $contentUse = \App\Models\ContentEntry::where(function ($query) use ($path): void {
            $query->where('image_path', $path)->orWhere('body', 'like', '%'.$path.'%');
        })->exists();

        if (! $coverUse && ! $inlineArticleUse && ! $contentUse) {
            Storage::disk('public')->delete($path);
            MediaAsset::where('path', $path)->delete();
        }
    }
    private function log(Article $article,string $action,string $detail=''): void { ActivityLog::create(['user_id'=>request()->user()->id,'article_id'=>$article->id,'action'=>$action,'detail'=>$detail?:$article->title]); }
}
