<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['view' => 'عرض الإرشاد الطلابي', 'manage' => 'إدارة الإرشاد الطلابي'] as $action => $name) {
            DB::table('permissions')->updateOrInsert(['slug' => "counseling.$action"],
                ['name' => $name, 'group' => 'counseling', 'created_at' => now(), 'updated_at' => now()]);
            $permissionId = DB::table('permissions')->where('slug', "counseling.$action")->value('id');
            foreach (DB::table('job_titles')->where('slug', 'counselor')->pluck('id') as $jobTitleId) {
                DB::table('job_title_permissions')->updateOrInsert(
                    ['job_title_id' => $jobTitleId, 'permission_id' => $permissionId],
                    ['scope' => 'own', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', ['counseling.view', 'counseling.manage'])->pluck('id');
        DB::table('job_title_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
