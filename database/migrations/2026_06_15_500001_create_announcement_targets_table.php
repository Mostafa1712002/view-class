<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();

            // kind: user | role
            $table->enum('kind', ['user', 'role']);

            // For kind=user -> users.id ; for kind=role -> roles.id
            $table->unsignedBigInteger('target_id');

            $table->timestamps();

            $table->index(['announcement_id', 'kind']);
            $table->unique(['announcement_id', 'kind', 'target_id'], 'announcement_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};
