import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { type ColumnDef } from '@tanstack/react-table';
import {
  ShieldCheck,
  Plus,
  ExternalLink,
  Copy,
  Check,
  Pencil,
  Trash2,
} from 'lucide-react';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';
import { DataTable } from '../components/ui/DataTable';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { Modal } from '../components/ui/Modal';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { Textarea } from '../components/ui/Textarea';
import { formatDate } from '../lib/utils';
import type { EquipmentAsset, PaginationMeta, Warranty } from '../types';

export const WarrantiesPage: React.FC = () => {
  const { hasPermission } = useAuth();
  const [warranties, setWarranties] = useState<Warranty[]>([]);
  const [assets, setAssets] = useState<EquipmentAsset[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [copiedId, setCopiedId] = useState<string | null>(null);

  // Modal state
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Warranty | null>(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    equipment_asset_id: '',
    provider_name: '',
    policy_number: '',
    warranty_type: 'standard',
    start_date: '',
    end_date: '',
    service_level: '',
    support_phone: '',
    support_email: '',
    support_url: '',
    terms_and_conditions: '',
  });

  const fetchWarranties = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get('/warranties', {
        params: {
          page,
          per_page: 15,
          status: statusFilter || undefined,
        },
      });
      const payload = res.data.data;
      setWarranties(payload.data || []);
      setMeta(payload.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 });
    } finally {
      setLoading(false);
    }
  }, [page, statusFilter]);

  useEffect(() => {
    fetchWarranties();
  }, [fetchWarranties]);

  useEffect(() => {
    api.get('/assets', { params: { per_page: 100 } }).then((res) => {
      setAssets(res.data.data?.data || []);
    });
  }, []);

  const openCreate = () => {
    setEditing(null);
    setForm({
      equipment_asset_id: '',
      provider_name: '',
      policy_number: '',
      warranty_type: 'standard',
      start_date: '',
      end_date: '',
      service_level: '',
      support_phone: '',
      support_email: '',
      support_url: '',
      terms_and_conditions: '',
    });
    setError(null);
    setModalOpen(true);
  };

  const openEdit = (w: Warranty) => {
    setEditing(w);
    setForm({
      equipment_asset_id: w.asset?.id || '',
      provider_name: w.provider_name || '',
      policy_number: w.policy_number || '',
      warranty_type: w.warranty_type || 'standard',
      start_date: w.start_date || '',
      end_date: w.end_date || '',
      service_level: w.service_level || '',
      support_phone: w.support_contact?.phone || '',
      support_email: w.support_contact?.email || '',
      support_url: w.support_contact?.portal_url || '',
      terms_and_conditions: w.terms_and_conditions || '',
    });
    setError(null);
    setModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    const payload = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]));

    try {
      if (editing) {
        await api.put(`/warranties/${editing.id}`, payload);
      } else {
        await api.post('/warranties', payload);
      }
      setModalOpen(false);
      fetchWarranties();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } };
      setError(axiosErr.response?.data?.message || 'Failed to save warranty.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (w: Warranty) => {
    if (!window.confirm(`Delete warranty policy ${w.policy_number || w.provider_name}?`)) return;
    await api.delete(`/warranties/${w.id}`);
    fetchWarranties();
  };

  const copySerial = (id: string, serial?: string | null) => {
    if (!serial) return;
    navigator.clipboard.writeText(serial);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 1500);
  };

  const statusBadge = (status: Warranty['status']) => {
    if (status === 'active') return <Badge variant="success">Active</Badge>;
    if (status === 'expiring_soon') return <Badge variant="warning">Expiring Soon</Badge>;
    return <Badge variant="danger">Expired</Badge>;
  };

  const columns = useMemo<ColumnDef<Warranty, unknown>[]>(
    () => [
      {
        id: 'asset_tag',
        accessorFn: (row) => row.asset?.asset_tag || '',
        header: 'Asset',
        cell: ({ row }) => (
          <div>
            <Link to={`/assets/${row.original.asset?.id}`} className="font-semibold text-indigo-600 hover:underline">
              {row.original.asset?.asset_tag || '—'}
            </Link>
            <div className="flex items-center gap-1.5 text-xs text-slate-500 mt-0.5">
              <span className="font-mono">{row.original.asset?.serial_number || 'N/A'}</span>
              {row.original.asset?.serial_number && (
                <button
                  type="button"
                  onClick={() => copySerial(row.original.id, row.original.asset?.serial_number)}
                  className="text-slate-400 hover:text-indigo-600"
                  title="Copy Serial Number"
                >
                  {copiedId === row.original.id ? (
                    <Check className="w-3 h-3 text-emerald-600" />
                  ) : (
                    <Copy className="w-3 h-3" />
                  )}
                </button>
              )}
            </div>
          </div>
        ),
      },
      {
        accessorKey: 'provider_name',
        header: 'Provider & Policy',
        cell: ({ row }) => (
          <div>
            <p className="font-medium text-slate-800">{row.original.provider_name}</p>
            <p className="text-xs text-slate-500">
              Policy: {row.original.policy_number || 'N/A'} •{' '}
              <span className="capitalize">{row.original.warranty_type?.replace(/_/g, ' ') || 'standard'}</span>
            </p>
          </div>
        ),
      },
      {
        accessorKey: 'end_date',
        header: 'Coverage Window',
        cell: ({ row }) => (
          <div className="text-xs">
            <p className="text-slate-700 font-medium">
              {formatDate(row.original.start_date)} → {formatDate(row.original.end_date)}
            </p>
            <p className="text-slate-500">{row.original.days_remaining} days remaining</p>
          </div>
        ),
      },
      {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => statusBadge(row.original.status),
      },
      {
        id: 'oem_lookup',
        header: 'OEM Verification',
        enableSorting: false,
        cell: ({ row }) =>
          row.original.oem_lookup_url ? (
            <a
              href={row.original.oem_lookup_url}
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2.5 py-1 rounded-md"
            >
              HP OEM Lookup <ExternalLink className="w-3 h-3" />
            </a>
          ) : (
            <span className="text-xs text-slate-400">N/A</span>
          ),
      },
      {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        cell: ({ row }) => (
          <div className="flex items-center gap-1">
            {hasPermission('assets.update') && (
              <button
                type="button"
                onClick={() => openEdit(row.original)}
                className="p-1.5 rounded-md text-slate-500 hover:text-amber-600 hover:bg-amber-50"
                title="Edit Warranty"
              >
                <Pencil className="w-4 h-4" />
              </button>
            )}
            {hasPermission('assets.delete') && (
              <button
                type="button"
                onClick={() => handleDelete(row.original)}
                className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50"
                title="Delete Warranty"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            )}
          </div>
        ),
      },
    ],
    [copiedId, hasPermission]
  );

  const filterTabs = [
    { key: '', label: 'All Warranties' },
    { key: 'active', label: 'Active' },
    { key: 'expiring_soon', label: 'Expiring Soon (≤30d)' },
    { key: 'expired', label: 'Expired' },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <ShieldCheck className="w-6 h-6 text-indigo-600" />
            Warranties &amp; OEM Coverage
          </h1>
          <p className="text-sm text-slate-500">
            Monitor active support contracts, upcoming expirations, and direct HP OEM serial verification.
          </p>
        </div>
        {hasPermission('assets.create') && (
          <Button size="sm" onClick={openCreate}>
            <Plus className="w-4 h-4" /> Add Warranty Contract
          </Button>
        )}
      </div>

      {/* Filter Pills */}
      <div className="flex flex-wrap gap-2">
        {filterTabs.map((tab) => (
          <button
            key={tab.key}
            type="button"
            onClick={() => {
              setStatusFilter(tab.key);
              setPage(1);
            }}
            className={`px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors ${
              statusFilter === tab.key
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      <DataTable
        columns={columns}
        data={warranties}
        loading={loading}
        emptyMessage="No warranty contracts found."
        serverPagination={{
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
          onPageChange: (p) => setPage(p),
        }}
      />

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? 'Update Warranty Contract' : 'Register Warranty Contract'}
        size="lg"
      >
        <form onSubmit={handleSave} className="space-y-4">
          {error && <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">{error}</div>}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Select
              label="Equipment Asset"
              value={form.equipment_asset_id}
              onChange={(e) => setForm({ ...form, equipment_asset_id: e.target.value })}
              required
              disabled={Boolean(editing)}
            >
              <option value="">Select asset...</option>
              {assets.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.asset_tag} — {a.name || a.serial_number}
                </option>
              ))}
            </Select>

            <Input
              label="Provider Name"
              value={form.provider_name}
              onChange={(e) => setForm({ ...form, provider_name: e.target.value })}
              placeholder="HP Care Pack / Dell ProSupport"
              required
            />

            <Input
              label="Policy Number"
              value={form.policy_number}
              onChange={(e) => setForm({ ...form, policy_number: e.target.value })}
            />

            <Select
              label="Warranty Type"
              value={form.warranty_type}
              onChange={(e) => setForm({ ...form, warranty_type: e.target.value })}
            >
              <option value="standard">Standard</option>
              <option value="extended">Extended</option>
              <option value="accidental_damage">Accidental Damage</option>
              <option value="onsite">Onsite Support</option>
            </Select>

            <Input
              label="Start Date"
              type="date"
              value={form.start_date}
              onChange={(e) => setForm({ ...form, start_date: e.target.value })}
              required
            />

            <Input
              label="End Date"
              type="date"
              value={form.end_date}
              onChange={(e) => setForm({ ...form, end_date: e.target.value })}
              required
            />

            <Input
              label="Service Level (SLA)"
              value={form.service_level}
              onChange={(e) => setForm({ ...form, service_level: e.target.value })}
              placeholder="Next Business Day Onsite"
            />

            <Input
              label="Support Phone"
              value={form.support_phone}
              onChange={(e) => setForm({ ...form, support_phone: e.target.value })}
            />

            <Input
              label="Support Email"
              type="email"
              value={form.support_email}
              onChange={(e) => setForm({ ...form, support_email: e.target.value })}
            />

            <Input
              label="Support Portal URL"
              type="url"
              value={form.support_url}
              onChange={(e) => setForm({ ...form, support_url: e.target.value })}
              placeholder="https://support.hp.com"
            />
          </div>

          <Textarea
            label="Terms & Conditions"
            rows={2}
            value={form.terms_and_conditions}
            onChange={(e) => setForm({ ...form, terms_and_conditions: e.target.value })}
          />

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'Saving...' : editing ? 'Update Warranty' : 'Create Warranty'}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
