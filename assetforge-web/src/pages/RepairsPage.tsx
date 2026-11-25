import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { type ColumnDef } from '@tanstack/react-table';
import { Wrench, Plus, ArrowUpRight, Clock } from 'lucide-react';
import api from '../services/api';
import { useAuth } from '../context/useAuth';
import { DataTable } from '../components/ui/DataTable';
import { Button } from '../components/ui/Button';
import { Badge, type BadgeVariant } from '../components/ui/Badge';
import { Modal } from '../components/ui/Modal';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { Textarea } from '../components/ui/Textarea';
import { formatCurrency, formatDate } from '../lib/utils';
import type { EquipmentAsset, FaultType, PaginationMeta, RepairJob, User } from '../types';

const STATUS_OPTIONS = [
  { value: '', label: 'All Tickets' },
  { value: 'pending', label: 'Pending' },
  { value: 'diagnosing', label: 'Diagnosing' },
  { value: 'in_repair', label: 'In Repair' },
  { value: 'parts_on_order', label: 'Parts On Order' },
  { value: 'completed', label: 'Completed' },
  { value: 'unrepairable', label: 'Unrepairable' },
];

export const RepairsPage: React.FC = () => {
  const { hasPermission } = useAuth();
  const [repairs, setRepairs] = useState<RepairJob[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);

  // Reference lists
  const [assets, setAssets] = useState<EquipmentAsset[]>([]);
  const [faultTypes, setFaultTypes] = useState<FaultType[]>([]);
  const [users, setUsers] = useState<User[]>([]);

  // Create modal
  const [createOpen, setCreateOpen] = useState(false);
  const [creating, setCreating] = useState(false);
  const [createErr, setCreateErr] = useState<string | null>(null);
  const [createForm, setCreateForm] = useState({
    equipment_asset_id: '',
    fault_type_id: '',
    fault_description: '',
    technician_id: '',
    repair_center: 'internal',
    vendor_name: '',
    cost: '',
    diagnostic_notes: '',
  });

  // Workflow / Progress modal
  const [selectedJob, setSelectedJob] = useState<RepairJob | null>(null);
  const [progressSaving, setProgressSaving] = useState(false);
  const [progressErr, setProgressErr] = useState<string | null>(null);
  const [progressForm, setProgressForm] = useState({
    status: 'diagnosing',
    comments: '',
    cost: '',
    resolution_summary: '',
  });

  const [reloadTick, setReloadTick] = useState(0);

  const reloadRepairs = useCallback(() => {
    setLoading(true);
    setReloadTick((t) => t + 1);
  }, []);

  useEffect(() => {
    let active = true;
    api
      .get('/repairs', {
        params: {
          page,
          per_page: 15,
          repair_status: statusFilter || undefined,
        },
      })
      .then((res) => {
        if (!active) return;
        const payload = res.data.data;
        setRepairs(payload.data || []);
        setMeta(payload.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 });
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [page, statusFilter, reloadTick]);

  useEffect(() => {
    Promise.allSettled([
      api.get('/assets', { params: { per_page: 100 } }),
      api.get('/fault-types'),
      api.get('/users', { params: { per_page: 100 } }),
    ]).then(([aRes, fRes, uRes]) => {
      if (aRes.status === 'fulfilled') setAssets(aRes.value.data.data?.data || []);
      if (fRes.status === 'fulfilled') setFaultTypes(fRes.value.data.data || []);
      if (uRes.status === 'fulfilled') setUsers(uRes.value.data.data?.data || []);
    });
  }, []);

  const openWorkflowModal = (job: RepairJob) => {
    setSelectedJob(job);
    setProgressForm({
      status: job.repair_status === 'pending' ? 'diagnosing' : job.repair_status,
      comments: '',
      cost: job.cost ? String(job.cost) : '',
      resolution_summary: job.resolution_summary || '',
    });
    setProgressErr(null);
  };

  const handleCreateRepair = async (e: React.FormEvent) => {
    e.preventDefault();
    setCreating(true);
    setCreateErr(null);
    const payload = Object.fromEntries(Object.entries(createForm).map(([k, v]) => [k, v === '' ? null : v]));

    try {
      await api.post('/repairs', payload);
      setCreateOpen(false);
      setCreateForm({
        equipment_asset_id: '',
        fault_type_id: '',
        fault_description: '',
        technician_id: '',
        repair_center: 'internal',
        vendor_name: '',
        cost: '',
        diagnostic_notes: '',
      });
      reloadRepairs();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } };
      setCreateErr(axiosErr.response?.data?.message || 'Failed to log repair ticket.');
    } finally {
      setCreating(false);
    }
  };

  const handleAddProgress = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedJob) return;
    setProgressSaving(true);
    setProgressErr(null);

    const payload = Object.fromEntries(Object.entries(progressForm).map(([k, v]) => [k, v === '' ? null : v]));

    try {
      const res = await api.post(`/repairs/${selectedJob.id}/progress`, payload);
      setSelectedJob(res.data.data);
      setProgressForm((prev) => ({ ...prev, comments: '' }));
      reloadRepairs();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } };
      setProgressErr(axiosErr.response?.data?.message || 'Failed to record repair progress.');
    } finally {
      setProgressSaving(false);
    }
  };

  const statusVariant = (status: string): BadgeVariant => {
    switch (status) {
      case 'completed':
        return 'success';
      case 'diagnosing':
      case 'in_repair':
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

  const columns = useMemo<ColumnDef<RepairJob, unknown>[]>(
    () => [
      {
        accessorKey: 'job_number',
        header: 'Ticket #',
        cell: ({ row }) => <span className="font-semibold text-slate-900">{row.original.job_number}</span>,
      },
      {
        id: 'asset',
        accessorFn: (row) => row.asset?.asset_tag || '',
        header: 'Asset',
        cell: ({ row }) => (
          <div>
            <Link to={`/assets/${row.original.asset?.id}`} className="font-medium text-indigo-600 hover:underline">
              {row.original.asset?.asset_tag || '—'}
            </Link>
            <p className="text-xs text-slate-500">{row.original.asset?.name || row.original.asset?.serial_number}</p>
          </div>
        ),
      },
      {
        accessorKey: 'fault_description',
        header: 'Fault & Severity',
        cell: ({ row }) => (
          <div className="max-w-xs">
            <p className="text-xs font-medium text-slate-800 truncate">{row.original.fault_description}</p>
            <p className="text-[11px] text-slate-500">
              {row.original.fault_type?.name || 'General Defect'} •{' '}
              <span className="capitalize">{row.original.repair_center.replace(/_/g, ' ')}</span>
            </p>
          </div>
        ),
      },
      {
        accessorKey: 'repair_status',
        header: 'Workflow State',
        cell: ({ row }) => (
          <Badge variant={statusVariant(row.original.repair_status)} className="capitalize whitespace-nowrap">
            {row.original.repair_status.replace(/_/g, ' ')}
          </Badge>
        ),
      },
      {
        id: 'technician',
        accessorFn: (row) => row.technician?.name || '',
        header: 'Technician',
        cell: ({ row }) => row.original.technician?.name || <span className="text-slate-400">Unassigned</span>,
      },
      {
        accessorKey: 'cost',
        header: 'Cost',
        cell: ({ row }) => formatCurrency(row.original.cost),
      },
      {
        accessorKey: 'start_date',
        header: 'Logged',
        cell: ({ row }) => <span className="text-xs">{formatDate(row.original.start_date)}</span>,
      },
      {
        id: 'actions',
        header: 'Workflow',
        enableSorting: false,
        cell: ({ row }) => (
          <Button variant="outline" size="sm" onClick={() => openWorkflowModal(row.original)}>
            Track / Update <ArrowUpRight className="w-3.5 h-3.5" />
          </Button>
        ),
      },
    ],
    []
  );

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Wrench className="w-6 h-6 text-amber-600" />
            Repair Jobs &amp; Service Workflow
          </h1>
          <p className="text-sm text-slate-500">
            Track hardware diagnostics, vendor repairs, cost accumulation, and automatic stock restoration.
          </p>
        </div>
        {hasPermission('repairs.create') && (
          <Button size="sm" onClick={() => setCreateOpen(true)}>
            <Plus className="w-4 h-4" /> Log Repair Ticket
          </Button>
        )}
      </div>

      {/* Status Filter Pills */}
      <div className="flex flex-wrap gap-2">
        {STATUS_OPTIONS.map((opt) => (
          <button
            key={opt.value}
            type="button"
            onClick={() => {
              setStatusFilter(opt.value);
              setPage(1);
            }}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors ${
              statusFilter === opt.value
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
            }`}
          >
            {opt.label}
          </button>
        ))}
      </div>

      <DataTable
        columns={columns}
        data={repairs}
        loading={loading}
        emptyMessage="No repair tickets match the selected status."
        serverPagination={{
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
          onPageChange: (p) => setPage(p),
        }}
      />

      {/* Create Repair Modal */}
      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="Log New Repair Ticket"
        description="Automatically transitions the selected asset status to Under Repair."
        size="lg"
      >
        <form onSubmit={handleCreateRepair} className="space-y-4">
          {createErr && (
            <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">{createErr}</div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Select
              label="Equipment Asset"
              value={createForm.equipment_asset_id}
              onChange={(e) => setCreateForm({ ...createForm, equipment_asset_id: e.target.value })}
              required
            >
              <option value="">Select asset...</option>
              {assets.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.asset_tag} — {a.name || a.serial_number}
                </option>
              ))}
            </Select>

            <Select
              label="Fault Type"
              value={createForm.fault_type_id}
              onChange={(e) => setCreateForm({ ...createForm, fault_type_id: e.target.value })}
            >
              <option value="">Select fault classification...</option>
              {faultTypes.map((f) => (
                <option key={f.id} value={f.id}>
                  {f.name} ({f.severity || 'normal'})
                </option>
              ))}
            </Select>

            <Select
              label="Repair Center"
              value={createForm.repair_center}
              onChange={(e) => setCreateForm({ ...createForm, repair_center: e.target.value })}
            >
              <option value="internal">Internal IT Workshop</option>
              <option value="external_vendor">External Vendor</option>
              <option value="warranty_oem">Warranty OEM</option>
            </Select>

            <Select
              label="Assign Technician"
              value={createForm.technician_id}
              onChange={(e) => setCreateForm({ ...createForm, technician_id: e.target.value })}
            >
              <option value="">Unassigned</option>
              {users.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.name}
                </option>
              ))}
            </Select>

            <Input
              label="Vendor Name (if external)"
              value={createForm.vendor_name}
              onChange={(e) => setCreateForm({ ...createForm, vendor_name: e.target.value })}
            />

            <Input
              label="Estimated Cost (USD)"
              type="number"
              step="0.01"
              min="0"
              value={createForm.cost}
              onChange={(e) => setCreateForm({ ...createForm, cost: e.target.value })}
            />
          </div>

          <Textarea
            label="Fault Description"
            rows={3}
            value={createForm.fault_description}
            onChange={(e) => setCreateForm({ ...createForm, fault_description: e.target.value })}
            required
          />

          <Textarea
            label="Initial Diagnostic Notes"
            rows={2}
            value={createForm.diagnostic_notes}
            onChange={(e) => setCreateForm({ ...createForm, diagnostic_notes: e.target.value })}
          />

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={() => setCreateOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={creating}>
              {creating ? 'Logging...' : 'Create Repair Ticket'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Repair Progress & Timeline Modal */}
      <Modal
        open={Boolean(selectedJob)}
        onClose={() => setSelectedJob(null)}
        title={selectedJob ? `Repair Workflow: ${selectedJob.job_number}` : ''}
        description={selectedJob ? `${selectedJob.asset?.asset_tag} — ${selectedJob.fault_description}` : ''}
        size="xl"
      >
        {selectedJob && (
          <div className="space-y-6">
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
              <div>
                <p className="text-slate-500">Current State</p>
                <div className="mt-1">
                  <Badge variant={statusVariant(selectedJob.repair_status)} className="capitalize">
                    {selectedJob.repair_status.replace(/_/g, ' ')}
                  </Badge>
                </div>
              </div>
              <div>
                <p className="text-slate-500">Repair Center</p>
                <p className="font-semibold text-slate-800 mt-1 capitalize">
                  {selectedJob.repair_center.replace(/_/g, ' ')}
                </p>
              </div>
              <div>
                <p className="text-slate-500">Accumulated Cost</p>
                <p className="font-semibold text-slate-800 mt-1">{formatCurrency(selectedJob.cost)}</p>
              </div>
              <div>
                <p className="text-slate-500">Logged Date</p>
                <p className="font-semibold text-slate-800 mt-1">{formatDate(selectedJob.start_date)}</p>
              </div>
            </div>

            {hasPermission('repairs.update') && (
              <form onSubmit={handleAddProgress} className="space-y-4 border border-slate-200 rounded-xl p-4">
                <h4 className="text-sm font-semibold text-slate-800">Record Technician Progress</h4>
                {progressErr && (
                  <div className="p-2.5 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">
                    {progressErr}
                  </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <Select
                    label="Transition Status To"
                    value={progressForm.status}
                    onChange={(e) => setProgressForm({ ...progressForm, status: e.target.value })}
                    required
                  >
                    <option value="pending">Pending</option>
                    <option value="diagnosing">Diagnosing</option>
                    <option value="in_repair">In Repair</option>
                    <option value="parts_on_order">Parts On Order</option>
                    <option value="completed">Completed (Restores Asset to Available)</option>
                    <option value="unrepairable">Unrepairable</option>
                  </Select>

                  <Input
                    label="Updated Total Cost (USD)"
                    type="number"
                    step="0.01"
                    min="0"
                    value={progressForm.cost}
                    onChange={(e) => setProgressForm({ ...progressForm, cost: e.target.value })}
                  />
                </div>

                <Textarea
                  label="Work Log / Technician Comments"
                  rows={2}
                  value={progressForm.comments}
                  onChange={(e) => setProgressForm({ ...progressForm, comments: e.target.value })}
                  placeholder="Replaced SSD, ran hardware diagnostics..."
                  required
                />

                {progressForm.status === 'completed' && (
                  <Textarea
                    label="Resolution Summary"
                    rows={2}
                    value={progressForm.resolution_summary}
                    onChange={(e) => setProgressForm({ ...progressForm, resolution_summary: e.target.value })}
                  />
                )}

                <div className="flex justify-end">
                  <Button type="submit" size="sm" disabled={progressSaving}>
                    {progressSaving ? 'Recording...' : 'Save Progress Update'}
                  </Button>
                </div>
              </form>
            )}

            <div>
              <h4 className="text-sm font-semibold text-slate-800 mb-3">Repair Timeline</h4>
              <div className="space-y-3">
                {(selectedJob.updates || []).length === 0 ? (
                  <p className="text-xs text-slate-400">No progress updates yet.</p>
                ) : (
                  selectedJob.updates?.map((u) => (
                    <div key={u.id} className="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1">
                      <div className="flex items-center justify-between">
                        <span className="font-semibold text-slate-800 capitalize">
                          {u.status_from ? `${u.status_from.replace(/_/g, ' ')} → ` : ''}
                          {u.status_to.replace(/_/g, ' ')}
                        </span>
                        <span className="text-slate-400 flex items-center gap-1">
                          <Clock className="w-3 h-3" /> {formatDate(u.created_at)}
                        </span>
                      </div>
                      <p className="text-slate-600">{u.comments}</p>
                      <p className="text-[11px] text-slate-400">By {u.author?.name || 'System'}</p>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
};
