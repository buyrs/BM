<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mission Report #{{ $mission->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
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
            color: #007bff;
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
        .status.completed { background-color: #d4edda; color: #155724; }
        .status.in_progress { background-color: #d1ecf1; color: #0c5460; }
        .status.assigned { background-color: #fff3cd; color: #856404; }
        .status.pending { background-color: #f8d7da; color: #721c24; }
        .checklist-item {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 10px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Mission Report</h1>
        <p>Mission #{{ $mission->id }} - {{ ucfirst($mission->type) }} Mission</p>
        <p>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="section">
        <h2>Mission Details</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status {{ $mission->status }}">{{ ucfirst($mission->status) }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Type:</div>
                <div class="info-value">{{ ucfirst($mission->type) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tenant:</div>
                <div class="info-value">{{ $mission->tenant_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Address:</div>
                <div class="info-value">{{ $mission->address }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Scheduled:</div>
                <div class="info-value">{{ $mission->scheduled_at?->format('F j, Y \a\t g:i A') ?? 'Not scheduled' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Completed:</div>
                <div class="info-value">{{ $mission->completed_at?->format('F j, Y \a\t g:i A') ?? 'Not completed' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Assigned Checker:</div>
                <div class="info-value">{{ $mission->agent?->user?->name ?? 'Not assigned' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Ops User:</div>
                <div class="info-value">{{ $mission->bailMobilite?->opsUser?->name ?? 'Not assigned' }}</div>
            </div>
        </div>
    </div>

    @if($mission->notes)
    <div class="section">
        <h2>Notes</h2>
        <p>{{ $mission->notes }}</p>
    </div>
    @endif

    @if($mission->checklist)
    <div class="section">
        <h2>Checklist</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status {{ $mission->checklist->status }}">{{ ucfirst($mission->checklist->status) }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Items:</div>
                <div class="info-value">{{ $mission->checklist->items->count() }} items</div>
            </div>
            <div class="info-row">
                <div class="info-label">Photos:</div>
                <div class="info-value">{{ $mission->checklist->photos->count() }} photos</div>
            </div>
            @if($mission->checklist->tenant_comment)
            <div class="info-row">
                <div class="info-label">Tenant Comment:</div>
                <div class="info-value">{{ $mission->checklist->tenant_comment }}</div>
            </div>
            @endif
            @if($mission->checklist->ops_validation_comments)
            <div class="info-row">
                <div class="info-label">Ops Comments:</div>
                <div class="info-value">{{ $mission->checklist->ops_validation_comments }}</div>
            </div>
            @endif
        </div>

        @if($mission->checklist->items->count() > 0)
        <h3>Checklist Items</h3>
        @foreach($mission->checklist->items as $item)
        <div class="checklist-item">
            <strong>{{ $item->name }}</strong>
            <br>
            Condition: <span class="status {{ $item->condition }}">{{ ucfirst($item->condition) }}</span>
            @if($item->notes)
            <br>
            Notes: {{ $item->notes }}
            @endif
        </div>
        @endforeach
        @endif
    </div>
    @endif

    <div class="footer">
        <p>This report was generated automatically by the Airbnb Concierge System</p>
    </div>
</body>
</html>