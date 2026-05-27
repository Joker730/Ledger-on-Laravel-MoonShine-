<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ledger.test'],
            [
                'name'     => 'Admin',
                'email'    => 'admin@ledger.test',
                'password' => Hash::make('password'),
            ]
        );
    }
}
