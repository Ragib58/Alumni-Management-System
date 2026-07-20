<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generic tabular export used for Excel (.xlsx) and CSV output. Fed by
 * ReportService::tableFor() which yields { title, headings, rows }.
 */
class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    /**
     * @param array<int, string>            $headings
     * @param array<int, array<int, mixed>> $rows
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title = 'Report',
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        // Sheet names are capped at 31 chars by the spreadsheet format.
        return mb_substr($this->title, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
