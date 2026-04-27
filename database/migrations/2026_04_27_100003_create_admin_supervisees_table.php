<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_supervisees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('supervisee_type', 32);
            $table->foreignId('supervisee_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['admin_id', 'supervisee_type', 'supervisee_id'], 'admin_super_unique');
            $table->index(['supervisee_type', 'supervisee_id'], 'admin_super_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_supervisees');
    }
};
