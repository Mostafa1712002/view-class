<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('educational_company_id')->nullable()->after('id')
                ->constrained('educational_companies')->nullOnDelete();
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('branch')->nullable()->after('name_en');
            $table->unsignedInteger('sort_order')->nullable()->after('branch');
            $table->enum('educational_track', ['national', 'international', 'general', 'k12'])
                ->default('national')->after('sort_order');
            $table->string('stage')->nullable()->after('educational_track');
            $table->string('city')->nullable()->after('stage');
            $table->enum('default_language', ['ar', 'en'])->default('ar')->after('city');
            $table->string('fax')->nullable()->after('phone');
            $table->string('cover_image')->nullable()->after('logo');
            $table->string('facebook')->nullable()->after('website');
            $table->string('twitter')->nullable()->after('facebook');
            $table->string('instagram')->nullable()->after('twitter');
            $table->string('linkedin')->nullable()->after('instagram');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('educational_company_id');
            $table->dropColumn([
                'name_ar', 'name_en', 'branch', 'sort_order',
                'educational_track', 'stage', 'city', 'default_language',
                'fax', 'cover_image', 'facebook', 'twitter', 'instagram', 'linkedin',
            ]);
        });
    }
};
