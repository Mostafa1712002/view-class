<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'key']);
        });

        // Add only missing profile fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable();
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language')->default('ar');
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('Asia/Riyadh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'notification_preferences')) {
                $cols[] = 'notification_preferences';
            }
            if (Schema::hasColumn('users', 'language')) {
                $cols[] = 'language';
            }
            if (Schema::hasColumn('users', 'timezone')) {
                $cols[] = 'timezone';
            }
            if (count($cols) > 0) {
                $table->dropColumn($cols);
            }
        });

        Schema::dropIfExists('settings');
    }
};
