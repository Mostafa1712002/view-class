<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_item_reactions')) {
            return;
        }

        Schema::create('library_item_reactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('library_item_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('type', ['like', 'dislike', 'understood']);
            $table->timestamps();

            // One row per (item, user, type). Like/dislike mutual exclusivity is enforced
            // in the controller; "understood" is an independent toggle.
            $table->unique(['library_item_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_item_reactions');
    }
};
