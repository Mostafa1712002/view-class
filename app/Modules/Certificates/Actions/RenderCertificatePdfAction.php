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

        $html = view('certificates.pdf.certificate', array_merge([
            'certificate' => $certificate,
            'template'    => $template,
            'fields'      => $this->resolveFields($certificate),
        ], $this->resolveDesign($certificate)))->render();

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

        // Spec: the school logo appears as a watermark across the whole sheet.
        // mPDF renders it centered, behind the content, at a faint alpha.
        $watermark = $this->watermarkPath($certificate);
        if ($watermark) {
            $mpdf->showWatermarkImage = true;
            $mpdf->SetWatermarkImage($watermark, 0.08, 'P', 'P');
        }

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

    /**
     * Resolve the design/decoration data for the PDF view: local image paths
     * (mPDF needs a filesystem path, not a URL), the free-text body, and the
     * recipient's published grades for grade-bearing certificate types.
     *
     * @return array<string,mixed>
     */
    private function resolveDesign(Certificate $certificate): array
    {
        return [
            'signer_name'   => $certificate->signer_name,
            'signature_url' => $this->localPath($certificate->signature_path),
            'logo_url'      => $this->localPath($certificate->logo_path),
            'stamp_url'     => $this->localPath($certificate->stamp_path),
            'body_html'     => $certificate->body_html,
            'grades'        => $this->resolveGrades($certificate),
        ];
    }

    /** Watermark image: the school's logo, falling back to the certificate's own logo. */
    private function watermarkPath(Certificate $certificate): ?string
    {
        $school = $certificate->school_id ? \App\Models\School::find($certificate->school_id) : null;

        return $this->localPath($school?->logo) ?? $this->localPath($certificate->logo_path);
    }

    /** Absolute filesystem path for a public-disk file, or null if unset/missing. */
    private function localPath(?string $path): ?string
    {
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        }

        return null;
    }

    /**
     * Published grades for the recipient in the school's current academic year,
     * for 'appreciation' and 'grades' certificate types. Empty array otherwise.
     *
     * @return array<int,array{subject:string,score:string,label:string}>
     */
    private function resolveGrades(Certificate $certificate): array
    {
        if (! in_array($certificate->type, ['appreciation', 'grades'], true)) {
            return [];
        }

        $recipient = $certificate->recipient;
        if (! $recipient) {
            return [];
        }

        $query = \App\Models\Grade::forStudent($recipient->id)
            ->published()
            ->with('subject');

        if ($certificate->school_id) {
            $currentYear = \App\Models\AcademicYear::forSchool($certificate->school_id)
                ->current()
                ->first();
            if ($currentYear) {
                $query->forAcademicYear($currentYear->id);
            }
        }

        return $query->get()->map(function ($grade) {
            $total = $grade->total;

            return [
                'subject' => optional($grade->subject)->display_name ?? (optional($grade->subject)->name ?? ''),
                'score'   => $total !== null ? rtrim(rtrim((string) $total, '0'), '.') : '',
                'label'   => $grade->letter_grade_label,
            ];
        })->all();
    }
}
