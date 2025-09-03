<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Checklist Report #{{ $checklist->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #28a745;
            margin: 0;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 30%;
            padding: 5px 10px 5px 0;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #28a745;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .status {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .status.validated { background-color: #d4edda; color: #155724; }
        .status.submitted { background-color: #d1ecf1; color: #0c5460; }
        .status.pending { background-color: #fff3cd; color: #856404; }
        .status.rejected { background-color: #f8d7da; color: #721c24; }
        .status.good { background-color: #d4edda; color: #155724; }
        .status.fair { background-color: #fff3cd; color: #856404; }
        .status.damaged { background-color: #f8d7da; color: #721c24; }
        .checklist-item {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .item-header {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .item-condition {
            margin-bottom: 5px;
        }
        .item-notes {
            font-style: italic;
            color: #666;
            margin-top: 5px;
        }
        .photos-section {
            margin-top: 15px;
        }
        .photo-list {
            list-style: none;
            padding: 0;
        }
        .photo-list li {
            margin-bottom: 5px;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .summary-stats {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: table;
            width: 100%;
        }
        .stats-row {
            display: table-row;
        }
        .stats-cell {
            display: table-cell;
            text-align: center;
            padding: 10px;
            border-right: 1px solid #ddd;
        }
        .stats-cell:last-child {
            border-right: none;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .stats-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Checklist Report</h1>
        <p>Checklist #{{ $checklist->id }} - {{ ucfirst($checklist->mission?->type ?? 'Unknown') }} Mission</p>
        <p>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="section">
        <h2>Checklist Overview</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status {{ $checklist->status }}">{{ ucfirst($checklist->status) }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Mission Type:</div>
                <div class="info-value">{{ ucfirst($checklist->mission?->type ?? 'Unknown') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Property:</div>
                <div class="info-value">{{ $checklist->mission?->address ?? 'Not specified' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Checker:</div>
                <div class="info-value">{{ $checklist->mission?->agent?->user?->name ?? 'Not assigned' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Created:</div>
                <div class="info-value">{{ $checklist->created_at->format('F j, Y \a\t g:i A') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Validated:</div>
                <div class="info-value">
                    {{ $checklist->validated_at?->format('F j, Y \a\t g:i A') ?? 'Not validated' }}
                    @if($checklist->validatedBy)
                    by {{ $checklist->validatedBy->name }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $totalItems = $checklist->items->count();
        $goodItems = $checklist->items->where('condition', 'good')->count();
        $fairItems = $checklist->items->where('condition', 'fair')->count();
        $damagedItems = $checklist->items->where('condition', 'damaged')->count();
        $totalPhotos = $checklist->photos->count();
    @endphp

    <div class="section">
        <h2>Summary Statistics</h2>
        <div class="summary-stats">
            <div class="stats-grid">
                <div class="stats-row">
                    <div class="stats-cell">
                        <div class="stats-number">{{ $totalItems }}</div>
                        <div class="stats-label">Total Items</div>
                    </div>
                    <div class="stats-cell">
                        <div class="stats-number">{{ $goodItems }}</div>
                        <div class="stats-label">Good Condition</div>
                    </div>
                    <div class="stats-cell">
                        <div class="stats-number">{{ $fairItems }}</div>
                        <div class="stats-label">Fair Condition</div>
                    </div>
                    <div class="stats-cell">
                        <div class="stats-number">{{ $damagedItems }}</div>
                        <div class="stats-label">Damaged</div>
                    </div>
                    <div class="stats-cell">
                        <div class="stats-number">{{ $totalPhotos }}</div>
                        <div class="stats-label">Photos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($checklist->tenant_comment)
    <div class="section">
        <h2>Tenant Comments</h2>
        <p style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;">
            {{ $checklist->tenant_comment }}
        </p>
    </div>
    @endif

    @if($checklist->ops_validation_comments)
    <div class="section">
        <h2>Ops Validation Comments</h2>
        <p style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
            {{ $checklist->ops_validation_comments }}
        </p>
    </div>
    @endif

    @if($checklist->items->count() > 0)
    <div class="section">
        <h2>Checklist Items ({{ $checklist->items->count() }})</h2>
        @foreach($checklist->items as $item)
        <div class="checklist-item">
            <div class="item-header">{{ $item->name }}</div>
            <div class="item-condition">
                Condition: <span class="status {{ $item->condition }}">{{ ucfirst($item->condition) }}</span>
            </div>
            @if($item->notes)
            <div class="item-notes">Notes: {{ $item->notes }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    @if($checklist->photos->count() > 0)
    <div class="section">
        <h2>Photos ({{ $checklist->photos->count() }})</h2>
        <ul class="photo-list">
            @foreach($checklist->photos as $photo)
            <li>
                <strong>{{ $photo->filename ?? 'Photo ' . $loop->iteration }}</strong>
                @if($photo->description)
                - {{ $photo->description }}
                @endif
                <br>
                <small>Uploaded: {{ $photo->created_at->format('F j, Y \a\t g:i A') }}</small>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="footer">
        <p>This checklist report was generated automatically by the Airbnb Concierge System</p>
    </div>
</body>
</html>