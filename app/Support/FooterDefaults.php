<?php

namespace App\Support;

final class FooterDefaults
{
    public static function navigation(): array
    {
        return [
            ['label' => 'Beranda', 'url' => '/#beranda'],
            ['label' => 'Profil Sekolah', 'url' => '/#profil'],
            ['label' => 'Akademik', 'url' => '/#akademik'],
            ['label' => 'Pengumuman', 'url' => '/#pengumuman'],
            ['label' => 'Ekstrakurikuler', 'url' => '/#ekstrakurikuler'],
            ['label' => 'Galeri', 'url' => '/#galeri'],
        ];
    }

    public static function social(): array
    {
        return [
            ['label' => 'Instagram', 'url' => 'https://www.instagram.com/smp1gondang/'],
            ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@s1nagagondang'],
            ['label' => 'Facebook', 'url' => 'https://www.facebook.com/share/1HpHaBebbb/'],
            ['label' => 'YouTube', 'url' => 'https://youtube.com/@smpn1gondang432'],
        ];
    }
}
