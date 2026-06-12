<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('scope_type', 20)->default('school');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->json('audience')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by');
            $table->unsignedInteger('topics_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_rooms');
    }
};
