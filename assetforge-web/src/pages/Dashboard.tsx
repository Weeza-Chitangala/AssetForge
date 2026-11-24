import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import {
  Laptop,
  Wrench,
  ShieldCheck,
  DollarSign,
  Activity,
  AlertTriangle,
  ArrowRight,
  Clock,
} from 'lucide-react';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { formatCurrency } from '../lib/utils';
import type { DashboardData } from '../types';

export const Dashboard: React.FC = () => {
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api
      .get('/dashboard/summary')
      .then((res) => {
        setData(res.data.data);
        setLoading(false);
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Failed to load dashboard summary');
        setLoading(false);
      });
  }, []);

  if (loading) {
    return (
      <div className="space-y-6">
        <div className="h-8 w-64 bg-slate-200 rounded animate-pulse" />
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-28 bg-white rounded-xl border border-slate-200 animate-pulse" />
          ))}
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="h-80 bg-white rounded-xl border border-slate-200 animate-pulse" />
          <div className="h-80 bg-white rounded-xl border border-slate-200 animate-pulse" />
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3">
        <AlertTriangle className="w-5 h-5 shrink-0" />
        <span className="text-sm">{error}</span>
      </div>
    );
  }

  const warrantyChartData = [
    { name: 'Active', value: data.kpis.active_warranties, color: '#10b981' },
    { name: 'Expiring (30d)', value: data.kpis.expiring_warranties, color: '#f59e0b' },
    { name: 'Expired', value: data.kpis.expired_warranties, color: '#ef4444' },
  ];

  const statusChartData = data.breakdowns.by_status.map((item) => ({
    name: item.name,
    value: item.count,
    color: item.color || '#6366f1',
  }));

  const repairStatusVariant = (status: string) => {
    switch (status) {
      case 'completed':
        return 'success';
      case 'in_repair':
      case 'diagnosing':
        return 'info';
      case 'parts_on_order':
      case 'pending':
        return 'warning';
      case 'unrepairable':
        return 'danger';
      default:
        return 'default';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Executive ITAM Dashboard</h1>
          <p className="text-sm text-slate-500">
            Real-time telemetry across hardware inventory, warranties, repairs, and custody movements.
          </p>
        </div>
        <Link
          to="/assets"
          className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm"
        >
          View Equipment Registry
          <ArrowRight className="w-4 h-4" />
        </Link>
      </div>

      {/* KPI Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <Card>
          <CardContent className="flex items-center justify-between">
            <div>
              <p className="text-xs uppercase font-semibold text-slate-500 tracking-wider">Total Assets</p>
              <h3 className="text-2xl font-bold text-slate-900 mt-1">{data.kpis.total_assets}</h3>
              <p className="text-xs text-slate-400 mt-1">{data.kpis.assets_in_repair} currently in repair</p>
            </div>
            <div className="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
              <Laptop className="w-5 h-5" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="flex items-center justify-between">
            <div>
              <p className="text-xs uppercase font-semibold text-slate-500 tracking-wider">Total Valuation</p>
              <h3 className="text-2xl font-bold text-slate-900 mt-1">
                {formatCurrency(data.kpis.total_valuation)}
              </h3>
              <p className="text-xs text-slate-400 mt-1">Capitalized hardware cost</p>
            </div>
            <div className="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <DollarSign className="w-5 h-5" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="flex items-center justify-between">
            <div>
              <p className="text-xs uppercase font-semibold text-slate-500 tracking-wider">Active Warranties</p>
              <h3 className="text-2xl font-bold text-slate-900 mt-1">{data.kpis.active_warranties}</h3>
              <p className="text-xs text-amber-600 font-medium mt-1">
                {data.kpis.expiring_warranties} expiring within 30 days
              </p>
            </div>
            <div className="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <ShieldCheck className="w-5 h-5" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="flex items-center justify-between">
            <div>
              <p className="text-xs uppercase font-semibold text-slate-500 tracking-wider">Repair Spend</p>
              <h3 className="text-2xl font-bold text-slate-900 mt-1">
                {formatCurrency(data.kpis.total_repair_spend)}
              </h3>
              <p className="text-xs text-slate-400 mt-1">{data.kpis.assets_in_repair} open service tickets</p>
            </div>
            <div className="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
              <Wrench className="w-5 h-5" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Assets by Category</CardTitle>
            <CardDescription>Hardware distribution across asset classes</CardDescription>
          </CardHeader>
          <CardContent className="h-72">
            {data.breakdowns.by_category.length === 0 ? (
              <div className="h-full flex items-center justify-center text-xs text-slate-400">
                No category data available.
              </div>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={data.breakdowns.by_category} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                  <XAxis dataKey="code" tick={{ fontSize: 12, fill: '#64748b' }} />
                  <YAxis allowDecimals={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                  <Tooltip />
                  <Bar dataKey="count" name="Assets" fill="#4f46e5" radius={[6, 6, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Lifecycle Status Breakdown</CardTitle>
            <CardDescription>Current deployment vs. maintenance state</CardDescription>
          </CardHeader>
          <CardContent className="h-72">
            {statusChartData.length === 0 ? (
              <div className="h-full flex items-center justify-center text-xs text-slate-400">
                No status data available.
              </div>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={statusChartData}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="45%"
                    innerRadius={48}
                    outerRadius={76}
                    paddingAngle={3}
                  >
                    {statusChartData.map((entry, idx) => (
                      <Cell key={idx} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend wrapperStyle={{ fontSize: '11px' }} />
                </PieChart>
              </ResponsiveContainer>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>Warranty &amp; Repair Health</CardTitle>
            <CardDescription>Contract coverage &amp; expiration outlook</CardDescription>
          </CardHeader>
          <CardContent className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={warrantyChartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} />
                <YAxis allowDecimals={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                <Tooltip />
                <Bar dataKey="value" name="Warranties" radius={[6, 6, 0, 0]}>
                  {warrantyChartData.map((entry, idx) => (
                    <Cell key={idx} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      {/* Activity Timelines */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <div className="flex items-center gap-2">
              <Activity className="w-4 h-4 text-indigo-600" />
              <CardTitle>Recent Custody Movements</CardTitle>
            </div>
            <Link to="/audit-logs" className="text-xs font-medium text-indigo-600 hover:underline">
              Audit trail
            </Link>
          </CardHeader>
          <CardContent>
            {data.activity.recent_movements.length === 0 ? (
              <p className="text-xs text-slate-400 py-6 text-center">No custody movements logged yet.</p>
            ) : (
              <div className="divide-y divide-slate-100">
                {data.activity.recent_movements.map((m) => (
                  <div key={m.id} className="py-3 flex justify-between items-start gap-4 text-sm">
                    <div>
                      <p className="font-medium text-slate-800">
                        {m.asset_tag || 'Asset'} —{' '}
                        <span className="capitalize text-indigo-700">{m.action_type.replace(/_/g, ' ')}</span>
                      </p>
                      <p className="text-xs text-slate-500 mt-0.5">{m.notes || 'Lifecycle state updated'}</p>
                    </div>
                    <div className="text-right shrink-0">
                      <p className="text-xs font-medium text-slate-600">{m.performer || 'System'}</p>
                      <p className="text-[11px] text-slate-400 flex items-center justify-end gap-1 mt-0.5">
                        <Clock className="w-3 h-3" /> {m.created_at}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <div className="flex items-center gap-2">
              <Wrench className="w-4 h-4 text-amber-600" />
              <CardTitle>Recent Repair Tickets</CardTitle>
            </div>
            <Link to="/repairs" className="text-xs font-medium text-indigo-600 hover:underline">
              All repairs
            </Link>
          </CardHeader>
          <CardContent>
            {data.activity.recent_repairs.length === 0 ? (
              <p className="text-xs text-slate-400 py-6 text-center">No repair tickets logged.</p>
            ) : (
              <div className="divide-y divide-slate-100">
                {data.activity.recent_repairs.map((r) => (
                  <div key={r.id} className="py-3 flex justify-between items-start gap-4 text-sm">
                    <div>
                      <p className="font-medium text-slate-800">
                        {r.job_number}{' '}
                        {r.asset_tag && <span className="text-xs text-slate-500 font-normal">({r.asset_tag})</span>}
                      </p>
                      <p className="text-xs text-slate-500 mt-0.5">{r.fault}</p>
                    </div>
                    <div className="text-right shrink-0 space-y-1">
                      <Badge variant={repairStatusVariant(r.status)} className="capitalize">
                        {r.status.replace(/_/g, ' ')}
                      </Badge>
                      <p className="text-[11px] text-slate-400">{r.created_at}</p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
