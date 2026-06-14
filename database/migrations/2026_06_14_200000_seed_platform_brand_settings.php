<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $brandDefaults = [
            ['key' => 'brand_name_ar',         'value' => 'المنصة الذهبية',   'type' => 'string', 'description' => 'اسم المنصة بالعربية'],
            ['key' => 'brand_name_en',         'value' => 'Golden Platform',  'type' => 'string', 'description' => 'Platform name (English)'],
            ['key' => 'brand_logo',            'value' => '',                 'type' => 'string', 'description' => 'مسار شعار المنصة (يُرفع عبر صفحة الإعدادات)'],
            ['key' => 'brand_favicon',         'value' => '',                 'type' => 'string', 'description' => 'مسار الـ favicon'],
            ['key' => 'brand_primary_color',   'value' => '#C9A227',          'type' => 'string', 'description' => 'اللون الأساسي للمنصة'],
            ['key' => 'brand_secondary_color', 'value' => '#14233A',          'type' => 'string', 'description' => 'اللون الثانوي للمنصة'],
            ['key' => 'brand_font',            'value' => 'Cairo',            'type' => 'string', 'description' => 'خط المنصة'],
        ];

        foreach ($brandDefaults as $row) {
            // school_id = NULL = platform-level; only insert if not already set
            DB::table('settings')->insertOrIgnore([
                'school_id'   => null,
                'key'         => $row['key'],
                'value'       => $row['value'],
                'type'        => $row['type'],
                'group'       => 'brand',
                'description' => $row['description'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereNull('school_id')
            ->where('group', 'brand')
            ->delete();
    }
};
