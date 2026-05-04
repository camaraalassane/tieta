<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class NinaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $ninas;
    protected $concour;

    public function __construct($ninas, $concour)
    {
        $this->ninas = $ninas;
        $this->concour = $concour;
    }

    public function collection()
    {
        return $this->ninas;
    }

    public function headings(): array
    {
        return [
            'Prénom',
            'Nom',
            'Date de naissance',
            'Lieu de naissance',
            'NINA',
            'Observation',
        ];
    }

    public function map($row): array
    {
        return [
            $row['prenom'] ?? '',
            $row['nom'] ?? '',
            $row['date_naissance'] ?? '',
            $row['lieu_naissance'] ?? '',
            $row['nina'] ?? '',
            $row['observation'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style pour l'en-tête (A1:F1)
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Hauteur de l'en-tête
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(20); // Prénom
        $sheet->getColumnDimension('B')->setWidth(20); // Nom
        $sheet->getColumnDimension('C')->setWidth(18); // Date de naissance
        $sheet->getColumnDimension('D')->setWidth(25); // Lieu de naissance
        $sheet->getColumnDimension('E')->setWidth(25); // NINA
        $sheet->getColumnDimension('F')->setWidth(40); // Observation

        // Bordures pour tout le tableau
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:F' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Alignement
        $sheet->getStyle('A2:F' . $highestRow)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Lignes paires en gris
        for ($i = 2; $i <= $highestRow; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':F' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                ]);
            }
        }

        return [];
    }

    public function title(): string
    {
        return 'NINA - ' . ($this->concour->intitule ?? 'Concours');
    }
}
