<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('username')->nullable()->unique()->after('email');
            $table->enum('language_preference', ['ar', 'en'])->default('ar')->after('username');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active')->after('is_active');
            $table->string('profile_picture')->nullable()->after('avatar');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'name_ar', 'name_en', 'username',
                'language_preference', 'status', 'profile_picture',
            ]);
        });
    }
};
