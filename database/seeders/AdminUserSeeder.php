<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء مدير النظام الرئيسي
        $admin = User::updateOrCreate(
            ['email' => 'admin@goldenplatform.com'],
            [
                'name' => 'مدير النظام',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // تعيين دور مدير النظام
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $admin->roles()->syncWithoutDetaching($superAdminRole);
        }
    }
}
