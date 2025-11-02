<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@oshongshoy.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Editor User', 
                'email' => 'editor@oshongshoy.com',
                'password' => Hash::make('editor123'),
                'role' => 'editor',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Content Writer',
                'email' => 'writer@oshongshoy.com', 
                'password' => Hash::make('writer123'),
                'role' => 'author',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Kamal Ahmed',
                'email' => 'kamal@oshongshoy.com',
                'password' => Hash::make('kamal123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
