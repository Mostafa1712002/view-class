<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $tree = [
            ['ar' => 'الفيزياء', 'en' => 'Physics', 'slug' => 'physics', 'children' => [
                ['ar' => 'الحركة', 'en' => 'Motion', 'slug' => 'physics-motion'],
                ['ar' => 'الصوت والموجات', 'en' => 'Sound & Waves', 'slug' => 'physics-sound-waves'],
                ['ar' => 'الشغل والطاقة والقدرة', 'en' => 'Work, Energy & Power', 'slug' => 'physics-work-energy'],
                ['ar' => 'الحرارة والديناميكا الحرارية', 'en' => 'Heat & Thermodynamics', 'slug' => 'physics-heat'],
                ['ar' => 'الظواهر الكمية', 'en' => 'Quantum Phenomena', 'slug' => 'physics-quantum'],
                ['ar' => 'الضوء والإشعاع', 'en' => 'Light & Radiation', 'slug' => 'physics-light'],
                ['ar' => 'الكهرباء والمغناطيسية والدوران', 'en' => 'Electricity & Magnetism', 'slug' => 'physics-electricity'],
            ]],
            ['ar' => 'الكيمياء', 'en' => 'Chemistry', 'slug' => 'chemistry', 'children' => [
                ['ar' => 'الكيمياء العامة', 'en' => 'General Chemistry', 'slug' => 'chemistry-general'],
                ['ar' => 'الكيمياء الكمية', 'en' => 'Quantum Chemistry', 'slug' => 'chemistry-quantum'],
            ]],
            ['ar' => 'الرياضيات', 'en' => 'Mathematics', 'slug' => 'mathematics', 'children' => [
                ['ar' => 'مفاهيم الرياضيات', 'en' => 'Math Concepts', 'slug' => 'math-concepts'],
                ['ar' => 'تطبيقات الرياضيات', 'en' => 'Math Applications', 'slug' => 'math-applications'],
            ]],
            ['ar' => 'علم الأرض', 'en' => 'Earth Science', 'slug' => 'earth-science', 'children' => []],
            ['ar' => 'علم الأحياء', 'en' => 'Biology', 'slug' => 'biology', 'children' => []],
        ];

        $sort = 0;
        foreach ($tree as $node) {
            $parentId = DB::table('virtual_lab_categories')->insertGetId([
                'name_ar' => $node['ar'],
                'name_en' => $node['en'],
                'parent_id' => null,
                'slug' => $node['slug'],
                'sort_order' => $sort++,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $childSort = 0;
            foreach ($node['children'] as $child) {
                DB::table('virtual_lab_categories')->insert([
                    'name_ar' => $child['ar'],
                    'name_en' => $child['en'],
                    'parent_id' => $parentId,
                    'slug' => $child['slug'],
                    'sort_order' => $childSort++,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('virtual_lab_categories')->truncate();
    }
};
