<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Link to a design template (nullable: legacy file-upload certs have none).
            $table->unsignedBigInteger('template_id')->nullable()->after('type')->index();
            // Public share token for the per-certificate link (المعاينة والإرسال).
            $table->string('share_token', 40)->nullable()->unique()->after('file_path');
            // Synchronous "processing" progress to satisfy the card's progress column.
            $table->unsignedTinyInteger('progress')->default(0)->after('share_token');

            $table->foreign('template_id')->references('id')->on('certificate_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'share_token', 'progress']);
        });
    }
};
