<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إدارة رسائل غياب الطالب — the auto (end-of-school-day) send time, evaluated
 * in the school's own timezone by the SendDailyAbsenceMessages command.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_auto_message_settings', function (Blueprint $table) {
            $table->time('send_time')->nullable()->after('threshold');
        });
    }

    public function down(): void
    {
        Schema::table('sms_auto_message_settings', function (Blueprint $table) {
            $table->dropColumn('send_time');
        });
    }
};
