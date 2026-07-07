<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Card #295: the internal mailbox permissions (mailbox.send/draft/delete/archive)
 * were never granted to any role, so composing/sending mail failed closed with 403
 * for everyone except super-admin — teachers (and students/parents/school-admins)
 * could open the inbox (mailbox.view is default-allowed) but not write a new mail.
 * Grant the mailbox permissions to every role that owns a /my/mailbox.
 */
return new class extends Migration
{
    private array $slugs = [
        'mailbox.view', 'mailbox.send', 'mailbox.draft', 'mailbox.delete', 'mailbox.archive',
    ];

    private array $roleSlugs = ['teacher', 'school-admin', 'student', 'parent'];

    public function up(): void
    {
        $now = now();
        $permIds = DB::table('permissions')->whereIn('slug', $this->slugs)->pluck('id', 'slug');
        $roleIds = DB::table('roles')->whereIn('slug', $this->roleSlugs)->pluck('id', 'slug');

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')->whereIn('slug', $this->slugs)->pluck('id');
        $roleIds = DB::table('roles')->whereIn('slug', $this->roleSlugs)->pluck('id');

        DB::table('permission_role')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permIds)
            ->delete();
    }
};
