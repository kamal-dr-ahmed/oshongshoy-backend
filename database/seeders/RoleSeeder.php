<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'superadmin',
                'description' => 'Has all permissions and can manage everything including other admins',
                'permissions' => [
                    'manage_users',
                    'manage_roles',
                    'manage_content',
                    'moderate_content',
                    'publish_content',
                    'delete_content',
                    'manage_categories',
                    'manage_tags',
                    'manage_media',
                    'view_analytics',
                    'manage_settings',
                    'block_users',
                    'warn_users',
                    'message_users',
                ],
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Can manage content and moderate users',
                'permissions' => [
                    'manage_content',
                    'moderate_content',
                    'publish_content',
                    'delete_content',
                    'manage_categories',
                    'manage_tags',
                    'manage_media',
                    'view_analytics',
                    'block_users',
                    'warn_users',
                    'message_users',
                ],
            ],
            [
                'name' => 'Moderator',
                'slug' => 'moderator',
                'description' => 'Can moderate and approve content',
                'permissions' => [
                    'moderate_content',
                    'publish_content',
                    'manage_media',
                    'warn_users',
                    'message_users',
                ],
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Can review, approve and moderate content',
                'permissions' => [
                    'moderate_content',
                    'publish_content',
                    'manage_media',
                    'warn_users',
                    'message_users',
                ],
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Regular user who can create and manage their own content',
                'permissions' => [
                    'create_content',
                    'edit_own_content',
                    'delete_own_content',
                    'upload_media',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }
    }
}
