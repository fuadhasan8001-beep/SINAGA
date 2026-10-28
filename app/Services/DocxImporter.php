<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;

class DocxImporter
{
    public function import(UploadedFile $upload, int $userId): array
    {
        $zip=new \ZipArchive();
        if($zip->open($upload->getRealPath())!==true) throw new RuntimeException('Dokumen Word tidak valid atau rusak.');
        $total=0;
        if($zip->numFiles>500){$zip->close();throw new RuntimeException('Dokumen memuat terlalu banyak bagian.');}
        for($i=0;$i<$zip->numFiles;$i++){$stat=$zip->statIndex($i);$total+=(int)($stat['size']??0);if($total>30*1024*1024){$zip->close();throw new RuntimeException('Isi dokumen melebihi batas pemrosesan 30 MB.');}}
        $zip->close();

        $word=IOFactory::load($upload->getRealPath(),'Word2007');
        $writer=IOFactory::createWriter($word,'HTML');
        $temp=tempnam(sys_get_temp_dir(),'sinaga-docx-');
        try{$writer->save($temp);$html=file_get_contents($temp)?:'';}finally{@unlink($temp);}
        $doc=new \DOMDocument('1.0','UTF-8');
        if(!@$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING)) throw new RuntimeException('Konten dokumen tidak dapat dibaca.');
        $xpath=new \DOMXPath($doc);
        $warnings=[];
        foreach($xpath->query('//img') as $image){
            $src=$image->getAttribute('src');
            if(!preg_match('~^data:image/(png|jpeg|gif|webp);base64,([a-zA-Z0-9+/=]+)$~',$src,$match)){$image->parentNode?->removeChild($image);$warnings[]='Sebagian gambar dengan format yang tidak didukung dilewati.';continue;}
            $bytes=base64_decode($match[2],true);$info=$bytes===false?false:@getimagesizefromstring($bytes);
            if(!$info||!in_array($info['mime'],['image/png','image/jpeg','image/gif','image/webp'],true)||strlen($bytes)>5*1024*1024){$image->parentNode?->removeChild($image);$warnings[]='Sebagian gambar tidak valid atau terlalu besar dan dilewati.';continue;}
            $ext=match($info['mime']){'image/png'=>'png','image/jpeg'=>'jpg','image/gif'=>'gif','image/webp'=>'webp'};
            $path='media/'.Str::uuid().'.'.$ext;
            Storage::disk('public')->put($path,$bytes);
            MediaAsset::create(['uploaded_by'=>$userId,'path'=>$path,'mime_type'=>$info['mime'],'size'=>strlen($bytes)]);
            $image->setAttribute('src',Storage::disk('public')->url($path));
            $image->setAttribute('alt','');
            $image->removeAttribute('style');$image->removeAttribute('border');
        }
        $body=$doc->getElementsByTagName('body')->item(0);
        $bodyHtml='';if($body)foreach($body->childNodes as $child)$bodyHtml.=$doc->saveHTML($child);
        $bodyHtml=app(ArticleHtml::class)->clean($bodyHtml);
        if(trim(strip_tags($bodyHtml))==='') throw new RuntimeException('Dokumen tidak berisi teks yang dapat diimpor.');
        return ['html'=>$bodyHtml,'warnings'=>array_values(array_unique($warnings))];
    }
}
