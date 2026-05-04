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
            margin: 0;
            padding: 0;
        }
        .receipt-container {
            max-width: 1000px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .header {
            padding: 15px 20px;
            background: white;
            border-bottom: 2px solid #10b981;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            vertical-align: middle;
            text-align: center;
        }
        .logo-column {
            width: 80px;
        }
        .logo-column img {
            max-height: 75px;
            max-width: 75px;
            height: auto;
            width: auto;
        }
        .header-text {
            text-align: center;
            line-height: 1.3;
        }

        .republic {
            font-size: 11px;
            font-weight: bold;
            color: #065f46;
            text-transform: uppercase;
        }
        .motto {
            font-size: 9px;
            color: #4b5563;
        }
        .separator {
            font-size: 9px;
            color: #9ca3af;
            margin: 2px 0;
        }
        .ministere {
            font-size: 9px;
            font-weight: bold;
            color: #1f2937;
        }
        .etat-major {
            font-size: 8px;
            color: #4b5563;
        }
        .service-description {
            font-size: 9px;
            font-weight: bold;
            color: #065f46;
            margin-top: 2px;
        }

        .doc-title {
            text-align: center;
            padding: 12px;
            background: #f0fdf4;
            border-bottom: 2px solid #10b981;
        }
        .doc-title h2 {
            color: #065f46;
            font-size: 16px;
            margin: 0;
        }
        .doc-title .ref {
            font-size: 12px;
            color: #047857;
            margin-top: 3px;
            font-weight: bold;
        }

        .content {
            padding: 20px 25px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #065f46;
            border-left: 4px solid #10b981;
            padding-left: 8px;
            margin-bottom: 12px;
        }

        .candidate-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .candidate-table td {
            vertical-align: top;
        }
        .photo-cell {
            width: 140px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-label {
            width: 35%;
            padding: 6px 10px 6px 0;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }
        .info-value {
            padding: 6px 0;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }

        .photo-placeholder {
            width: 120px;
            height: 150px;
            border: 2px solid #10b981;
            border-radius: 8px;
            background: #f9fafb;
            overflow: hidden;
            text-align: center;
        }
        .photo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-caption {
            font-size: 9px;
            color: #6b7280;
            margin-top: 5px;
            text-align: center;
            width: 120px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-Admis { background: #d1fae5; color: #065f46; }
        .status-Rejeté { background: #fee2e2; color: #991b1b; }
        .status-Rejété { background: #fee2e2; color: #991b1b; }
        .status-Traitement { background: #dbeafe; color: #1e40af; }

        .diplome-tag {
            background: #e0e7ff;
            color: #3730a3;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 500;
            margin-right: 5px;
            display: inline-block;
            margin-bottom: 5px;
        }

        .footer {
            background: #f9fafb;
            padding: 12px 25px;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }

        .doc-item {
            padding: 5px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- En-tête avec logos -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-column">
                        <img src="{{ public_path('Images/Fama.png') }}" alt="FAMa Logo">
                    </td>
                    <td>
                        <div class="header-text">
                            <div class="republic">RÉPUBLIQUE DU MALI</div>
                            <div class="motto">Un Peuple - Un But - Une Foi</div>
                            <div class="separator">-----------------</div>
                            <div class="ministere">MINISTÈRE DE LA DÉFENSE ET DES ANCIENS COMBATTANTS</div>
                            <div class="etat-major">ÉTAT-MAJOR GÉNÉRAL DES ARMÉES</div>
                            <div class="service-description">
                                {{ $service->description ?? $candidature->concour->organisateur }}
                            </div>
                        </div>
                    </td>
                    <td class="logo-column">
                        @if($serviceLogoPath)
                            <img src="{{ $serviceLogoPath }}" alt="{{ $service->nom ?? 'Service' }} Logo">
                        @else
                            <img src="{{ public_path('Images/Fama.png') }}" alt="FAMa Logo">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="doc-title">
            <h2>RÉCÉPISSÉ DE CANDIDATURE</h2>
            <div class="ref">N° dossier : {{ $candidature->num_dossier }}</div>
        </div>

        <div class="content">
            <table class="candidate-table">
                <tr>
                    <td class="photo-cell">
                        <div class="photo-placeholder">
                            @php
                                $photoPath = $profil->photo_identite ?? null;
                                $fullPhotoPath = $photoPath ? storage_path('app/public/' . $photoPath) : null;
                            @endphp
                            @if($photoPath && $fullPhotoPath && file_exists($fullPhotoPath))
                                <img src="{{ $fullPhotoPath }}" alt="Photo d'identité">
                            @else
                                <div style="padding-top: 50px; color: #9ca3af;">
                                    <div style="font-size: 20px;">📷</div>
                                    <div style="font-size: 8px;">Photo non disponible</div>
                                </div>
                            @endif
                        </div>
                        <div class="photo-caption">Photo d'identité</div>
                    </td>
                    <td>
                        <div class="section-title">👤 INFORMATIONS DU CANDIDAT</div>
                        <table class="info-grid">
                            <tr>
                                <td class="info-label">Nom complet</td>
                                <td class="info-value">{{ $user->name }} {{ $user->prenom }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Email</td>
                                <td class="info-value">{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Téléphone</td>
                                <td class="info-value">{{ $profil->telephone ?? 'Non renseigné' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Date de naissance</td>
                                <td class="info-value">{{ $profil->date_naissance ?? 'Non renseignée' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Lieu de naissance</td>
                                <td class="info-value">{{ $profil->lieu_naissance ?? 'Non renseigné' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Région</td>
                                <td class="info-value">{{ $profil->region ?? 'Non renseignée' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="section">
                <div class="section-title">📋 INFORMATIONS DU DOSSIER</div>
                <table class="info-grid">
                    <tr>
                        <td class="info-label">N° Dossier</td>
                        <td class="info-value"><strong>{{ $candidature->num_dossier }}</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label">Concours</td>
                        <td class="info-value">{{ $candidature->concour->intitule ?? 'Non défini' }}</td>
                    </tr>
                    @if($service)
                    <tr>
                        <td class="info-label">Service</td>
                        <td class="info-value">{{ $service->nom }}</td>
                    </tr>
                    @endif
                    @if($candidature->concour && $candidature->concour->has_specialites)
                    <tr>
                        <td class="info-label">Spécialité</td>
                        <td class="info-value">{{ $candidature->specialite ? $candidature->specialite->nom : 'Non spécifiée' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="info-label">Date de soumission</td>
                        <td class="info-value">{{ \Carbon\Carbon::parse($candidature->created_at)->format('d/m/Y à H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Statut actuel</td>
                        <td class="info-value">
                            <span class="status-badge status-{{ $candidature->resultat }}">
                                {{ $candidature->resultat }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">🎓 DIPLÔMES RENSEIGNÉS</div>
                <div style="margin-top: 5px;">
                    @php
                        $diplomes = ['DEF', 'CAP', 'BT', 'BAC', 'DUT', 'Licence', 'Master', 'Doctorat'];
                        $count = 0;
                    @endphp
                    @foreach($diplomes as $dip)
                        @if(!empty($profil->$dip))
                            <span class="diplome-tag">{{ $dip }}</span>
                            @php $count++; @endphp
                        @endif
                    @endforeach
                    @if($count == 0)
                        <span style="color: #9ca3af;">Aucun diplôme renseigné</span>
                    @endif
                </div>
            </div>

      <div class="section">
    <div class="section-title">📎 DOCUMENTS FOURNIS</div>
    @php
        try {
            $fichiersList = collect($fichiers)->all();
        } catch (\Exception $e) {
            $fichiersList = [];
        }
    @endphp
    
    @if(!empty($fichiersList))
        @foreach($fichiersList as $fichier)
            @php
                $type = '';
                if (is_object($fichier)) {
                    $type = $fichier->type ?? 'Document';
                } elseif (is_array($fichier)) {
                    $type = $fichier['type'] ?? 'Document';
                }
            @endphp
            <div class="doc-item">• {{ $type }}</div>
        @endforeach
    @else
        <div class="doc-item" style="color: #9ca3af;">Aucun document fourni</div>
    @endif
</div>

        </div>

        <div class="footer">
            <p>Ce document fait foi de dépôt de candidature. Il vous sera demandé lors des épreuves.</p>
            <p>Généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>