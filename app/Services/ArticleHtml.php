<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class ArticleHtml
{
    public function clean(string $html): string
    {
        $config=HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed','p,br,h1,h2,h3,h4,strong,b,em,i,u,s,ul,ol,li,a[href|target|rel],blockquote,img[src|alt|title|width|height],figure,figcaption,hr');
        $config->set('Attr.AllowedFrameTargets',['_blank']);
        $config->set('URI.AllowedSchemes',['http'=>true,'https'=>true,'mailto'=>true]);
        $purifier=new HTMLPurifier($config);
        $safe=$purifier->purify($html);
        $doc=new \DOMDocument('1.0','UTF-8');
        @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="article-root">'.$safe.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        $xpath=new \DOMXPath($doc);
        foreach($xpath->query('//img') as $image){
            $src=$image->getAttribute('src');
            if(!preg_match('~^/storage/media/[a-f0-9-]+\.(?:png|jpe?g|webp|gif)$~i',$src)) $image->removeAttribute('src');
        }
        $root=$doc->getElementById('article-root');
        $result='';
        if($root) foreach($root->childNodes as $child) $result.=$doc->saveHTML($child);
        return $result;
    }
}
