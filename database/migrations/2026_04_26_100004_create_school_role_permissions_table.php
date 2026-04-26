<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'role_id', 'permission_id'], 'srp_unique');
            $table->index(['school_id', 'role_id'], 'srp_school_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_role_permissions');
    }
};
