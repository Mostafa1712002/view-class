<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            // Nullable: super-admin (null active school) owns global templates.
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('name', 255);
            // type: appreciation (شكر) | recognition (تقدير) | general (عام) | grades_notice (إشعار درجات)
            $table->string('type', 32)->default('appreciation');
            // orientation: landscape (أفقي) | portrait (رأسي)
            $table->string('orientation', 16)->default('landscape');
            $table->string('background_path', 255)->nullable();
            $table->string('text_color', 16)->default('#222222');
            $table->string('name_color', 16)->default('#1a3c6e');
            // Body lines / variables payload (JSON) for شكر templates and beyond.
            $table->json('body')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
