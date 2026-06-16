<?php

namespace App\Modules\Attendance\Controllers\Concerns;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Trello #273 — shared export primitives for Sprint-10 report/list screens.
 *
 * - mPDF (Arabic RTL, XB Riyaz) reusing partials.pdf.header/styles + page numbers
 * - Excel (.xlsx) with Arabic headers (phpoffice/phpspreadsheet, already a dep)
 * - CSV with a UTF-8 BOM so Excel opens Arabic correctly
 *
 * Mirrors the canonical patterns already in App\Http\Controllers\Admin\
 * ExportController (mpdfResponse + downloadCsv) and SmsReportsController
 * (xlsx StreamedResponse) — does NOT introduce a new export engine.
 */
trait ExportsReports
{
    /**
     * Render a Blade table view to a branded RTL PDF via mPDF.
     */
    protected function exportPdf(string $viewName, array $data, string $filename, string $orientation = 'P'): Response
    {
        $html = view($viewName, $data)->render();

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
            'margin_top'       => 15,
            'margin_bottom'    => 15,
            'margin_left'      => 12,
            'margin_right'     => 12,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:8px;color:#94a3b8;font-family:dejavusans;">'
            . 'صفحة {PAGENO} من {nb}'
            . '</div>'
        );
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * Stream an .xlsx with Arabic headers.
     *
     * @param  array<int, string>            $headers
     * @param  iterable<int, array<int,mixed>>  $rows
     */
    protected function exportExcel(array $headers, iterable $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }

        $r = 2;
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $v) {
                $sheet->setCellValueExplicit(
                    [$i + 1, $r],
                    (string) $v,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
            $r++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Build a CSV (UTF-8 BOM) so Excel renders Arabic without mojibake.
     *
     * @param  array<int, string>            $headers
     * @param  iterable<int, array<int,mixed>>  $rows
     */
    protected function exportCsv(array $headers, iterable $rows, string $filename): Response
    {
        $out = "\xEF\xBB\xBF";
        $out .= implode(',', array_map(fn ($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";
        foreach ($rows as $row) {
            $out .= implode(',', array_map(fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', array_values($row))) . "\n";
        }

        return response($out, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
