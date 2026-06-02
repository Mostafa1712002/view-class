<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #97 — التقييمات والتعليقات: ratings (1-5, one per user) and comments
 * (toggle-gated) on public library items. Card #96 — add the allow_comments
 * publishing flag to library items.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_items', function (Blueprint $table) {
            if (! Schema::hasColumn('library_items', 'allow_comments')) {
                $table->boolean('allow_comments')->default(true)->after('is_public');
            }
        });

        Schema::create('library_item_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_item_id')->index();
            $table->foreignId('user_id')->index();
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->timestamps();
            $table->unique(['library_item_id', 'user_id']); // one rating per user per item
        });

        Schema::create('library_item_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_item_id')->index();
            $table->foreignId('user_id')->index();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_item_comments');
        Schema::dropIfExists('library_item_ratings');
        Schema::table('library_items', function (Blueprint $table) {
            if (Schema::hasColumn('library_items', 'allow_comments')) {
                $table->dropColumn('allow_comments');
            }
        });
    }
};
