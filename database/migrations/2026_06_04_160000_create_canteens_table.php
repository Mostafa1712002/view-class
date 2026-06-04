<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('canteens')) {
            return;
        }

        Schema::create('canteens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->json('target_grades')->nullable();          // الصفوف المستهدفة
            $table->unsignedBigInteger('manager_id')->nullable()->index(); // مسؤول المقصف (إداري)
            $table->boolean('is_active')->default(false);        // ينشأ غير مفعّل
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteens');
    }
};
