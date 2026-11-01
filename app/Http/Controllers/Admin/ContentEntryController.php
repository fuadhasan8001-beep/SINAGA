<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Services\ArticleHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContentEntryController extends Controller
{
    public const TYPES=['announcement'=>'Pengumuman','event'=>'Agenda','teacher'=>'Guru','extracurricular'=>'Ekstrakurikuler','learning_activity'=>'Kegiatan Pembelajaran','education_calendar'=>'Kalender Pendidikan','academic_program'=>'Program Kokurikuler & Ekstrakurikuler','academic_assessment'=>'Informasi Asesmen Akademik','achievement'=>'Prestasi','gallery'=>'Galeri','document'=>'Dokumen'];

    public function index(string $type): View
    {
        $this->assertType($type);
        return view('admin.content.index',['type'=>$type,'label'=>self::TYPES[$type],'entries'=>ContentEntry::where('type',$type)->with('author')->orderBy('sort_order')->latest('starts_at')->paginate(20)]);
    }
    public function create(string $type): View { $this->assertType($type);return view('admin.content.form',['type'=>$type,'label'=>self::TYPES[$type],'entry'=>new ContentEntry()]); }
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
        foreach([$entry->image_path,$entry->document_path] as $path)if($path){Storage::disk('public')->delete($path);MediaAsset::where('path',$path)->delete();}
        $title=$entry->title;$entry->delete();ActivityLog::create(['user_id'=>request()->user()->id,'action'=>'hapus '.$type,'detail'=>$title]);
        return $this->afterSave($type, self::TYPES[$type].' dihapus.');
    }

    private function validateData(Request $request,string $type,?ContentEntry $entry=null): array
    {
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
        if($old){Storage::disk('public')->delete($old);MediaAsset::where('path',$old)->delete();}
    }

    private function afterSave(string $type, string $message): RedirectResponse
    {
        return in_array($type, ['learning_activity','education_calendar','academic_program','academic_assessment'], true)
            ? redirect()->route('admin.settings.edit',['section'=>'academic','edit'=>1])->with('success',$message)
            : redirect()->route('admin.content.index',$type)->with('success',$message);
    }

    private function assertType(string $type): void { abort_unless(array_key_exists($type,self::TYPES),404); }
}
