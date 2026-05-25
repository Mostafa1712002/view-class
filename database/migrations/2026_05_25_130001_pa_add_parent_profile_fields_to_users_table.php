<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the extended profile fields requested for parent accounts.
     * All nullable so existing rows and other roles are unaffected.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name_en');
            }
            if (!Schema::hasColumn('users', 'father_name')) {
                $table->string('father_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'grandfather_name')) {
                $table->string('grandfather_name')->nullable()->after('father_name');
            }
            if (!Schema::hasColumn('users', 'family_name')) {
                $table->string('family_name')->nullable()->after('grandfather_name');
            }
            if (!Schema::hasColumn('users', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality')->nullable()->after('birth_place');
            }
            if (!Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['first_name', 'father_name', 'grandfather_name', 'family_name', 'birth_place', 'nationality', 'whatsapp'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
