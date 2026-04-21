<?php

namespace Database\Seeders;

use App\Models\EducationalCompany;
use App\Models\School;
use Illuminate\Database\Seeder;

class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = EducationalCompany::updateOrCreate(
            ['name_en' => 'ViewClass Demo Company'],
            [
                'name_ar' => 'شركة فيوكلاس التعليمية التجريبية',
                'status' => 'active',
            ],
        );

        $school = School::updateOrCreate(
            ['code' => 'DEMO-001'],
            [
                'educational_company_id' => $company->id,
                'name' => 'مدرسة ابتدائية تجريبية',
                'name_ar' => 'مدرسة ابتدائية تجريبية',
                'name_en' => 'Primary Demo School',
                'educational_track' => 'national',
                'stage' => 'primary',
                'default_language' => 'ar',
                'is_active' => true,
            ],
        );

        School::whereNull('educational_company_id')
            ->where('id', '!=', $school->id)
            ->update(['educational_company_id' => $company->id]);
    }
}
