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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
        $admis = $this->candidats->where('resultat', 'Admis')
            ->sortBy(function ($c) {
                return strtolower(($c->profil->nom ?? '') . ' ' . ($c->profil->prenom ?? ''));
            })->values();
        $rejetes = $this->candidats->where('resultat', 'Rejété')
            ->sortBy(function ($c) {
                return strtolower(($c->profil->nom ?? '') . ' ' . ($c->profil->prenom ?? ''));
            })->values();
        $sheets[] = new ResultatSheet($this->resultat, $admis, $this->hasSpecialites, 'Admis');
        $sheets[] = new ResultatSheet($this->resultat, $rejetes, $this->hasSpecialites, 'Rejetés');
        return $sheets;
    }
}

class ResultatSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $resultat;
    protected $candidats;
    protected $hasSpecialites;
    protected $sheetTitle;
    protected $counter = 0;

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
            'N°O',
            'Armée',
            'Localité',
            'Colonne1',
            'Numéro du dossier',
            'NINA ou Récipissé',
            'Prénom',
            'NOM',
            'Date de naissance',
            'Lieu de naissance',
            'Sexe',
            'Prénom du Père',
            'Prénom de la Maman',
            'Nom de la Maman',
            'Diplômes',
        ];

        if ($this->hasSpecialites) {
            $headings[] = 'Spécialité';
        }

        $headings[] = 'Contact';
        $headings[] = 'Résultat';
        $headings[] = 'Motif';

        return $headings;
    }

    public function map($candidat): array
    {
        $this->counter++;

        $row = [
            $this->counter,                                                  // N°O (séquentiel trié par nom/prénom)
            $this->resultat->concour->service->nom ?? 'FAMa',               // Armée (= service.nom)
            $candidat->profil->region ?? 'N/A',                             // Localité (= région)
            $candidat->profil->region ?? 'N/A',                             // Colonne1 (= région aussi)
            $candidat->num_dossier,                                         // Numéro du dossier
            $candidat->profil->nina ?? 'N/A',                              // NINA ou Récipissé
            $candidat->profil->prenom ?? 'N/A',                            // Prénom
            $candidat->profil->nom ?? 'N/A',                               // NOM
            $candidat->profil->date_naissance
                ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y')
                : 'N/A',                                                     // Date de naissance
            $candidat->profil->lieu_naissance ?? 'N/A',                    // Lieu de naissance
            $candidat->profil->sexe ?? 'N/A',                              // Sexe
            $candidat->profil->prenom_pere ?? 'N/A',                       // Prénom du Père
            $candidat->profil->prenom_mere ?? 'N/A',                       // Prénom de la Maman
            $candidat->profil->nom_mere ?? 'N/A',                          // Nom de la Maman
            $this->resultat->concour->diplome_min ?? 'N/A',                // Diplômes (= concours.diplome_min)
        ];

        if ($this->hasSpecialites) {
            $row[] = $candidat->specialite ? $candidat->specialite->nom : 'Non spécifiée';
        }

        $row[] = $candidat->profil->telephone ?? 'N/A';                    // Contact (= telephone)
        $row[] = $candidat->resultat;                                        // Résultat
        $row[] = $candidat->motif ?? '';                                     // Motif

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $headerColor = $this->sheetTitle === 'Admis' ? '10B981' : 'EF4444';

        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $highestRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $highestRow; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':' . $sheet->getHighestColumn() . $i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                ]);
            }
        }

        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $highestRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);

        return [];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
