<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContentEntry;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const FIELDS=['school_name'=>'Nama sekolah','school_tagline'=>'Ringkasan identitas sekolah','address'=>'Alamat','phone'=>'Nomor WhatsApp','email'=>'Email','office_hours'=>'Jam pelayanan','principal'=>'Nama kepala sekolah','history'=>'Deskripsi profil sekolah','vision'=>'Visi Sekolah','mission'=>'Misi Sekolah','principal_greeting'=>'Isi sambutan Kepala Sekolah','hero_label'=>'Label kecil Hero','hero_title'=>'Judul Hero','hero_description'=>'Deskripsi Hero','hero_photo_position'=>'Posisi fokus foto Hero','academic_intro'=>'Pengantar akademik','curriculum'=>'Nama kurikulum','curriculum_summary'=>'Ringkasan kurikulum','learning_program'=>'Program pembelajaran','learning_system'=>'Sistem pembelajaran','academic_excellence'=>'Program unggulan akademik','academic_support'=>'Informasi pendukung akademik','academic_calendar'=>'Kalender akademik','ppdb_summary'=>'Ringkasan PPDB','ppdb_url'=>'Tautan PPDB'];
    private const DEFAULT_OFFICE_HOURS = "Senin–Kamis: 07.30–13.30\nJumat: 07.30–11.00\nSabtu: 07.30–12.00\nMinggu: Tutup";

    public function edit(): View
    {
        $settings=SiteSetting::all()->keyBy('key')->map(fn($item)=>json_decode($item->value,true)??$item->value);
        if (! $settings->has('office_hours')) {
            $settings['office_hours'] = self::DEFAULT_OFFICE_HOURS;
        }
        $section = request()->query('section', 'profile');
        abort_unless(in_array($section, ['profile', 'hero', 'academic', 'contact'], true), 404);
        $photoPaths = [];
        if ($section === 'hero') {
            $photoPaths = [
                'hero' => $settings['hero_photo_path'] ?? null,
            ];
        } elseif ($section === 'profile') {
            $photoPaths = [
                'logo' => $settings['school_logo_path'] ?? null,
                'profile' => $settings['profile_photo_path'] ?? ContentEntry::visible()->where('type','teacher')->orderBy('sort_order')->orderBy('id')->value('image_path'),
                'principal' => $settings['principal_photo_path'] ?? null,
            ];
        }
        $academicEntries = $section === 'academic'
            ? ContentEntry::whereIn('type', ['learning_activity','education_calendar','academic_program','academic_assessment'])->orderBy('sort_order')->get()->groupBy('type')
            : collect();
        $editing = in_array($section, ['profile', 'hero'], true) || request()->boolean('edit');
        return view('admin.settings',compact('settings', 'section', 'academicEntries', 'editing', 'photoPaths')+['fields'=>self::FIELDS]);
    }
    public function update(Request $request): RedirectResponse
    {
        $settingsInput = $request->input('settings', []);
        $heroKeys = ['hero_label', 'hero_title', 'hero_description', 'hero_photo_position'];
        if (is_array($settingsInput) && (! $request->user()->isAdmin() || $request->input('section') === 'profile')) {
            foreach ($heroKeys as $heroKey) unset($settingsInput[$heroKey]);
        } elseif (is_array($settingsInput) && $request->input('section') === 'hero') {
            $settingsInput = array_intersect_key($settingsInput, array_flip($heroKeys));
        }
        if ($settingsInput !== $request->input('settings', [])) {
            $request->merge(['settings' => $settingsInput]);
        }
        $rules=[];foreach(self::FIELDS as $key=>$label){$max=in_array($key,['hero_label','hero_title','hero_description'],true)?match($key){'hero_label'=>80,'hero_title'=>180,'hero_description'=>500}:3000;$rules['settings.'.$key]=['nullable','string','max:'.$max];}
        $rules['settings.hero_photo_position']=['nullable','in:center,left,right,top,bottom'];
        $rules['section']=['nullable','in:profile,hero,academic,contact'];
        $data=$request->validate($rules);
        foreach(($data['settings'] ?? []) as $key => $value) {
            SiteSetting::updateOrCreate(['key'=>$key],['value'=>json_encode($value ?? '',JSON_UNESCAPED_UNICODE)]);
        }
        ActivityLog::create(['user_id'=>$request->user()->id,'action'=>'perbarui pengaturan website','detail'=>'Informasi sekolah']);
        if (empty($data['section'])) return redirect()->route('admin.dashboard')->with('success','Pengaturan website disimpan.');
        if ($data['section'] === 'profile') {
            return redirect()->route('admin.dashboard',['section'=>'profil'])->with('success','Profil sekolah disimpan.');
        }
        if ($data['section'] === 'hero') {
            return redirect()->route('admin.settings.edit',['section'=>'hero'])->with('success','Pengaturan Hero disimpan.');
        }
        return redirect()->route('admin.settings.edit',['section'=>$data['section'],'edit'=>1])->with('success','Pengaturan '.($data['section'] === 'academic' ? 'akademik' : 'website').' disimpan.');
    }
}
