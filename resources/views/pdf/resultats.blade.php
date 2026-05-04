<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Résultats</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; font-size: 9px; word-break: break-word; }
        th { background-color: #f2f2f2; font-size: 9px; }
        h3 { border-bottom: 2px solid #000; padding-bottom: 5px; text-transform: uppercase; }
        .admis-title { color: green; }
        .rejet-title { color: red; }
        .row-feminin { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Liste Officielle des Résultats</h1>
        <h2>{{ $resultat->intitule }}</h2>
        <p>Généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}</p>
    </div>

    <!-- ⭐ Liste des candidats Admis -->
    <h3 class="admis-title">Liste des candidats Admis</h3>
    <table>
        <thead>
            <tr>
                <th>N° Dossier</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Né(e) le</th>
                <th>À</th>
                <th>NINA</th>
                <th>Prénom Père</th>
                <th>Nom Mère</th>
                <th>Prénom Mère</th>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <th>Spécialité</th>
                @endif
                <th>Résultat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($candidats->where('resultat', 'Admis') as $candidat)
            @php
                $sexe = $candidat->profil->sexe ?? '';
                $isFeminin = $sexe === 'Féminin' || $sexe === 'Feminin';
            @endphp
            <tr @if($isFeminin) class="row-feminin" @endif>
                <td>{{ $candidat->num_dossier }}</td>
                <td>{{ $candidat->profil->prenom ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nom ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->date_naissance ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $candidat->profil->lieu_naissance ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nina ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_pere ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nom_mere ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_mere ?? 'N/A' }}</td>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <td>{{ $candidat->specialite ? $candidat->specialite->nom : 'Non spécifiée' }}</td>
                @endif
                <td style="font-weight: bold; color: green;">{{ $candidat->resultat }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ⭐ Liste des candidats Rejetés -->
    <h3 class="rejet-title">Liste des candidats Rejetés</h3>
    <table>
        <thead>
            <tr>
                <th>N° Dossier</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Né(e) le</th>
                <th>À</th>
                <th>NINA</th>
                <th>Prénom Père</th>
                <th>Nom Mère</th>
                <th>Prénom Mère</th>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <th>Spécialité</th>
                @endif
                <th>Résultat</th>
                <th>Motif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($candidats->where('resultat', 'Rejété') as $candidat)
            @php
                $sexe = $candidat->profil->sexe ?? '';
                $isFeminin = $sexe === 'Féminin' || $sexe === 'Feminin';
            @endphp
            <tr @if($isFeminin) class="row-feminin" @endif>
                <td>{{ $candidat->num_dossier }}</td>
                <td>{{ $candidat->profil->prenom ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nom ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->date_naissance ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $candidat->profil->lieu_naissance ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nina ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_pere ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->nom_mere ?? 'N/A' }}</td>
                <td>{{ $candidat->profil->prenom_mere ?? 'N/A' }}</td>
                @if($resultat->concour && $resultat->concour->has_specialites)
                <td>{{ $candidat->specialite ? $candidat->specialite->nom : 'Non spécifiée' }}</td>
                @endif
                <td style="color: red;">{{ $candidat->resultat }}</td>
                <td>{{ $candidat->motif ?? 'Non spécifié' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pied de page -->
    <div style="text-align: center; margin-top: 50px; font-size: 9px; color: #666;">
        <p>Ce document fait foi. Aucune modification ne sera acceptée après publication.</p>
        <p>Document généré automatiquement par FAMa Recrutement</p>
    </div>
</body>
</html>