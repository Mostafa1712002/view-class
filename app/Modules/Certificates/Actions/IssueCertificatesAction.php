<?php

namespace App\Modules\Certificates\Actions;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Issues a certificate to one or more students from a template, generating and
 * storing each student's PDF synchronously (the card's "Job" + progress model is
 * served inline: progress goes 0 → 100 as each PDF is produced and stored).
 *
 * Returns the created Certificate models.
 */
final class IssueCertificatesAction
{
    public function __construct(private RenderCertificatePdfAction $pdf) {}

    /**
     * @param  array  $attrs           shared attributes (school_id, template_id, type, title, issue_date, note, issued_by, status)
     * @param  array<int>  $recipientIds
     * @return array<Certificate>
     */
    public function execute(array $attrs, array $recipientIds): array
    {
        $created = [];

        foreach (array_unique($recipientIds) as $recipientId) {
            $certificate = Certificate::create(array_merge($attrs, [
                'recipient_user_id' => $recipientId,
                'share_token'       => Str::random(32),
                'progress'          => 0,
            ]));

            // Generate + store the PDF, then mark complete (inline "processing").
            $binary = $this->pdf->execute($certificate);
            $path = 'certificates/generated/' . $certificate->id . '.pdf';
            Storage::disk('public')->put($path, $binary);

            $certificate->update([
                'file_path' => $path,
                'progress'  => 100,
            ]);

            $created[] = $certificate->fresh(['recipient', 'template']);
        }

        return $created;
    }
}
