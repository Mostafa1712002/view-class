<?php

namespace App\Modules\Certificates\Actions;

use App\Models\Certificate;

/**
 * Renders a single certificate to a PDF (mPDF, Arabic RTL, XB Riyaz font),
 * branded "المنصة الذهبية", overlaying dynamic text fields on the template
 * background. Dynamic fields are real HTML text (not baked into the image) so
 * the Arabic content remains extractable / high-resolution.
 */
final class RenderCertificatePdfAction
{
    /** Return the PDF as a binary string. */
    public function execute(Certificate $certificate): string
    {
        $certificate->loadMissing(['recipient:id,name', 'issuer:id,name', 'template']);
        $template = $certificate->template;

        $orientation = $template && $template->orientation === 'portrait' ? 'P' : 'L';

        $html = view('certificates.pdf.certificate', [
            'certificate' => $certificate,
            'template'    => $template,
            'fields'      => $this->resolveFields($certificate),
        ])->render();

        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'orientation'      => $orientation,
            'default_font'     => 'xbriyaz',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'tempDir'          => $tmp,
            'margin_top'       => 0,
            'margin_bottom'    => 0,
            'margin_left'      => 0,
            'margin_right'     => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('certificate.pdf', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Resolve the placeholder values for this certificate's recipient.
     *
     * @return array<string,string>
     */
    private function resolveFields(Certificate $certificate): array
    {
        $recipient = $certificate->recipient;
        $school = $certificate->school_id
            ? \App\Models\School::find($certificate->school_id)
            : null;

        $grade = null;
        if ($recipient && $recipient->class_room_id) {
            $grade = optional($recipient->classRoom)->name ?? null;
        }

        return [
            'student_name' => $recipient->name ?? '',
            'school'       => $school->name ?? config('app.name'),
            'grade'        => $grade ?? '',
            'date'         => optional($certificate->issue_date)->format('Y-m-d') ?? now()->format('Y-m-d'),
        ];
    }
}
