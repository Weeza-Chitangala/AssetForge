import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { type ColumnDef } from '@tanstack/react-table';
import { FileBarChart2, Download, RotateCcw, Filter } from 'lucide-react';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
} from 'recharts';
import api, { downloadBlob } from '../services/api';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { DataTable } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { formatCurrency } from '../lib/utils';
import type { Category, Location } from '../types';

type ReportTab = 'inventory' | 'warranties' | 'repairs';

interface ReportPayload {
  summary: Record<string, string | number>;
  breakdown: Array<{ name: string; count: number; valuation?: string; cost?: string }>;
  rows: Array<Record<string, string | number | null>>;
}

export const ReportsPage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ReportTab>('inventory');
  const [report, setReport] = useState<ReportPayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState<string | null>(null);

  const [categories, setCategories] = useState<Category[]>([]);
  const [locations, setLocations] = useState<Location[]>([]);

  const [filters, setFilters] = useState({
    date_from: '',
    date_to: '',
    category_id: '',
    location_id: '',
    status: '',
    repair_status: '',
  });

  useEffect(() => {
    Promise.allSettled([api.get('/categories'), api.get('/locations')]).then(([c, l]) => {
      if (c.status === 'fulfilled') setCategories(c.value.data.data || []);
      if (l.status === 'fulfilled') setLocations(l.value.data.data || []);
    });
  }, []);

  const fetchReport = useCallback(async () => {
    setLoading(true);
    try {
      const params: Record<string, string> = {};
      if (filters.date_from) params.date_from = filters.date_from;
      if (filters.date_to) params.date_to = filters.date_to;
      if (filters.category_id) params.category_id = filters.category_id;
      if (filters.location_id) params.location_id = filters.location_id;
      if (activeTab === 'warranties' && filters.status) params.status = filters.status;
      if (activeTab === 'repairs' && filters.repair_status) params.repair_status = filters.repair_status;

      const res = await api.get(`/reports/${activeTab}`, { params });
      setReport(res.data.data);
    } finally {
      setLoading(false);
    }
  }, [activeTab, filters]);

  useEffect(() => {
    fetchReport();
  }, [fetchReport]);

  const handleExport = async (format: 'xlsx' | 'csv' | 'pdf') => {
    setExporting(format);
    try {
      const params: Record<string, string | undefined> = {
        format,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        category_id: filters.category_id || undefined,
        location_id: filters.location_id || undefined,
        status: activeTab === 'warranties' ? filters.status || undefined : undefined,
        repair_status: activeTab === 'repairs' ? filters.repair_status || undefined : undefined,
      };
      await downloadBlob(`/reports/${activeTab}/export`, params, `assetforge-${activeTab}-report.${format}`);
    } finally {
      setExporting(null);
    }
  };

  const resetFilters = () => {
    setFilters({
      date_from: '',
      date_to: '',
      category_id: '',
      location_id: '',
      status: '',
      repair_status: '',
    });
  };

  const columns = useMemo<ColumnDef<Record<string, string | number | null>, unknown>[]>(() => {
    if (activeTab === 'inventory') {
      return [
        { accessorKey: 'asset_tag', header: 'Asset Tag' },
        { accessorKey: 'serial_number', header: 'Serial #' },
        { accessorKey: 'name', header: 'Device' },
        { accessorKey: 'category', header: 'Category' },
        { accessorKey: 'status', header: 'Status' },
        { accessorKey: 'location', header: 'Location' },
        { accessorKey: 'assigned_to', header: 'Custodian' },
        {
          accessorKey: 'purchase_cost',
          header: 'Cost',
          cell: ({ row }) => formatCurrency(row.original.purchase_cost),
        },
      ];
    }

    if (activeTab === 'warranties') {
      return [
        { accessorKey: 'asset_tag', header: 'Asset Tag' },
        { accessorKey: 'serial_number', header: 'Serial #' },
        { accessorKey: 'provider_name', header: 'Provider' },
        { accessorKey: 'policy_number', header: 'Policy #' },
        { accessorKey: 'end_date', header: 'Expiry Date' },
        {
          accessorKey: 'status',
          header: 'Status',
          cell: ({ row }) => {
            const st = String(row.original.status || '');
            return (
              <Badge variant={st === 'active' ? 'success' : st === 'expiring_soon' ? 'warning' : 'danger'}>
                {st.replace(/_/g, ' ')}
              </Badge>
            );
          },
        },
        { accessorKey: 'days_remaining', header: 'Days Left' },
      ];
    }

    return [
      { accessorKey: 'job_number', header: 'Ticket #' },
      { accessorKey: 'asset_tag', header: 'Asset Tag' },
      { accessorKey: 'fault_type', header: 'Fault' },
      { accessorKey: 'repair_center', header: 'Center' },
      { accessorKey: 'vendor_name', header: 'Vendor' },
      {
        accessorKey: 'repair_status',
        header: 'Status',
        cell: ({ row }) => (
          <Badge variant="info" className="capitalize">
            {String(row.original.repair_status || '').replace(/_/g, ' ')}
          </Badge>
        ),
      },
      { accessorKey: 'start_date', header: 'Start Date' },
      {
        accessorKey: 'cost',
        header: 'Cost',
        cell: ({ row }) => formatCurrency(row.original.cost),
      },
    ];
  }, [activeTab]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <FileBarChart2 className="w-6 h-6 text-indigo-600" />
            Enterprise Reports &amp; Exports
          </h1>
          <p className="text-sm text-slate-500">
            Generate filtered inventory, warranty expiration, and repair cost reports in Excel, CSV, or PDF.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Button variant="outline" size="sm" disabled={Boolean(exporting)} onClick={() => handleExport('xlsx')}>
            <Download className="w-3.5 h-3.5" /> {exporting === 'xlsx' ? 'Exporting...' : 'Excel (.xlsx)'}
          </Button>
          <Button variant="outline" size="sm" disabled={Boolean(exporting)} onClick={() => handleExport('csv')}>
            <Download className="w-3.5 h-3.5" /> {exporting === 'csv' ? 'Exporting...' : 'CSV (.csv)'}
          </Button>
          <Button size="sm" disabled={Boolean(exporting)} onClick={() => handleExport('pdf')}>
            <Download className="w-3.5 h-3.5" /> {exporting === 'pdf' ? 'Generating...' : 'PDF (.pdf)'}
          </Button>
        </div>
      </div>

      {/* Report Type Tabs */}
      <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        {[
          { id: 'inventory', label: 'Inventory Valuation' },
          { id: 'warranties', label: 'Warranty Expiration' },
          { id: 'repairs', label: 'Repair Cost Analysis' },
        ].map((tab) => (
          <button
            key={tab.id}
            type="button"
            onClick={() => setActiveTab(tab.id as ReportTab)}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              activeTab === tab.id
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Filter Bar */}
      <Card>
        <CardContent className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
          <Input
            label="Date From"
            type="date"
            value={filters.date_from}
            onChange={(e) => setFilters({ ...filters, date_from: e.target.value })}
          />
          <Input
            label="Date To"
            type="date"
            value={filters.date_to}
            onChange={(e) => setFilters({ ...filters, date_to: e.target.value })}
          />
          <Select
            label="Category"
            value={filters.category_id}
            onChange={(e) => setFilters({ ...filters, category_id: e.target.value })}
          >
            <option value="">All Categories</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </Select>
          <Select
            label="Location"
            value={filters.location_id}
            onChange={(e) => setFilters({ ...filters, location_id: e.target.value })}
          >
            <option value="">All Locations</option>
            {locations.map((l) => (
              <option key={l.id} value={l.id}>
                {l.name}
              </option>
            ))}
          </Select>

          {activeTab === 'warranties' ? (
            <Select
              label="Warranty Status"
              value={filters.status}
              onChange={(e) => setFilters({ ...filters, status: e.target.value })}
            >
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="expiring_soon">Expiring Soon</option>
              <option value="expired">Expired</option>
            </Select>
          ) : activeTab === 'repairs' ? (
            <Select
              label="Repair Status"
              value={filters.repair_status}
              onChange={(e) => setFilters({ ...filters, repair_status: e.target.value })}
            >
              <option value="">All</option>
              <option value="pending">Pending</option>
              <option value="diagnosing">Diagnosing</option>
              <option value="in_repair">In Repair</option>
              <option value="parts_on_order">Parts On Order</option>
              <option value="completed">Completed</option>
              <option value="unrepairable">Unrepairable</option>
            </Select>
          ) : (
            <div className="hidden lg:block" />
          )}

          <div className="flex gap-2">
            <Button variant="outline" className="w-full" onClick={fetchReport}>
              <Filter className="w-3.5 h-3.5" /> Apply
            </Button>
            <Button variant="ghost" onClick={resetFilters} title="Reset Filters">
              <RotateCcw className="w-3.5 h-3.5" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Summary KPI Cards + Breakdown Chart */}
      {report && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="grid grid-cols-2 gap-4">
            {Object.entries(report.summary).map(([key, val]) => {
              const isMoney = key.includes('valuation') || key.includes('cost');
              return (
                <Card key={key}>
                  <CardContent>
                    <p className="text-xs uppercase font-semibold text-slate-500">{key.replace(/_/g, ' ')}</p>
                    <p className="text-xl font-bold text-slate-900 mt-1">
                      {isMoney ? formatCurrency(val) : String(val)}
                    </p>
                  </CardContent>
                </Card>
              );
            })}
          </div>

          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Distribution Breakdown</CardTitle>
              <CardDescription>Aggregated counts across matching records</CardDescription>
            </CardHeader>
            <CardContent className="h-52">
              {report.breakdown.length === 0 ? (
                <div className="h-full flex items-center justify-center text-xs text-slate-400">
                  No chart data for current filters.
                </div>
              ) : (
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={report.breakdown} margin={{ top: 5, right: 10, left: -20, bottom: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                    <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} />
                    <YAxis allowDecimals={false} tick={{ fontSize: 11, fill: '#64748b' }} />
                    <Tooltip />
                    <Bar dataKey="count" name="Records" fill="#4f46e5" radius={[6, 6, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              )}
            </CardContent>
          </Card>
        </div>
      )}

      <DataTable
        columns={columns}
        data={report?.rows || []}
        loading={loading}
        emptyMessage="No report rows match the selected filters."
      />
    </div>
  );
};
