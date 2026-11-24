<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AssetForge Warranty Expiration Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 4px; color: #312e81; }
        .meta { font-size: 9px; color: #64748b; margin-bottom: 12px; }
        .summary { margin-bottom: 12px; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .summary span { margin-right: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; }
        th { background: #e2e8f0; font-size: 9px; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>AssetForge — Warranty Expiration Report</h1>
    <div class="meta">Tenant: {{ $tenantName }} | Generated: {{ $generatedAt }}</div>
    <div class="summary">
        <span>Total Contracts: {{ $report['summary']['total_warranties'] }}</span>
        <span>Active: {{ $report['summary']['active'] }}</span>
        <span>Expiring Soon: {{ $report['summary']['expiring_soon'] }}</span>
        <span>Expired: {{ $report['summary']['expired'] }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Asset Tag</th>
                <th>Serial #</th>
                <th>Provider</th>
                <th>Policy #</th>
                <th>Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Days Left</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
                <tr>
                    <td>{{ $row['asset_tag'] }}</td>
                    <td>{{ $row['serial_number'] }}</td>
                    <td>{{ $row['provider_name'] }}</td>
                    <td>{{ $row['policy_number'] }}</td>
                    <td>{{ $row['warranty_type'] }}</td>
                    <td>{{ $row['start_date'] }}</td>
                    <td>{{ $row['end_date'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['days_remaining'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
