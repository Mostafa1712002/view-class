<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Modules\Certificates\Actions\RenderCertificatePdfAction;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Public, tokenised certificate access — the per-certificate share link sent to
 * parents ({certificate_link}). No auth: knowing the random share_token is the
 * grant. Only PUBLISHED certificates are exposed.
 */
class PublicCertificateController extends Controller
{
    public function show(string $token, RenderCertificatePdfAction $action): Response
    {
        $cert = Certificate::query()
            ->where('share_token', $token)
            ->where('status', 'published')
            ->first();

        abort_if(! $cert, 404);

        $binary = $cert->file_path && Storage::disk('public')->exists($cert->file_path)
            && str_ends_with($cert->file_path, '.pdf')
            ? Storage::disk('public')->get($cert->file_path)
            : $action->execute($cert);

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificate-' . $cert->id . '.pdf"',
        ]);
    }
}
