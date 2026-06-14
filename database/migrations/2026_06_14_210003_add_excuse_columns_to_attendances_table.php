<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('excuse_status', ['pending', 'accepted', 'rejected'])->nullable()->default(null)->after('notified_parent');
            $table->text('excuse_text')->nullable()->after('excuse_status');
            $table->timestamp('excuse_submitted_at')->nullable()->after('excuse_text');
            $table->timestamp('excuse_reviewed_at')->nullable()->after('excuse_submitted_at');
            $table->unsignedBigInteger('excuse_reviewed_by')->nullable()->after('excuse_reviewed_at');
            $table->foreign('excuse_reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['excuse_reviewed_by']);
            $table->dropColumn([
                'excuse_status',
                'excuse_text',
                'excuse_submitted_at',
                'excuse_reviewed_at',
                'excuse_reviewed_by',
            ]);
        });
    }
};
