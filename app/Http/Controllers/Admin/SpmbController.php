<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MediaAsset;
use App\Models\SpmbAnnouncement;
use App\Models\SpmbContact;
use App\Models\SpmbPhoto;
use App\Models\SpmbSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SpmbController extends Controller
{
    public function dashboard(): View
    {
        $settings=$this->settings();
        $announcementCounts=SpmbAnnouncement::selectRaw('published, count(*) as aggregate')->groupBy('published')->pluck('aggregate','published');
        return view('admin.spmb.dashboard',[
            'settings'=>$settings,
            'contacts'=>SpmbContact::orderBy('sort_order')->orderBy('name')->get(),
            'announcements'=>SpmbAnnouncement::latest()->limit(5)->get(),
            'publishedAnnouncements'=>(int)($announcementCounts[1]??0),
            'draftAnnouncements'=>(int)($announcementCounts[0]??0),
        ]);
    }

    public function information(): View { return view('admin.spmb.information',['settings'=>$this->settings()]); }

    public function updateInformation(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'title'=>['required','string','min:3','max:180'],
            'school_year'=>['nullable','string','max:30'],
            'introduction'=>['nullable','string','max:5000'],
            'status'=>['required','in:preparation,open,closed'],
            'quota'=>['nullable','integer','min:0','max:10000'],
            'fee_information'=>['nullable','string','max:2000'],
            'registration_url'=>['nullable','url','max:2048'],
            'published'=>['nullable','boolean'],
        ]);
        $settings=$this->settings();
        $settings->update([...$data,'published'=>$request->boolean('published')]);
        $this->log('perbarui informasi SPMB',$settings->title);
        return back()->with('success','Informasi SPMB disimpan.');
    }

    public function schedule(): View { return view('admin.spmb.schedule',['settings'=>$this->settings()]); }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $data=$request->validate(['schedule_text'=>['nullable','string','max:12000']]);
        $schedule=[];
        foreach(preg_split('/\R/u',$data['schedule_text']??'') as $line){
            if(trim($line)==='')continue;
            $parts=array_map('trim',explode('|',$line,3));
            if(count($parts)<2||$parts[0]===''||$parts[1]==='')throw ValidationException::withMessages(['schedule_text'=>'Setiap baris harus berisi tanggal dan tahap, dipisahkan dengan tanda |.']);
            $schedule[]=['date'=>$parts[0],'stage'=>$parts[1],'details'=>$parts[2]??''];
        }
        $settings=$this->settings();$settings->update(['schedule'=>$schedule]);
        $this->log('perbarui jadwal SPMB',$settings->school_year??'');
        return back()->with('success','Jadwal SPMB disimpan.');
    }

    public function requirements(): View { return view('admin.spmb.requirements',['settings'=>$this->settings()]); }

    public function updateRequirements(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'requirements_text'=>['nullable','string','max:12000'],
            'steps_text'=>['nullable','string','max:12000'],
            'documents_text'=>['nullable','string','max:12000'],
        ]);
        $settings=$this->settings();
        $settings->update([
            'requirements'=>$this->lines($data['requirements_text']??''),
            'steps'=>$this->lines($data['steps_text']??''),
            'documents'=>$this->lines($data['documents_text']??''),
        ]);
        $this->log('perbarui persyaratan SPMB',$settings->school_year??'');
        return back()->with('success','Persyaratan dan alur SPMB disimpan.');
    }

    public function poster(): View { return view('admin.spmb.poster',['settings'=>$this->settings()]); }

    public function updatePoster(Request $request): RedirectResponse
    {
        $request->validate(['poster'=>['required','image','mimes:jpg,jpeg,png,webp','max:10240']]);
        $settings=$this->settings();$oldPath=$settings->poster_path;
        $path=$request->file('poster')->storePublicly('spmb/posters','public');
        try{
            DB::transaction(function()use($request,$settings,$path){
                $settings->update(['poster_path'=>$path]);
                MediaAsset::create(['uploaded_by'=>$request->user()->id,'path'=>$path,'mime_type'=>$request->file('poster')->getMimeType(),'size'=>$request->file('poster')->getSize(),'alt_text'=>'Poster SPMB']);
            });
        }catch(Throwable $exception){Storage::disk('public')->delete($path);throw $exception;}
        if($oldPath){Storage::disk('public')->delete($oldPath);MediaAsset::where('path',$oldPath)->delete();}
        $this->log('ganti poster SPMB',$path);
        return back()->with('success','Poster SPMB diperbarui.');
    }

    public function deletePoster(): RedirectResponse
    {
        $settings=$this->settings();$path=$settings->poster_path;
        $settings->update(['poster_path'=>null]);
        if($path){Storage::disk('public')->delete($path);MediaAsset::where('path',$path)->delete();}
        $this->log('hapus poster SPMB',$path??'');
        return back()->with('success','Poster SPMB dihapus.');
    }

    public function contacts(): View { return view('admin.spmb.contacts.index',['contacts'=>SpmbContact::orderBy('sort_order')->orderBy('name')->get()]); }
    public function createContact(): View { return view('admin.spmb.contacts.form',['contact'=>new SpmbContact()]); }
    public function editContact(SpmbContact $contact): View { return view('admin.spmb.contacts.form',compact('contact')); }

    public function storeContact(Request $request): RedirectResponse
    {
        $data=$this->validatedContact($request);$contact=SpmbContact::create($data);
        $this->log('buat contact person SPMB',$contact->name);
        return redirect()->route('admin.spmb.contacts.index')->with('success','Contact person SPMB ditambahkan.');
    }

    public function updateContact(Request $request,SpmbContact $contact): RedirectResponse
    {
        $contact->update($this->validatedContact($request));$this->log('perbarui contact person SPMB',$contact->name);
        return redirect()->route('admin.spmb.contacts.index')->with('success','Contact person SPMB diperbarui.');
    }

    public function destroyContact(SpmbContact $contact): RedirectResponse
    {
        $name=$contact->name;$contact->delete();$this->log('hapus contact person SPMB',$name);
        return back()->with('success','Contact person SPMB dihapus.');
    }

    public function announcements(): View { return view('admin.spmb.announcements.index',['announcements'=>SpmbAnnouncement::latest()->paginate(20)]); }
    public function createAnnouncement(): View { return view('admin.spmb.announcements.form',['announcement'=>new SpmbAnnouncement()]); }
    public function editAnnouncement(SpmbAnnouncement $announcement): View { return view('admin.spmb.announcements.form',compact('announcement')); }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $announcement=SpmbAnnouncement::create($this->validatedAnnouncement($request));
        $this->log('buat pengumuman SPMB',$announcement->title);
        return redirect()->route('admin.spmb.announcements.index')->with('success','Pengumuman SPMB ditambahkan.');
    }

    public function updateAnnouncement(Request $request,SpmbAnnouncement $announcement): RedirectResponse
    {
        $announcement->update($this->validatedAnnouncement($request));$this->log('perbarui pengumuman SPMB',$announcement->title);
        return redirect()->route('admin.spmb.announcements.index')->with('success','Pengumuman SPMB diperbarui.');
    }

    public function destroyAnnouncement(SpmbAnnouncement $announcement): RedirectResponse
    {
        $title=$announcement->title;$announcement->delete();$this->log('hapus pengumuman SPMB',$title);
        return back()->with('success','Pengumuman SPMB dihapus.');
    }

    public function photos(): View
    {
        return view('admin.spmb.photos.index', ['photos' => SpmbPhoto::orderBy('sort_order')->latest()->paginate(20)]);
    }

    public function createPhoto(): View
    {
        return view('admin.spmb.photos.form', ['photo' => new SpmbPhoto()]);
    }

    public function editPhoto(SpmbPhoto $photo): View
    {
        return view('admin.spmb.photos.form', compact('photo'));
    }

    public function storePhoto(Request $request): RedirectResponse
    {
        $data = $this->validatedPhoto($request, true);
        $data['image_path'] = $request->file('image')->storePublicly('spmb/gallery', 'public');
        $photo = SpmbPhoto::create($data);
        $this->recordPhotoAsset($request, $photo);
        $this->log('tambah dokumentasi SPMB', $photo->title);
        return redirect()->route('admin.spmb.photos.index')->with('success', 'Foto dokumentasi SPMB ditambahkan.');
    }

    public function updatePhoto(Request $request, SpmbPhoto $photo): RedirectResponse
    {
        $data = $this->validatedPhoto($request, false);
        if ($request->hasFile('image')) {
            $oldPath = $photo->image_path;
            $data['image_path'] = $request->file('image')->storePublicly('spmb/gallery', 'public');
        }
        $photo->update($data);
        $this->recordPhotoAsset($request, $photo);
        if (isset($oldPath)) {
            Storage::disk('public')->delete($oldPath);
            MediaAsset::where('path', $oldPath)->delete();
        }
        $this->log('perbarui dokumentasi SPMB', $photo->title);
        return redirect()->route('admin.spmb.photos.index')->with('success', 'Foto dokumentasi SPMB diperbarui.');
    }

    public function destroyPhoto(SpmbPhoto $photo): RedirectResponse
    {
        $path = $photo->image_path;
        $photo->delete();
        Storage::disk('public')->delete($path);
        MediaAsset::where('path', $path)->delete();
        $this->log('hapus dokumentasi SPMB', $photo->title);
        return back()->with('success', 'Foto dokumentasi SPMB dihapus.');
    }

    private function validatedPhoto(Request $request, bool $required): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:160'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'image' => [$required ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'published' => ['nullable', 'boolean'],
        ]);
        $data['published'] = $request->boolean('published');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        unset($data['image']);
        return $data;
    }

    private function recordPhotoAsset(Request $request, SpmbPhoto $photo): void
    {
        if (!$request->hasFile('image')) return;
        MediaAsset::create([
            'uploaded_by' => $request->user()->id,
            'path' => $photo->image_path,
            'mime_type' => $request->file('image')->getMimeType(),
            'size' => $request->file('image')->getSize(),
            'alt_text' => $photo->title,
        ]);
    }

    private function settings(): SpmbSetting
    {
        return SpmbSetting::firstOrCreate(['id'=>1],['title'=>'Penerimaan Murid Baru','published'=>false]);
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim',preg_split('/\R/u',$text)),fn($line)=>$line!==''));
    }

    private function validatedContact(Request $request): array
    {
        $data=$request->validate([
            'name'=>['required','string','min:2','max:120'],
            'role'=>['nullable','string','max:120'],
            'phone'=>['required','string','max:40','regex:/^[0-9+()\-\s]{6,40}$/'],
            'office_hours'=>['nullable','string','max:180'],
            'note'=>['nullable','string','max:1000'],
            'sort_order'=>['nullable','integer','min:0','max:99999'],
            'published'=>['nullable','boolean'],
        ]);
        $data['published']=$request->boolean('published');$data['sort_order']=(int)($data['sort_order']??0);
        return $data;
    }

    private function validatedAnnouncement(Request $request): array
    {
        $data=$request->validate(['title'=>['required','string','min:3','max:180'],'body'=>['required','string','max:10000'],'starts_at'=>['nullable','date'],'published'=>['nullable','boolean']]);
        $data['published']=$request->boolean('published');
        return $data;
    }

    private function log(string $action,string $detail): void
    {
        ActivityLog::create(['user_id'=>request()->user()->id,'action'=>$action,'detail'=>Str::limit($detail,300,'')]);
    }
}
