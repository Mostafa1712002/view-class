<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an administrator account to the schools it manages. An admin still has
 * a primary users.school_id (default/home school); these rows widen the set of
 * schools the admin may switch to via the header scope selector. A multi-school
 * admin therefore behaves like a super-admin scoped to just its linked schools.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_school', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('school_id');
            $table->timestamps();

            $table->primary(['admin_id', 'school_id']);
            $table->index('school_id');
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_school');
    }
};
