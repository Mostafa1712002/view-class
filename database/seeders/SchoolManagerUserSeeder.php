<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class SchoolManagerUserSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();

        $manager = User::updateOrCreate(
            ['email' => 'manager@alawwal.local'],
            [
                'name' => 'مدير المدرسة',
                'name_ar' => 'مدير المدرسة',
                'name_en' => 'School Manager',
                'username' => 'manager',
                'password' => bcrypt('manager123'),
                'school_id' => $school?->id,
                'gender' => 'male',
                'email_verified_at' => now(),
                'is_active' => true,
                'status' => 'active',
                'language' => 'ar',
                'language_preference' => 'ar',
                'timezone' => 'Asia/Riyadh',
            ],
        );

        $managerRole = Role::where('name', 'مدير المدرسة')->first();
        if ($managerRole) {
            $manager->roles()->syncWithoutDetaching($managerRole);
        }
    }
}
