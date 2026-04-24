<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ⭐ Utilisation de WithMultipleSheets pour plusieurs feuilles
class ResultatsExport implements WithMultipleSheets
{
    protected $resultat;
    protected $candidats;
    protected $hasSpecialites;

    public function __construct($resultat, $candidats)
    {
        $this->resultat = $resultat;
        $this->candidats = $candidats;
        $this->hasSpecialites = $resultat->concour && $resultat->concour->has_specialites;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Séparer les candidats par statut
        $admis = $this->candidats->where('resultat', 'Admis');
        $rejetes = $this->candidats->where('resultat', 'Rejété');

        // Feuille des Admis
        $sheets[] = new ResultatSheet($this->resultat, $admis, $this->hasSpecialites, 'Admis');

        // Feuille des Rejetés
        $sheets[] = new ResultatSheet($this->resultat, $rejetes, $this->hasSpecialites, 'Rejetés');

        return $sheets;
    }
}

// ⭐ Classe pour une feuille individuelle
class ResultatSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $resultat;
    protected $candidats;
    protected $hasSpecialites;
    protected $sheetTitle;

    public function __construct($resultat, $candidats, $hasSpecialites, $sheetTitle)
    {
        $this->resultat = $resultat;
        $this->candidats = $candidats;
        $this->hasSpecialites = $hasSpecialites;
        $this->sheetTitle = $sheetTitle;
    }

    public function collection()
    {
        return $this->candidats;
    }

    public function headings(): array
    {
        $headings = [
            'N° Dossier',
            'Prénom',
            'Nom',
            'Date de naissance',
            'Lieu de naissance',
            'Résultat',
        ];

        if ($this->hasSpecialites) {
            array_splice($headings, 5, 0, ['Spécialité']);
        }

        $headings[] = 'Motif';

        return $headings;
    }

    public function map($candidat): array
    {
        $row = [
            $candidat->num_dossier,
            $candidat->profil->prenom ?? 'N/A',
            $candidat->profil->nom ?? 'N/A',
            $candidat->profil->date_naissance ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y') : 'N/A',
            $candidat->profil->lieu_naissance ?? 'N/A',
            $candidat->resultat,
        ];

        if ($this->hasSpecialites) {
            array_splice($row, 5, 0, [$candidat->specialite ? $candidat->specialite->nom : 'Non spécifiée']);
        }

        $row[] = $candidat->motif ?? '';

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        // Couleur selon le type de feuille
        $headerColor = $this->sheetTitle === 'Admis' ? '10B981' : 'EF4444';

        // Style pour l'en-tête
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $headerColor],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Ajuster la largeur des colonnes
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Style pour les lignes paires (alternance)
        $highestRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $highestRow; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':' . $sheet->getHighestColumn() . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                ]);
            }
        }

        // Bordures pour tout le tableau
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
