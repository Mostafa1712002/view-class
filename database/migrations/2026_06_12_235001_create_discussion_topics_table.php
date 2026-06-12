<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 200);
            $table->text('body');
            $table->unsignedBigInteger('created_by');
            $table->tinyInteger('is_pinned')->default(0);
            $table->tinyInteger('is_closed')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_topics');
    }
};
