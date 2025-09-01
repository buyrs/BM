<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat de Bail Mobilité - {{ $signature->signature_type === 'entry' ? 'Entrée' : 'Sortie' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: normal;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 30%;
            padding: 5px 10px 5px 0;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
            vertical-align: top;
        }
        .contract-content {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            white-space: pre-wrap;
        }
        .signatures-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-container {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        .signature-box {
            display: table-cell;
            width: 48%;
            padding: 15px;
            border: 1px solid #333;
            text-align: center;
            vertical-align: top;
        }
        .signature-box:first-child {
            margin-right: 4%;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .signature-image {
            max-width: 200px;
            max-height: 80px;
            margin: 10px 0;
        }
        .signature-details {
            margin-top: 15px;
            font-size: 11px;
            color: #666;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CONTRAT DE BAIL MOBILITÉ</h1>
        <h2>{{ $signature->signature_type === 'entry' ? 'Document d\'Entrée' : 'Document de Sortie' }}</h2>
        <p>Référence: BM-{{ $bail_mobilite->id }}-{{ strtoupper($signature->signature_type) }}</p>
    </div>

    <div class="section">
        <div class="section-title">Informations du Logement</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Adresse :</div>
                <div class="info-value">{{ $property_info['address'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de début :</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($property_info['start_date'])->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de fin :</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($property_info['end_date'])->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Informations du Locataire</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom :</div>
                <div class="info-value">{{ $tenant_info['name'] }}</div>
            </div>
            @if($tenant_info['email'])
            <div class="info-row">
                <div class="info-label">Email :</div>
                <div class="info-value">{{ $tenant_info['email'] }}</div>
            </div>
            @endif
            @if($tenant_info['phone'])
            <div class="info-row">
                <div class="info-label">Téléphone :</div>
                <div class="info-value">{{ $tenant_info['phone'] }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Contenu du Contrat</div>
        <div class="contract-content">{{ $contract_template->content }}</div>
    </div>

    @if($bail_mobilite->notes)
    <div class="section">
        <div class="section-title">Notes Spécifiques</div>
        <div class="contract-content">{{ $bail_mobilite->notes }}</div>
    </div>
    @endif

    <div class="signatures-section">
        <div class="section-title">Signatures Électroniques</div>
        
        <div class="signature-container">
            <div class="signature-box">
                <div class="signature-title">Signature de l'Hôte/Propriétaire</div>
                @if($signatures['admin']['signature'])
                    <img src="{{ $signatures['admin']['signature'] }}" alt="Signature Hôte" class="signature-image">
                @endif
                <div class="signature-details">
                    <div><strong>Nom :</strong> {{ $signatures['admin']['name'] }}</div>
                    <div><strong>Date :</strong> {{ $signatures['admin']['signed_at']->format('d/m/Y à H:i') }}</div>
                </div>
            </div>

            <div class="signature-box">
                <div class="signature-title">Signature du Locataire</div>
                @if($signatures['tenant']['signature'])
                    <img src="{{ $signatures['tenant']['signature'] }}" alt="Signature Locataire" class="signature-image">
                @endif
                <div class="signature-details">
                    <div><strong>Nom :</strong> {{ $signatures['tenant']['name'] }}</div>
                    <div><strong>Date :</strong> {{ $signatures['tenant']['signed_at']->format('d/m/Y à H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Document généré électroniquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>Ce document constitue un contrat légalement contraignant entre les parties signataires.</p>
        <p>Référence technique: {{ $signature->id }} | Type: {{ $signature->signature_type }}</p>
    </div>
</body>
</html>