<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = \App\Models\Role::where('slug', 'admin')->first();
        $editorRole = \App\Models\Role::where('slug', 'editor')->first();
        $userRole = \App\Models\Role::where('slug', 'user')->first();

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@oshongshoy.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'role_slug' => 'admin',
            ],
            [
                'name' => 'Editor User', 
                'email' => 'editor@oshongshoy.com',
                'password' => Hash::make('editor123'),
                'email_verified_at' => now(),
                'role_slug' => 'editor',
            ],
            [
                'name' => 'Content Writer',
                'email' => 'writer@oshongshoy.com', 
                'password' => Hash::make('writer123'),
                'email_verified_at' => now(),
                'role_slug' => 'user',
            ],
            [
                'name' => 'Kamal Ahmed',
                'email' => 'kamal@oshongshoy.com',
                'password' => Hash::make('kamal123'),
                'email_verified_at' => now(),
                'role_slug' => 'admin',
            ],
        ];

        foreach ($users as $userData) {
            $roleSlug = $userData['role_slug'];
            unset($userData['role_slug']);
            
            $user = User::create($userData);
            
            // Attach appropriate role
            if ($roleSlug === 'admin' && $adminRole) {
                $user->roles()->attach($adminRole->id);
            } elseif ($roleSlug === 'editor' && $editorRole) {
                $user->roles()->attach($editorRole->id);
            } elseif ($roleSlug === 'user' && $userRole) {
                $user->roles()->attach($userRole->id);
            }
        }
    }
}
