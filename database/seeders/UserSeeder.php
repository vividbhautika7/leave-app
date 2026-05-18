<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin717@yopmail.com',
            'password' => Hash::make('12345678'),
            'role'     => 'admin',
        ]);

        // Employees
        User::create([
            'name'     => 'Test user',
            'email'    => 'test_user717@yopmail.com',
            'password' => Hash::make('12345678'),
            'role'     => 'employee',
        ]);
    }
}
