<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Services\ArticleHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentEntryController extends Controller
{
    public const TYPES=['announcement'=>'Pengumuman','event'=>'Agenda','teacher'=>'Guru','extracurricular'=>'Ekstrakurikuler','learning_activity'=>'Kegiatan Pembelajaran','education_calendar'=>'Kalender Pendidikan','academic_program'=>'Program Ekstrakurikuler','academic_assessment'=>'Informasi Asesmen Akademik','achievement'=>'Prestasi','gallery'=>'Galeri','document'=>'Dokumen'];

    public function index(string $type): View
    {
        $this->assertType($type);
        $entries = $type === 'gallery'
            ? ContentEntry::where('type', 'gallery')->orderBy('sort_order')->orderBy('id')->get()
            : ContentEntry::where('type', $type)->with('author')->orderBy('sort_order')->orderByDesc('starts_at')->orderByDesc('id')->paginate(20);

        return view('admin.content.index',['type'=>$type,'label'=>self::TYPES[$type],'entries'=>$entries]);
    }
    public function create(string $type): View
    {
        $this->assertType($type);
        $entry = new ContentEntry();
        if ($type === 'gallery') $entry->sort_order = (int) ContentEntry::where('type', 'gallery')->max('sort_order') + 1;
        if ($type === 'announcement') {
            $entry->sort_order = (int) ContentEntry::where('type', 'announcement')->max('sort_order') + 1;
            $entry->published = true;
        }
        if (in_array($type, ['extracurricular', 'learning_activity'], true)) {
            $entry->sort_order = (int) ContentEntry::where('type', $type)->max('sort_order') + 1;
            $entry->published = true;
        }

        return view('admin.content.form',['type'=>$type,'label'=>self::TYPES[$type],'entry'=>$entry]);
    }
    public function edit(string $type,ContentEntry $entry): View { $this->assertType($type);abort_unless($entry->type===$type,404);return view('admin.content.form',['type'=>$type,'label'=>self::TYPES[$type],'entry'=>$entry]); }

    public function store(Request $request,string $type): RedirectResponse
    {
        $this->assertType($type);$data=$this->validateData($request,$type);
        $entry=ContentEntry::create([...$data,'type'=>$type,'author_id'=>$request->user()->id,'slug'=>Str::slug($data['title']).'-'.Str::lower(Str::random(5))]);
        ActivityLog::create(['user_id'=>$request->user()->id,'action'=>'buat '.$type,'detail'=>$entry->title]);
        return $this->afterSave($type, self::TYPES[$type].' berhasil ditambahkan.');
    }

    public function update(Request $request,string $type,ContentEntry $entry): RedirectResponse
    {
        $this->assertType($type);abort_unless($entry->type===$type,404);$data=$this->validateData($request,$type,$entry);
        $entry->update($data);ActivityLog::create(['user_id'=>$request->user()->id,'action'=>'perbarui '.$type,'detail'=>$entry->title]);
        return $this->afterSave($type, self::TYPES[$type].' diperbarui.');
    }

    public function destroy(string $type,ContentEntry $entry): RedirectResponse
    {
        $this->assertType($type);abort_unless($entry->type===$type,404);
        foreach([$entry->image_path,$entry->document_path] as $path)if($path)$this->deleteUploadIfUnused($path, $entry);
        $title=$entry->title;$entry->delete();ActivityLog::create(['user_id'=>request()->user()->id,'action'=>'hapus '.$type,'detail'=>$title]);
        return $this->afterSave($type, self::TYPES[$type].' dihapus.');
    }

    public function reorderGallery(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $orderedIds = array_map('intval', $data['ordered_ids']);
        $galleryIds = ContentEntry::where('type', 'gallery')->orderBy('id')->pluck('id')->all();
        $submittedIds = $orderedIds;
        sort($submittedIds);
        $expectedIds = array_map('intval', $galleryIds);
        sort($expectedIds);
        abort_unless($submittedIds === $expectedIds, 422, 'Daftar foto galeri berubah. Muat ulang halaman lalu coba lagi.');

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                ContentEntry::where('type', 'gallery')->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return response()->json(['message' => 'Urutan galeri tersimpan.']);
    }

    private function validateData(Request $request,string $type,?ContentEntry $entry=null): array
    {
        if ($type === 'gallery') {
            $data = $request->validate([
                'title' => ['required', 'string', 'min:2', 'max:180'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
                'image' => [$entry ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
            $data['sort_order'] = filled($data['sort_order'] ?? null)
                ? (int) $data['sort_order']
                : ($entry?->sort_order ?? ((int) ContentEntry::where('type', 'gallery')->max('sort_order') + 1));
            $data['published'] = true;
            $this->saveUpload($request, 'image', $data, $entry, 'image_path', 'media');
            unset($data['image']);

            return $data;
        }

        if ($type === 'extracurricular') {
            $validated = $request->validate([
                'title' => ['required', 'string', 'min:2', 'max:180'],
                'excerpt' => ['required', 'string', 'max:240'],
                'body' => ['nullable', 'string', 'max:500000'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
                'published' => ['nullable', 'boolean'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
            $data = [
                'title' => $validated['title'],
                'excerpt' => $validated['excerpt'],
                'sort_order' => filled($validated['sort_order'] ?? null)
                    ? (int) $validated['sort_order']
                    : ($entry?->sort_order ?? ((int) ContentEntry::where('type', 'extracurricular')->max('sort_order') + 1)),
                'published' => array_key_exists('published', $validated)
                    ? (bool) $validated['published']
                    : ($entry?->published ?? true),
            ];
            if (array_key_exists('body', $validated)) {
                $data['body'] = app(ArticleHtml::class)->clean($validated['body'] ?? '');
            }
            $this->saveUpload($request, 'image', $data, $entry, 'image_path', 'media');
            unset($data['image']);

            return $data;
        }

        if ($type === 'learning_activity') {
            $validated = $request->validate([
                // Older admin clients only submitted the shared academic body field.
                'title' => ['nullable', 'string', 'min:2', 'max:180'],
                'excerpt' => ['nullable', 'string', 'max:500'],
                'body' => ['nullable', 'string', 'max:500000'],
                'starts_at' => ['nullable', 'date'],
                'location' => ['nullable', 'string', 'max:180'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
                'published' => ['nullable', 'boolean'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
            $data = [
                'title' => $validated['title'] ?? $entry?->title ?? self::TYPES[$type],
                'excerpt' => $validated['excerpt'] ?? '',
                'starts_at' => $validated['starts_at'] ?? $entry?->starts_at,
                'location' => $validated['location'] ?? $entry?->location,
                'sort_order' => filled($validated['sort_order'] ?? null)
                    ? (int) $validated['sort_order']
                    : ($entry?->sort_order ?? ((int) ContentEntry::where('type', 'learning_activity')->max('sort_order') + 1)),
                'published' => array_key_exists('published', $validated)
                    ? (bool) $validated['published']
                    : ($entry?->published ?? true),
            ];
            if (array_key_exists('body', $validated)) {
                $data['body'] = app(ArticleHtml::class)->clean($validated['body'] ?? '');
            }
            $this->saveUpload($request, 'image', $data, $entry, 'image_path', 'media');

            return $data;
        }

        if ($type === 'announcement') {
            $validated = $request->validate([
                'title' => ['required', 'string', 'min:2', 'max:180'],
                'description' => [Rule::requiredIf(! $request->has('excerpt') && ! $request->has('body')), 'nullable', 'string', 'max:5000'],
                // Keep accepting the previous form payload during deployments and older clients.
                'excerpt' => ['nullable', 'string', 'max:500'],
                'body' => ['nullable', 'string', 'max:500000'],
                'starts_at' => ['nullable', 'date'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
                'published' => ['nullable', 'boolean'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);

            $data = [
                'title' => $validated['title'],
                'starts_at' => $validated['starts_at'] ?? null,
                'sort_order' => filled($validated['sort_order'] ?? null)
                    ? (int) $validated['sort_order']
                    : ($entry?->sort_order ?? ((int) ContentEntry::where('type', 'announcement')->max('sort_order') + 1)),
                'published' => array_key_exists('published', $validated)
                    ? (bool) $validated['published']
                    : ($entry?->published ?? true),
            ];

            if (filled($validated['description'] ?? null)) {
                $description = trim($validated['description']);
                $data['excerpt'] = Str::limit($description, 500, '');
                $data['body'] = nl2br(e($description), false);
            } else {
                $data['excerpt'] = $validated['excerpt'] ?? '';
                $data['body'] = app(ArticleHtml::class)->clean($validated['body'] ?? '');
            }

            $this->saveUpload($request, 'image', $data, $entry, 'image_path', 'media');
            unset($data['image']);

            return $data;
        }

        $academicTypes=['learning_activity','education_calendar','academic_program','academic_assessment'];
        $rules=['title'=>[in_array($type,$academicTypes,true)?'nullable':'required','string','min:2','max:180'],'excerpt'=>['nullable','string','max:500'],'body'=>['nullable','string','max:500000'],'location'=>['nullable','string','max:180'],'starts_at'=>['nullable','date'],'ends_at'=>['nullable','date','after_or_equal:starts_at'],'program_category'=>['nullable','in:kokurikuler,ekstrakurikuler'],'assessment_type'=>['nullable','string','max:180'],'sort_order'=>['nullable','integer','min:0','max:99999'],'published'=>['nullable','boolean'],'image'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],'document'=>['nullable','file','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx','max:10240'],'metadata_json'=>['nullable','json','max:20000']];
        $data=$request->validate($rules);
        if (in_array($type, $academicTypes, true)) {
            $data['title'] = self::TYPES[$type];
            $data['excerpt'] = '';
            $data['sort_order'] = 0;
            if ($type === 'academic_program') $data['program_category'] = 'kokurikuler';
        }
        $data['excerpt']=$data['excerpt']??'';$data['body']=app(ArticleHtml::class)->clean($data['body']??'');
        $data['published']=($type === 'announcement' || in_array($type,$academicTypes,true)) ? true : $request->boolean('published');$data['sort_order']=(int)($data['sort_order']??0);
        $metadata=json_decode($data['metadata_json']??'{}',true);$data['metadata']=is_array($metadata)?$metadata:[];
        foreach(['ends_at','program_category','assessment_type'] as $field) if(filled($data[$field]??null)) $data['metadata'][$field]=$data[$field];
        unset($data['metadata_json'],$data['ends_at'],$data['program_category'],$data['assessment_type']);
        $this->saveUpload($request,'image',$data,$entry,'image_path','media');
        $this->saveUpload($request,'document',$data,$entry,'document_path','documents');
        unset($data['image'],$data['document']);
        return $data;
    }

    private function saveUpload(Request $request,string $field,array &$data,?ContentEntry $entry,string $column,string $directory): void
    {
        if(!$request->hasFile($field)){$data[$column]=$entry?->{$column};return;}
        $old=$entry?->{$column};$file=$request->file($field);$path=$file->storePublicly($directory,'public');$data[$column]=$path;
        MediaAsset::create(['uploaded_by'=>$request->user()->id,'path'=>$path,'mime_type'=>$file->getMimeType(),'size'=>$file->getSize()]);
        if($old && $old !== $path)$this->deleteUploadIfUnused($old, $entry);
    }

    private function deleteUploadIfUnused(string $path, ?ContentEntry $except = null): void
    {
        $usedByContent = ContentEntry::where(function ($query) use ($path) {
            $query->where('image_path', $path)->orWhere('document_path', $path);
        })->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))->exists();
        $usedByArticle = \App\Models\Article::where('cover_image', $path)->exists();

        if (!$usedByContent && !$usedByArticle) {
            Storage::disk('public')->delete($path);
            MediaAsset::where('path', $path)->delete();
        }
    }

    private function afterSave(string $type, string $message): RedirectResponse
    {
        return in_array($type, ['learning_activity','education_calendar','academic_program','academic_assessment'], true)
            ? redirect()->route('admin.settings.edit',['section'=>'academic','edit'=>1])->with('success',$message)
            : redirect()->route('admin.content.index',$type)->with('success',$message);
    }

    private function assertType(string $type): void { abort_unless(array_key_exists($type,self::TYPES),404); }
}
