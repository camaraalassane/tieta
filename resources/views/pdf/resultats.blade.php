<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Résultats</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 6px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { font-size: 12px; margin: 0; }
        .header h2 { font-size: 10px; margin: 3px 0; }
        .header p { font-size: 7px; color: #666; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 1px 2px; text-align: left; font-size: 5.5px; word-break: break-word; }
        th { background-color: #f2f2f2; font-size: 5.5px; font-weight: bold; }
        h3 { border-bottom: 2px solid #000; padding-bottom: 2px; text-transform: uppercase; font-size: 9px; }
        .admis-title { color: green; }
        .rejet-title { color: red; }
        .row-feminin { font-weight: bold; }
        .service-info { text-align: center; font-size: 7px; margin-bottom: 8px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MINISTÈRE DE LA DÉFENSE ET DES ANCIENS COMBATTANTS</h1>
        <h2>{{ $resultat->intitule }}</h2>
        <p>Généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}</p>
        <div class="service-info">
            Service : {{ $resultat->concour->service->nom ?? 'FAMa' }} | 
            Diplôme requis : {{ $resultat->concour->diplome_min ?? 'Non spécifié' }}
        </div>
    </div>

    <!-- ⭐ Liste des candidats Admis -->
    <h3 class="admis-title">Liste des candidats Admis</h3>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>N° Dossier</th>
                <th>Prénom</th>
                <th>NOM</th>
                <th>Né(e) le</th>
                <th>À</th>
                <th>NINA</th>
                <th>Sexe</th>
                <th>Prénom Père</th>
                <th>Prénom Maman</th>
                <th>Nom Maman</th>
                <th>Diplôme</th>
                <th>Région</th>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <th>Spécialité</th>
                @endif
                <th>Contact</th>
                <th>Résultat</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 0; @endphp
            @foreach($candidats->where('resultat', 'Admis')->sortBy(function($c) { return strtolower(($c->profil->nom ?? '') . ' ' . ($c->profil->prenom ?? '')); }) as $candidat)
            @php
                $counter++;
                $sexe = $candidat->profil->sexe ?? '';
                $isFeminin = $sexe === 'Féminin' || $sexe === 'Feminin';
            @endphp
            <tr @if($isFeminin) class="row-feminin" @endif>
                <td>{{ $counter }}</td>
                <td>{{ $candidat->num_dossier }}</td>
                <td>{{ $candidat->profil->prenom ?? 'N/A' }}</td>
                <td>{{ strtoupper($candidat->profil->nom ?? 'N/A') }}</td>
                <td>{{ $candidat->profil->date_naissance ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $candidat->profil->lieu_naissance ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nina ?? 'N/A' }}</td>
                <td>{{ $sexe ? substr($sexe, 0, 1) : 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_pere ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_mere ?? 'N/A' }}</td>
                <td>{{ strtoupper($candidat->profil->nom_mere ?? 'N/A') }}</td>
                <td>{{ $resultat->concour->diplome_min ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->region ?? 'N/A' }}</td>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <td>{{ $candidat->specialite ? $candidat->specialite->nom : '-' }}</td>
                @endif
                <td>{{ $candidat->profil->telephone ?? 'N/A' }}</td>
                <td style="font-weight: bold; color: green;">Admis</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ⭐ Liste des candidats Rejetés -->
    <h3 class="rejet-title">Liste des candidats Rejetés</h3>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>N° Dossier</th>
                <th>Prénom</th>
                <th>NOM</th>
                <th>Né(e) le</th>
                <th>À</th>
                <th>NINA</th>
                <th>Sexe</th>
                <th>Prénom Père</th>
                <th>Prénom Maman</th>
                <th>Nom Maman</th>
                <th>Diplôme</th>
                <th>Région</th>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <th>Spécialité</th>
                @endif
                <th>Contact</th>
                <th>Résultat</th>
                <th>Motif</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 0; @endphp
            @foreach($candidats->where('resultat', 'Rejété')->sortBy(function($c) { return strtolower(($c->profil->nom ?? '') . ' ' . ($c->profil->prenom ?? '')); }) as $candidat)
            @php
                $counter++;
                $sexe = $candidat->profil->sexe ?? '';
                $isFeminin = $sexe === 'Féminin' || $sexe === 'Feminin';
            @endphp
            <tr @if($isFeminin) class="row-feminin" @endif>
                <td>{{ $counter }}</td>
                <td>{{ $candidat->num_dossier }}</td>
                <td>{{ $candidat->profil->prenom ?? 'N/A' }}</td>
                <td>{{ strtoupper($candidat->profil->nom ?? 'N/A') }}</td>
                <td>{{ $candidat->profil->date_naissance ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $candidat->profil->lieu_naissance ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nina ?? 'N/A' }}</td>
                <td>{{ $sexe ? substr($sexe, 0, 1) : 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_pere ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_mere ?? 'N/A' }}</td>
                <td>{{ strtoupper($candidat->profil->nom_mere ?? 'N/A') }}</td>
                <td>{{ $resultat->concour->diplome_min ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->region ?? 'N/A' }}</td>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <td>{{ $candidat->specialite ? $candidat->specialite->nom : '-' }}</td>
                @endif
                <td>{{ $candidat->profil->telephone ?? 'N/A' }}</td>
                <td style="color: red;">Rejeté</td>
                <td>{{ $candidat->motif ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 50px; font-size: 9px; color: #666;">
        <p>Ce document fait foi. Aucune modification ne sera acceptée après publication.</p>
        <p>Document généré automatiquement par FAMa Recrutement</p>
    </div>
</body>
</html>