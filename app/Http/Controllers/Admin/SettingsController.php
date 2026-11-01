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
    private const FIELDS=['school_name'=>'Nama sekolah','school_tagline'=>'Ringkasan identitas sekolah','address'=>'Alamat','phone'=>'Telepon','email'=>'Email','office_hours'=>'Jam pelayanan','principal'=>'Nama kepala sekolah','history'=>'Deskripsi profil sekolah','vision'=>'Visi Sekolah','mission'=>'Misi Sekolah','principal_greeting'=>'Isi sambutan Kepala Sekolah','academic_intro'=>'Pengantar akademik','curriculum'=>'Nama kurikulum','curriculum_summary'=>'Ringkasan kurikulum','learning_program'=>'Program pembelajaran','learning_system'=>'Sistem pembelajaran','academic_excellence'=>'Program unggulan akademik','academic_support'=>'Informasi pendukung akademik','academic_calendar'=>'Kalender akademik','ppdb_summary'=>'Ringkasan PPDB','ppdb_url'=>'Tautan PPDB'];
    public function edit(): View
    {
        $settings=SiteSetting::all()->keyBy('key')->map(fn($item)=>json_decode($item->value,true)??$item->value);
        $section = request()->query('section', 'profile');
        abort_unless(in_array($section, ['profile', 'academic', 'contact'], true), 404);
        $academicEntries = $section === 'academic'
            ? ContentEntry::whereIn('type', ['learning_activity','education_calendar','academic_program','academic_assessment'])->orderBy('sort_order')->get()->groupBy('type')
            : collect();
        $editing = $section === 'profile' || request()->boolean('edit');
        return view('admin.settings',compact('settings', 'section', 'academicEntries', 'editing')+['fields'=>self::FIELDS]);
    }
    public function update(Request $request): RedirectResponse
    {
        $rules=[];foreach(self::FIELDS as $key=>$label)$rules['settings.'.$key]=['nullable','string','max:3000'];
        $rules['section']=['nullable','in:profile,academic,contact'];
        $data=$request->validate($rules);
        foreach(($data['settings'] ?? []) as $key => $value) {
            SiteSetting::updateOrCreate(['key'=>$key],['value'=>json_encode($value ?? '',JSON_UNESCAPED_UNICODE)]);
        }
        ActivityLog::create(['user_id'=>$request->user()->id,'action'=>'perbarui pengaturan website','detail'=>'Informasi sekolah']);
        if (empty($data['section'])) return redirect()->route('admin.dashboard')->with('success','Pengaturan website disimpan.');
        if ($data['section'] === 'profile') {
            return redirect()->route('admin.dashboard',['section'=>'profil'])->with('success','Profil sekolah disimpan.');
        }
        return redirect()->route('admin.settings.edit',['section'=>$data['section'],'edit'=>1])->with('success','Pengaturan '.($data['section'] === 'academic' ? 'akademik' : 'website').' disimpan.');
    }
}
