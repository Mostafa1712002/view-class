<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('code', 'DEMO-001')->first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@goldenplatform.com'],
            [
                'name' => 'مدير النظام',
                'name_ar' => 'مدير النظام',
                'name_en' => 'System Administrator',
                'username' => 'admin',
                'password' => bcrypt('Admin@12345'),
                'school_id' => $school?->id,
                'email_verified_at' => now(),
                'is_active' => true,
                'status' => 'active',
                'language_preference' => 'ar',
            ],
        );

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $admin->roles()->syncWithoutDetaching($superAdminRole);
        }
    }
}
