<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name=config('sinaga.admin.name');$email=config('sinaga.admin.email');$password=config('sinaga.admin.password');
        if(!$name||!$email||!$password||strlen($password)<12)throw new RuntimeException('Set ADMIN_NAME, ADMIN_EMAIL, dan kata sandi ADMIN_PASSWORD minimal 12 karakter di .env sebelum menjalankan db:seed.');
        User::firstOrCreate(['email'=>$email],['name'=>$name,'password'=>Hash::make($password),'role'=>'admin','active'=>true]);
    }
}
