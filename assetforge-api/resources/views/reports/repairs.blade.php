<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AssetForge Repair Cost Report</title>
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
    <h1>AssetForge — Repair Cost &amp; Service Report</h1>
    <div class="meta">Tenant: {{ $tenantName }} | Generated: {{ $generatedAt }}</div>
    <div class="summary">
        <span>Total Jobs: {{ $report['summary']['total_jobs'] }}</span>
        <span>Completed: {{ $report['summary']['completed_jobs'] }}</span>
        <span>Total Cost: ${{ $report['summary']['total_cost'] }}</span>
        <span>Average Cost: ${{ $report['summary']['average_cost'] }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Job #</th>
                <th>Asset Tag</th>
                <th>Fault Type</th>
                <th>Center</th>
                <th>Vendor</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>Cost (USD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
                <tr>
                    <td>{{ $row['job_number'] }}</td>
                    <td>{{ $row['asset_tag'] }}</td>
                    <td>{{ $row['fault_type'] }}</td>
                    <td>{{ $row['repair_center'] }}</td>
                    <td>{{ $row['vendor_name'] }}</td>
                    <td>{{ $row['repair_status'] }}</td>
                    <td>{{ $row['start_date'] }}</td>
                    <td>${{ $row['cost'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
