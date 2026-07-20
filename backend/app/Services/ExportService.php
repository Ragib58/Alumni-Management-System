<?php

namespace App\Services;

use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns a report table ({ title, headings, rows }) into a downloadable file in
 * Excel, CSV or PDF format.
 */
class ExportService
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    /**
     * @param string $type   event | financial | alumni
     * @param string $format excel | csv | pdf
     */
    public function export(string $type, string $format, array $filters = []): Response|BinaryFileResponse
    {
        $table = $this->reports->tableFor($type, $filters);
        $filename = Str::slug($table['title']).'-'.now()->format('Ymd_His');

        return match ($format) {
            'csv'   => $this->csv($table, $filename),
            'pdf'   => $this->pdf($table, $filename),
            default => $this->excel($table, $filename),
        };
    }

    private function excel(array $table, string $filename): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($table['headings'], $table['rows'], $table['title']),
            $filename.'.xlsx',
            ExcelWriter::XLSX
        );
    }

    private function csv(array $table, string $filename): BinaryFileResponse
    {
        return Excel::download(
            new ReportExport($table['headings'], $table['rows'], $table['title']),
            $filename.'.csv',
            ExcelWriter::CSV
        );
    }

    private function pdf(array $table, string $filename): Response
    {
        $pdf = Pdf::loadView('reports.table', [
            'title'    => $table['title'],
            'headings' => $table['headings'],
            'rows'     => $table['rows'],
            'generatedAt' => now()->format('d M Y, h:i A'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }
}
