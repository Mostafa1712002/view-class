<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A super-admin has no single school, so a support ticket they open has no
     * school_id. The column was NOT NULL, which crashed ticket creation for
     * super-admins. Allow null; such tickets surface only in the super-admin's
     * all-schools view (getSchoolTickets skips the school filter when null).
     */
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
        });
    }
};
