<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Signer name printed under the signature line.
            $table->string('signer_name')->nullable()->after('note');
            // 'manual' (canvas-drawn) | 'file' (uploaded image).
            $table->string('signature_type')->nullable()->after('signer_name');
            // Stored signature image (public disk): certificates/signatures/*.
            $table->string('signature_path')->nullable()->after('signature_type');
            // Optional logo shown at the top: certificates/logos/*.
            $table->string('logo_path')->nullable()->after('signature_path');
            // Optional stamp/seal image: certificates/stamps/*.
            $table->string('stamp_path')->nullable()->after('logo_path');
            // Rich free-text body used by 'general' certificates.
            $table->longText('body_html')->nullable()->after('stamp_path');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'signer_name',
                'signature_type',
                'signature_path',
                'logo_path',
                'stamp_path',
                'body_html',
            ]);
        });
    }
};
