<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Historique des candidatures - {{ $concour->intitule }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #10b981;
            padding-bottom: 10px;
        }
        .header h1 { color: #065f46; margin: 0; font-size: 16px; }
        .header h2 { color: #047857; margin: 3px 0; font-size: 12px; }
        .header p { color: #6b7280; margin: 3px 0; font-size: 8px; }
        .summary { margin-bottom: 15px; padding: 8px; background: #f3f4f6; border-radius: 5px; }
        .summary table { width: 100%; }
        .summary td { padding: 3px; }
        .stats { margin-bottom: 15px; text-align: center; }
        .stats span { display: inline-block; padding: 3px 8px; margin: 0 3px; border-radius: 5px; }
        .stat-admis { background: #d1fae5; color: #065f46; }
        .stat-rejetes { background: #fee2e2; color: #991b1b; }
        .stat-traitement { background: #dbeafe; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px; text-align: left; }
        th { background: #f9fafb; font-weight: bold; color: #374151; }
        .badge-admis { color: #065f46; font-weight: bold; }
        .badge-rejete { color: #991b1b; font-weight: bold; }
        .badge-traitement { color: #1e40af; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 7px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Historique des candidatures</h1>
        <h2>{{ $concour->intitule }}</h2>
        <p>Généré le : {{ $generated_at }}</p>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td width="25%"><strong>Concours</strong></td>
                <td>{{ $concour->intitule }}</td>
                <td width="25%"><strong>Date limite</strong></td>
                <td>{{ $concour->date_limite ? \Carbon\Carbon::parse($concour->date_limite)->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td><strong>Statut</strong></td>
                <td>{{ $concour->statut }}</td>
                <td><strong>Total candidatures</strong></td>
                <td>{{ $total }}</td>
            </tr>
        </table>
    </div>

    <div class="stats">
        <span class="stat-admis">✅ Admis : {{ $admis_count }}</span>
        <span class="stat-rejetes">❌ Rejetés : {{ $rejetes_count }}</span>
        <span class="stat-traitement">⏳ En traitement : {{ $traitement_count }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Dossier</th>
                <th>Candidat</th>
                <th>Email</th>
                <th>NINA</th>
                <th>Prénom Père</th>
                <th>Nom Mère</th>
                <th>Prénom Mère</th>
                <th>Région</th>
                @if(isset($has_specialites) && $has_specialites)
                <th>Spécialité</th>
                @endif
                <th>Date dépôt</th>
                <th>Date modification</th>
                <th>Résultat</th>
                <th>Motif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($candidatures as $candidature)
            <tr>
                <td>{{ $candidature['num_dossier'] }}</td>
                <td>{{ $candidature['nom_complet'] }}</td>
                <td>{{ $candidature['email'] }}</td>
                <td>{{ $candidature['nina'] ?? '-' }}</td>
                <td>{{ $candidature['prenom_pere'] ?? '-' }}</td>
                <td>{{ $candidature['nom_mere'] ?? '-' }}</td>
                <td>{{ $candidature['prenom_mere'] ?? '-' }}</td>
                <td>{{ $candidature['region'] ?? '-' }}</td>
                @if(isset($has_specialites) && $has_specialites)
                <td>{{ $candidature['specialite'] ?? '-' }}</td>
                @endif
                <td>{{ $candidature['created_at'] }}</td>
                <td>{{ $candidature['updated_at'] }}</td>
                <td>
                    @if($candidature['resultat'] === 'Admis')
                        <span class="badge-admis">Admis</span>
                    @elseif($candidature['resultat'] === 'Rejété')
                        <span class="badge-rejete">Rejeté</span>
                    @else
                        <span class="badge-traitement">En traitement</span>
                    @endif
                </td>
                <td>{{ $candidature['motif'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="13" style="text-align: center;">Aucune candidature trouvée</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré automatiquement par FAMa Recrutement</p>
    </div>
</body>
</html>