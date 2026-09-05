<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card «الشركات والمجمعات التعليمية»: the complexes admin CRUD accepts a logo
 * upload (design.md), but the compounds table never had one. Additive column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compounds', function (Blueprint $table) {
            if (! Schema::hasColumn('compounds', 'logo')) {
                $table->string('logo')->nullable()->after('name_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compounds', function (Blueprint $table) {
            if (Schema::hasColumn('compounds', 'logo')) {
                $table->dropColumn('logo');
            }
        });
    }
};
