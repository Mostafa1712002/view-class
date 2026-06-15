<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_logs', 'broadcast_id')) {
                $table->foreignId('broadcast_id')->nullable()->after('id')
                    ->constrained('whatsapp_broadcasts')->nullOnDelete();
            }
            if (! Schema::hasColumn('whatsapp_logs', 'recipient_user_id')) {
                $table->foreignId('recipient_user_id')->nullable()->after('parent_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('whatsapp_logs', 'recipient_role')) {
                $table->string('recipient_role')->nullable()->after('recipient_user_id');
            }
            if (! Schema::hasColumn('whatsapp_logs', 'message_type')) {
                $table->string('message_type')->nullable()->after('message_text');
            }
            if (! Schema::hasColumn('whatsapp_logs', 'media_path')) {
                $table->string('media_path')->nullable()->after('message_type');
            }
        });

        // Extend the status enum additively to cover the broadcast statuses the
        // card requires, WITHOUT dropping the original attendance statuses.
        // (raw SQL — enum changes aren't expressible via Blueprint cleanly)
        DB::statement(
            "ALTER TABLE whatsapp_logs MODIFY COLUMN status ENUM(
                'pending','sent','failed','skipped',
                'invalid_number','no_number','rejected','delivered','read'
            ) NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            foreach (['broadcast_id', 'recipient_user_id'] as $fk) {
                if (Schema::hasColumn('whatsapp_logs', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            foreach (['recipient_role', 'message_type', 'media_path'] as $col) {
                if (Schema::hasColumn('whatsapp_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        DB::statement(
            "ALTER TABLE whatsapp_logs MODIFY COLUMN status ENUM(
                'pending','sent','failed','skipped'
            ) NOT NULL DEFAULT 'pending'"
        );
    }
};
