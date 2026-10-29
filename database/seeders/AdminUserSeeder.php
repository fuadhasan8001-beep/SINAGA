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
        $name=config('sinaga.admin.name');$username=config('sinaga.admin.username');$email=config('sinaga.admin.email');$password=config('sinaga.admin.password');
        if(!$name||!$username||!$email||!$password||strlen($password)<8)throw new RuntimeException('Set ADMIN_NAME, ADMIN_USERNAME, ADMIN_EMAIL, dan ADMIN_PASSWORD (minimal 8 karakter) di .env sebelum menjalankan db:seed.');
        $admin=User::firstOrCreate(['email'=>$email],['name'=>$name,'username'=>$username,'password'=>Hash::make($password),'role'=>'admin','active'=>true]);
        if(!$admin->username){$admin->username=$username;$admin->save();}
    }
}
