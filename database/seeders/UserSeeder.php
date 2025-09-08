<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('12345678');

        User::create([
            'name' => 'Admin',
            'nip'=>'1111',
            'password' => $defaultPassword,
            'roles' => 'admin',
        ]);

        User::create([
            'name' => 'Editor',
            'nip'=>'2222',
            'password' => $defaultPassword,
            'roles' => 'editor',
        ]);

        User::create([
            'name' => 'User',
            'nip'=>'3333',
            'password' => $defaultPassword,
            'roles' => 'user',
        ]);
    }
}
