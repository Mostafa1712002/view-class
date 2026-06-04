<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('behavior_groups')) {
            return;
        }

        Schema::create('behavior_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->enum('scope', ['student', 'teacher'])->index();   // الطلاب / المعلمين
            $table->string('name');
            $table->enum('type', ['positive', 'negative']);           // إيجابي / سلبي
            $table->boolean('available_for_teacher')->default(true);  // السماح للمعلمين باستخدامها
            $table->boolean('is_active')->default(true);              // تعطيل بدل الحذف
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'scope', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_groups');
    }
};
