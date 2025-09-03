<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Analytics Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #6f42c1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #6f42c1;
            margin: 0;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section h2 {
            color: #6f42c1;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .metrics-row {
            display: table-row;
        }
        .metric-cell {
            display: table-cell;
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
        }
        .metric-number {
            font-size: 24px;
            font-weight: bold;
            color: #6f42c1;
        }
        .metric-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-top: 5px;
        }
        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .performance-table th,
        .performance-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .performance-table th {
            background-color: #6f42c1;
            color: white;
            font-weight: bold;
        }
        .performance-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .trend-item {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .trend-month {
            display: table-cell;
            width: 20%;
            padding: 10px;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .trend-data {
            display: table-cell;
            padding: 10px;
        }
        .trend-stat {
            display: inline-block;
            margin-right: 20px;
        }
        .status-distribution {
            display: table;
            width: 100%;
        }
        .status-item {
            display: table-row;
        }
        .status-label {
            display: table-cell;
            padding: 8px;
            font-weight: bold;
            width: 30%;
        }
        .status-bar {
            display: table-cell;
            padding: 8px;
            width: 50%;
        }
        .status-count {
            display: table-cell;
            padding: 8px;
            text-align: right;
            font-weight: bold;
        }
        .bar {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            border-radius: 10px;
        }
        .bar-assigned { background-color: #ffc107; }
        .bar-in_progress { background-color: #17a2b8; }
        .bar-completed { background-color: #28a745; }
        .bar-incident { background-color: #dc3545; }
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
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Analytics Report</h1>
        <p>Period: {{ $analytics['period']['start'] }} to {{ $analytics['period']['end'] }}</p>
        <p>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="section">
        <h2>Executive Summary</h2>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metric-cell">
                    <div class="metric-number">{{ $analytics['summary']['bail_mobilites'] }}</div>
                    <div class="metric-label">Bail Mobilités</div>
                </div>
                <div class="metric-cell">
                    <div class="metric-number">{{ $analytics['summary']['missions'] }}</div>
                    <div class="metric-label">Missions</div>
                </div>
                <div class="metric-cell">
                    <div class="metric-number">{{ $analytics['summary']['checklists'] }}</div>
                    <div class="metric-label">Checklists</div>
                </div>
                <div class="metric-cell">
                    <div class="metric-number">{{ $analytics['summary']['incidents'] }}</div>
                    <div class="metric-label">Incidents</div>
                </div>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metric-cell">
                    <div class="metric-number">{{ round($additional_metrics['avg_bail_duration'] ?? 0, 1) }}</div>
                    <div class="metric-label">Avg Duration (Days)</div>
                </div>
                <div class="metric-cell">
                    <div class="metric-number">{{ $additional_metrics['mission_completion_rate'] ?? 0 }}%</div>
                    <div class="metric-label">Completion Rate</div>
                </div>
                <div class="metric-cell">
                    <div class="metric-number">{{ $additional_metrics['on_time_completion_rate'] ?? 0 }}</div>
                    <div class="metric-label">On-Time Completions</div>
                </div>
                <div class="metric-cell">
                    <div class="metric-number">{{ round($analytics['incident_analysis']['avg_resolution_time'] ?? 0, 1) }}h</div>
                    <div class="metric-label">Avg Resolution Time</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Status Distribution</h2>
        @php
            $total = array_sum($analytics['status_distribution']);
        @endphp
        <div class="status-distribution">
            @foreach($analytics['status_distribution'] as $status => $count)
            @php
                $percentage = $total > 0 ? ($count / $total) * 100 : 0;
            @endphp
            <div class="status-item">
                <div class="status-label">{{ ucfirst(str_replace('_', ' ', $status)) }}</div>
                <div class="status-bar">
                    <div class="bar">
                        <div class="bar-fill bar-{{ $status }}" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                <div class="status-count">{{ $count }} ({{ round($percentage, 1) }}%)</div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="section page-break">
        <h2>Monthly Trends</h2>
        @foreach($analytics['monthly_trends'] as $trend)
        <div class="trend-item">
            <div class="trend-month">{{ $trend['month'] }}</div>
            <div class="trend-data">
                <div class="trend-stat">
                    <strong>Created:</strong> {{ $trend['created'] }}
                </div>
                <div class="trend-stat">
                    <strong>Completed:</strong> {{ $trend['completed'] }}
                </div>
                <div class="trend-stat">
                    <strong>Incidents:</strong> {{ $trend['incidents'] }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="section">
        <h2>Checker Performance</h2>
        <table class="performance-table">
            <thead>
                <tr>
                    <th>Checker Name</th>
                    <th>Missions Completed</th>
                    <th>Total Missions</th>
                    <th>Success Rate</th>
                    <th>Avg Completion Time (h)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['checker_performance'] as $checker)
                <tr>
                    <td>{{ $checker['name'] }}</td>
                    <td>{{ $checker['missions_completed'] }}</td>
                    <td>{{ $checker['missions_total'] }}</td>
                    <td>{{ $checker['success_rate'] }}%</td>
                    <td>{{ $checker['avg_completion_time'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Incident Analysis</h2>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metric-cell">
                    <div class="metric-number">{{ array_sum($analytics['incident_analysis']['by_type']) }}</div>
                    <div class="metric-label">Total Incidents</div>
                </div>
                <div class="metric-cell">
                    <div class="metric-number">{{ round($analytics['incident_analysis']['avg_resolution_time'] ?? 0, 1) }}</div>
                    <div class="metric-label">Avg Resolution (h)</div>
                </div>
            </div>
        </div>

        <h3>By Type</h3>
        <div class="status-distribution">
            @foreach($analytics['incident_analysis']['by_type'] as $type => $count)
            <div class="status-item">
                <div class="status-label">{{ ucfirst(str_replace('_', ' ', $type)) }}</div>
                <div class="status-bar">
                    <div class="bar">
                        <div class="bar-fill bar-incident" style="width: {{ $count > 0 ? 100 : 0 }}%"></div>
                    </div>
                </div>
                <div class="status-count">{{ $count }}</div>
            </div>
            @endforeach
        </div>

        <h3>By Severity</h3>
        <div class="status-distribution">
            @foreach($analytics['incident_analysis']['by_severity'] as $severity => $count)
            <div class="status-item">
                <div class="status-label">{{ ucfirst($severity) }}</div>
                <div class="status-bar">
                    <div class="bar">
                        <div class="bar-fill bar-incident" style="width: {{ $count > 0 ? 100 : 0 }}%"></div>
                    </div>
                </div>
                <div class="status-count">{{ $count }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="footer">
        <p>This analytics report was generated automatically by the Airbnb Concierge System</p>
    </div>
</body>
</html>