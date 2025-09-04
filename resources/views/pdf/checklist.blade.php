<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist - {{ $checklist->mission->mission_type === 'checkin' ? 'Entrée' : 'Sortie' }}</title>
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
        .room-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .room-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .item-grid {
            display: table;
            width: 100%;
        }
        .item-row {
            display: table-row;
        }
        .item-name {
            display: table-cell;
            width: 40%;
            padding: 3px 10px 3px 0;
            vertical-align: top;
        }
        .item-condition {
            display: table-cell;
            width: 20%;
            padding: 3px 10px 3px 0;
            vertical-align: top;
        }
        .item-comment {
            display: table-cell;
            width: 40%;
            padding: 3px 0;
            vertical-align: top;
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
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .photo-item {
            text-align: center;
        }
        .photo-item img {
            max-width: 100%;
            max-height: 150px;
            border: 1px solid #ddd;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background-color: #fbbf24; color: #92400e; }
        .status-completed { background-color: #10b981; color: #064e3b; }
        .status-submitted { background-color: #3b82f6; color: #1e40af; }
        .status-validated { background-color: #10b981; color: #064e3b; }
        .status-rejected { background-color: #ef4444; color: #7f1d1d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Checklist {{ $checklist->mission->mission_type === 'checkin' ? 'd\'Entrée' : 'de Sortie' }}</h1>
        <h2>Référence: {{ $checklist->mission->reference ?? 'BM-' . $checklist->mission->id }}</h2>
        <p>Statut: <span class="status-badge status-{{ $checklist->status }}">{{ $checklist->status }}</span></p>
    </div>

    <!-- Mission Information -->
    <div class="section">
        <div class="section-title">Informations de la Mission</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Adresse :</div>
                <div class="info-value">{{ $checklist->mission->address }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de la mission :</div>
                <div class="info-value">{{ $checklist->mission->scheduled_date ? \Carbon\Carbon::parse($checklist->mission->scheduled_date)->format('d/m/Y H:i') : 'Non définie' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Agent assigné :</div>
                <div class="info-value">{{ $checklist->mission->agent ? $checklist->mission->agent->name : 'Non assigné' }}</div>
            </div>
            @if($checklist->mission->bailMobilite)
            <div class="info-row">
                <div class="info-label">Locataire :</div>
                <div class="info-value">{{ $checklist->mission->bailMobilite->tenant_name }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- General Information -->
    @if($checklist->general_info && count($checklist->general_info) > 0)
    <div class="section">
        <div class="section-title">Informations Générales</div>
        <div class="info-grid">
            @foreach($checklist->general_info as $key => $value)
                @if(is_array($value))
                    @foreach($value as $subKey => $subValue)
                        @if($subValue)
                        <div class="info-row">
                            <div class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }} - {{ ucfirst(str_replace('_', ' ', $subKey)) }} :</div>
                            <div class="info-value">{{ $subValue }}</div>
                        </div>
                        @endif
                    @endforeach
                @elseif($value)
                <div class="info-row">
                    <div class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }} :</div>
                    <div class="info-value">{{ $value }}</div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Rooms Information -->
    @if($checklist->rooms && count($checklist->rooms) > 0)
    <div class="section">
        <div class="section-title">Pièces</div>
        @foreach($checklist->rooms as $roomKey => $room)
            <div class="room-section">
                <div class="room-title">{{ ucfirst(str_replace('_', ' ', $roomKey)) }}</div>
                <div class="item-grid">
                    @if(is_array($room))
                        @foreach($room as $itemKey => $itemValue)
                            @if($itemValue && $itemKey !== 'comments')
                            <div class="item-row">
                                <div class="item-name">{{ ucfirst(str_replace('_', ' ', $itemKey)) }}</div>
                                <div class="item-condition">{{ $itemValue }}</div>
                                <div class="item-comment"></div>
                            </div>
                            @endif
                        @endforeach
                        @if(isset($room['comments']) && $room['comments'])
                        <div class="item-row">
                            <div class="item-name">Commentaires</div>
                            <div class="item-condition"></div>
                            <div class="item-comment">{{ $room['comments'] }}</div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Utilities Information -->
    @if($checklist->utilities && count($checklist->utilities) > 0)
    <div class="section">
        <div class="section-title">Utilitaires</div>
        <div class="info-grid">
            @foreach($checklist->utilities as $key => $value)
                @if(is_array($value))
                    @foreach($value as $subKey => $subValue)
                        @if($subValue)
                        <div class="info-row">
                            <div class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }} - {{ ucfirst(str_replace('_', ' ', $subKey)) }} :</div>
                            <div class="info-value">{{ $subValue }}</div>
                        </div>
                        @endif
                    @endforeach
                @elseif($value)
                <div class="info-row">
                    <div class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }} :</div>
                    <div class="info-value">{{ $value }}</div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Checklist Items -->
    @if($checklist->items && $checklist->items->count() > 0)
    <div class="section">
        <div class="section-title">Éléments de Contrôle</div>
        @foreach($checklist->items as $item)
            <div class="room-section">
                <div class="room-title">{{ $item->item_name }} ({{ ucfirst($item->category) }})</div>
                <div class="item-grid">
                    <div class="item-row">
                        <div class="item-name">État</div>
                        <div class="item-condition">{{ $item->condition ? ucfirst($item->condition) : 'Non spécifié' }}</div>
                        <div class="item-comment">{{ $item->comment ?? '' }}</div>
                    </div>
                </div>
                
                @if($item->photos && $item->photos->count() > 0)
                <div class="photo-grid">
                    @foreach($item->photos as $photo)
                    <div class="photo-item">
                        <img src="{{ storage_path('app/public/' . $photo->photo_path) }}" alt="{{ $item->item_name }}">
                        <p style="font-size: 10px; margin-top: 5px;">{{ $photo->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures-section">
        <div class="section-title">Signatures</div>
        
        <div class="signature-container">
            <div class="signature-box">
                <div class="signature-title">Signature du Locataire</div>
                @if($checklist->tenant_signature)
                    <img src="{{ $checklist->tenant_signature }}" alt="Signature Locataire" class="signature-image">
                @else
                    <p style="color: #999; font-style: italic;">Signature non fournie</p>
                @endif
                <div class="signature-details">
                    <div><strong>Date :</strong> {{ $checklist->created_at->format('d/m/Y à H:i') }}</div>
                </div>
            </div>

            <div class="signature-box">
                <div class="signature-title">Signature de l'Agent</div>
                @if($checklist->agent_signature)
                    <img src="{{ $checklist->agent_signature }}" alt="Signature Agent" class="signature-image">
                @else
                    <p style="color: #999; font-style: italic;">Signature non fournie</p>
                @endif
                <div class="signature-details">
                    <div><strong>Date :</strong> {{ $checklist->created_at->format('d/m/Y à H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Validation Information -->
    @if($checklist->status === 'validated' || $checklist->status === 'rejected')
    <div class="section">
        <div class="section-title">Validation</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Statut :</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $checklist->status }}">{{ $checklist->status }}</span>
                </div>
            </div>
            @if($checklist->validated_by)
            <div class="info-row">
                <div class="info-label">Validé par :</div>
                <div class="info-value">{{ $checklist->validatedBy->name ?? 'Utilisateur supprimé' }}</div>
            </div>
            @endif
            @if($checklist->validated_at)
            <div class="info-row">
                <div class="info-label">Date de validation :</div>
                <div class="info-value">{{ $checklist->validated_at->format('d/m/Y à H:i') }}</div>
            </div>
            @endif
            @if($checklist->ops_validation_comments)
            <div class="info-row">
                <div class="info-label">Commentaires :</div>
                <div class="info-value">{{ $checklist->ops_validation_comments }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>Bail Mobilité - Système de gestion des checklists</p>
    </div>
</body>
</html>
