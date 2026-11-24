<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AssetForge Inventory Report</title>
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
    <h1>AssetForge — Inventory Valuation Report</h1>
    <div class="meta">Tenant: {{ $tenantName }} | Generated: {{ $generatedAt }}</div>
    <div class="summary">
        <span>Total Assets: {{ $report['summary']['total_assets'] }}</span>
        <span>Assigned: {{ $report['summary']['assigned_assets'] }}</span>
        <span>Unassigned: {{ $report['summary']['unassigned_assets'] }}</span>
        <span>Total Valuation: ${{ $report['summary']['total_valuation'] }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Asset Tag</th>
                <th>Serial #</th>
                <th>Device</th>
                <th>Category</th>
                <th>Status</th>
                <th>Location</th>
                <th>Assigned To</th>
                <th>Cost (USD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
                <tr>
                    <td>{{ $row['asset_tag'] }}</td>
                    <td>{{ $row['serial_number'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['location'] }}</td>
                    <td>{{ $row['assigned_to'] }}</td>
                    <td>${{ $row['purchase_cost'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
