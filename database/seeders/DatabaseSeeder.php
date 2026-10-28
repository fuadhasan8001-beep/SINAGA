<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach(['Berita','Kegiatan','Prestasi'] as $name)Category::firstOrCreate(['slug'=>Str::slug($name)],['name'=>$name]);
        $this->call(AdminUserSeeder::class);
    }
}
