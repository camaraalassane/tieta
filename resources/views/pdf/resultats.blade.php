<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Résultats</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        h3 { border-bottom: 2px solid #000; padding-bottom: 5px; text-transform: uppercase; }
        .admis-title { color: green; }
        .rejet-title { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Liste Officielle des Résultats</h1>
        <h2>{{ $resultat->intitule }}</h2>
    </div>

    <h3 class="admis-title">Liste des candidats Admis</h3>
    <table>
        <thead>
            <tr>
                <th>N° Dossier</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Né(e) le</th>
                <th>À</th>
                <th>Résultat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($candidats->where('resultat', 'Admis') as $candidat)
            <tr>
                <td>{{ $candidat->num_dossier }}</td>
                <td>{{ $candidat->profil->prenom }}</td>
                <td>{{ $candidat->profil->nom }}</td>
                <td>{{ $candidat->profil->date_naissance ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $candidat->profil->lieu_naissance ?? 'N/A' }}</td>
                <td style="font-weight: bold; color: green;">{{ $candidat->resultat }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="rejet-title">Liste de Rejet</h3>
    <table>
        <thead>
            <tr>
                <th>N° Dossier</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Né(e) le</th>
                <th>À</th>
                <th>Résultat</th>
                <th>Motif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($candidats->where('resultat', 'Rejété') as $candidat)
            <tr>
                <td>{{ $candidat->num_dossier }}</td>
                <td>{{ $candidat->profil->prenom }}</td>
                <td>{{ $candidat->profil->nom }}</td>
                <td>{{ $candidat->profil->date_naissance ? \Carbon\Carbon::parse($candidat->profil->date_naissance)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $candidat->profil->lieu_naissance ?? 'N/A' }}</td>
                <td style="color: red;">{{ $candidat->resultat }}</td>
                <td>{{ $candidat->motif ?? 'Non spécifié' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>