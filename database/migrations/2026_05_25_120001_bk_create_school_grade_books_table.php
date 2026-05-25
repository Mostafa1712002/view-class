<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Books card — school ↔ grade ↔ book pivot.
 * Links a book (digital/ministry) to a grade (classes.id) inside a specific school
 * so admins can manage many books for many grades at once. Unique triple prevents
 * duplicates; integrity is enforced in SyncSchoolGradeBooksAction (legacy int-PK schema,
 * no DB-level FKs to stay consistent with the rest of the project).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_grade_books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('class_id')->index();   // classes.id (the grade / صف)
            $table->unsignedBigInteger('book_id')->index();
            $table->timestamps();

            $table->unique(['school_id', 'class_id', 'book_id'], 'school_grade_book_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_grade_books');
    }
};
