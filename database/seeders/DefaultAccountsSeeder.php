<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAccountsSeeder extends Seeder
{
    private const INITIAL_PASSWORD = 'sinagajaya';

    private const ACCOUNTS = [
        'guru1' => ['name' => 'Guru 1', 'role' => 'teacher'],
        'guru2' => ['name' => 'Guru 2', 'role' => 'teacher'],
        'guru3' => ['name' => 'Guru 3', 'role' => 'teacher'],
        'guru4' => ['name' => 'Guru 4', 'role' => 'teacher'],
        'guru5' => ['name' => 'Guru 5', 'role' => 'teacher'],
        'admin1' => ['name' => 'Admin 1', 'role' => 'admin'],
        'admin2' => ['name' => 'Admin 2', 'role' => 'admin'],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $username => $account) {
            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $account['name'],
                    'email' => $username.'@sinaga.local',
                    'password' => Hash::make(self::INITIAL_PASSWORD),
                    'role' => $account['role'],
                    'active' => true,
                ],
            );

            if ($user->role !== $account['role']) {
                $user->forceFill(['role' => $account['role']])->save();
            }
        }
    }
}
