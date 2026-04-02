<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Récépissé de candidature - {{ $candidature->num_dossier }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #1f2937;
            background: #fff;
            padding: 40px;
        }
        .receipt-container {
            max-width: 1000px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        /* En-tête */
        .header {
            background: linear-gradient(135deg, #065f46 0%, #059669 100%);
            color: white;
            padding: 20px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .header p {
            font-size: 11px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo img {
            max-height: 60px;
        }
        /* Titre du document */
        .doc-title {
            text-align: center;
            padding: 20px;
            background: #f0fdf4;
            border-bottom: 2px solid #10b981;
        }
        .doc-title h2 {
            color: #065f46;
            font-size: 18px;
            margin: 0;
        }
        .doc-title .ref {
            font-size: 14px;
            color: #047857;
            margin-top: 5px;
            font-weight: bold;
        }
        /* Contenu */
        .content {
            padding: 25px 30px;
        }
        /* Sections */
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #065f46;
            border-left: 4px solid #10b981;
            padding-left: 10px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 35%;
            padding: 8px 12px 8px 0;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-value {
            display: table-cell;
            width: 65%;
            padding: 8px 0;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-Admis { background: #d1fae5; color: #065f46; }
        .status-Rejeté { background: #fee2e2; color: #991b1b; }
        .status-Traitement { background: #dbeafe; color: #1e40af; }
        /* Diplômes */
        .diplomes-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .diplome-tag {
            background: #e0e7ff;
            color: #3730a3;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
        }
        /* Footer */
        .footer {
            background: #f9fafb;
            padding: 15px 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #d1d5db;
            display: flex;
            justify-content: space-between;
        }
        .signature div {
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #9ca3af;
            margin-top: 30px;
            padding-top: 8px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
        /* Liste des documents */
        .docs-list {
            margin-top: 10px;
        }
        .doc-item {
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .doc-name {
            font-weight: 500;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- En-tête -->
        <div class="header">
            <div class="logo">
                <img src="{{ public_path('Images/DTTIA.jpeg') }}" alt="DTTIA Logo" style="max-height: 50px;">
            </div>
            <h1>DTTIA Recrutement</h1>
            <p>Plateforme officielle de gestion des concours</p>
        </div>

        <!-- Titre du document -->
        <div class="doc-title">
            <h2>RÉCÉPISSÉ DE CANDIDATURE</h2>
            <div class="ref">N° dossier : {{ $candidature->num_dossier }}</div>
        </div>

        <div class="content">
            <!-- Section Informations du dossier -->
            <div class="section">
                <div class="section-title">📋 INFORMATIONS DU DOSSIER</div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">N° Dossier</div>
                        <div class="info-value"><strong>{{ $candidature->num_dossier }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Concours</div>
                        <div class="info-value">{{ $candidature->concour->intitule ?? 'Non défini' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date de soumission</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($candidature->created_at)->format('d/m/Y à H:i') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Statut actuel</div>
                        <div class="info-value">
                            <span class="status-badge status-{{ $candidature->resultat }}">
                                {{ $candidature->resultat }}
                            </span>
                        </div>
                    </div>
                    @if($candidature->motif)
                    <div class="info-row">
                        <div class="info-label">Motif</div>
                        <div class="info-value">{{ $candidature->motif }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Section Informations du candidat -->
            <div class="section">
                <div class="section-title">👤 INFORMATIONS DU CANDIDAT</div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Nom complet</div>
                        <div class="info-value">{{ $user->name }} {{ $user->prenom }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $user->email }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value">{{ $profil->telephone ?? 'Non renseigné' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date de naissance</div>
                        <div class="info-value">{{ $profil->date_naissance ?? 'Non renseignée' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Lieu de naissance</div>
                        <div class="info-value">{{ $profil->lieu_naissance ?? 'Non renseigné' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Région</div>
                        <div class="info-value">{{ $profil->region ?? 'Non renseignée' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Permis de conduire</div>
                        <div class="info-value">{{ $profil->permis ? 'Oui' : 'Non' }}</div>
                    </div>
                </div>
            </div>

            <!-- Section Diplômes -->
            <div class="section">
                <div class="section-title">🎓 DIPLÔMES RENSEIGNÉS</div>
                <div class="diplomes-list">
                    @php
                        $diplomes = ['DEF', 'CAP', 'BT', 'BAC', 'DUT', 'Licence', 'Master', 'Doctorat'];
                        $diplomesPossedes = [];
                        foreach($diplomes as $dip) {
                            if(!empty($profil->$dip)) {
                                $diplomesPossedes[] = $dip;
                            }
                        }
                    @endphp
                    @forelse($diplomesPossedes as $diplome)
                        <span class="diplome-tag">{{ $diplome }}</span>
                    @empty
                        <span class="text-muted">Aucun diplôme renseigné</span>
                    @endforelse
                </div>
            </div>

            <!-- Section Documents fournis - avec les noms des documents uniquement -->
            <div class="section">
                <div class="section-title">📎 DOCUMENTS FOURNIS</div>
                <div class="docs-list">
                    @forelse($fichiers as $fichier)
                    <div class="doc-item">
                        <span class="doc-name">• {{ $fichier->type }}</span>
                    </div>
                    @empty
                        <div class="doc-item">
                            <span class="text-muted">Aucun document fourni</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Signature -->
            <div class="signature">
                <div>
                    <div class="signature-line">Signature du candidat</div>
                </div>
                <div>
                    <div class="signature-line">Cachet de la Direction</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Ce document fait foi de dépôt de candidature. Il vous sera demandé lors des épreuves.</p>
            <p>Généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>