<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Snapshot of context at view time
            $table->string('role')->nullable();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();

            $table->timestamp('viewed_at')->nullable();        // first time the user saw it
            $table->timestamp('read_confirmed_at')->nullable(); // explicit read acknowledgement

            $table->string('ip_address', 64)->nullable();
            $table->string('device', 512)->nullable();

            $table->timestamps();

            $table->unique(['announcement_id', 'user_id'], 'announcement_read_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
