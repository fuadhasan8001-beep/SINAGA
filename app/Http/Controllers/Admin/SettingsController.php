<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const FIELDS=['school_name'=>'Nama sekolah','school_tagline'=>'Slogan','address'=>'Alamat','phone'=>'Telepon','email'=>'Email','office_hours'=>'Jam pelayanan','principal'=>'Nama kepala sekolah','history'=>'Sejarah sekolah','vision'=>'Visi','mission'=>'Misi','academic_intro'=>'Pengantar akademik','curriculum'=>'Kurikulum','learning_program'=>'Program pembelajaran','academic_calendar'=>'Kalender akademik','ppdb_summary'=>'Ringkasan PPDB','ppdb_url'=>'Tautan PPDB'];
    public function edit(): View
    {
        $settings=SiteSetting::all()->keyBy('key')->map(fn($item)=>json_decode($item->value,true)??$item->value);
        return view('admin.settings',compact('settings')+['fields'=>self::FIELDS]);
    }
    public function update(Request $request): RedirectResponse
    {
        $rules=[];foreach(self::FIELDS as $key=>$label)$rules['settings.'.$key]=['nullable','string','max:3000'];
        $data=$request->validate($rules);
        foreach(self::FIELDS as $key=>$label)SiteSetting::updateOrCreate(['key'=>$key],['value'=>json_encode($data['settings'][$key]??'',JSON_UNESCAPED_UNICODE)]);
        ActivityLog::create(['user_id'=>$request->user()->id,'action'=>'perbarui pengaturan website','detail'=>'Informasi sekolah']);
        return back()->with('success','Pengaturan website disimpan.');
    }
}
