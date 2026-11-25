import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  ArrowLeft,
  QrCode,
  Printer,
  UserCheck,
  RotateCcw,
  ArrowLeftRight,
  Cpu,
  DollarSign,
  History,
  Shield,
} from 'lucide-react';
import api from '../services/api';
import { useAuth } from '../context/useAuth';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { Modal } from '../components/ui/Modal';
import { Select } from '../components/ui/Select';
import { Textarea } from '../components/ui/Textarea';
import { formatCurrency, formatDate, formatDateTime } from '../lib/utils';
import type {
  AssetMovement,
  AssetStatus,
  AuditLog,
  Department,
  EquipmentAsset,
  LabelData,
  Location,
  User,
} from '../types';

export const AssetDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { hasPermission } = useAuth();

  const [asset, setAsset] = useState<EquipmentAsset | null>(null);
  const [movements, setMovements] = useState<AssetMovement[]>([]);
  const [auditLogs, setAuditLogs] = useState<AuditLog[]>([]);
  const [labelData, setLabelData] = useState<LabelData | null>(null);
  const [qrSvg, setQrSvg] = useState<string>('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Reference data for lifecycle actions
  const [users, setUsers] = useState<User[]>([]);
  const [locations, setLocations] = useState<Location[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [statuses, setStatuses] = useState<AssetStatus[]>([]);

  // Lifecycle action modal
  const [actionType, setActionType] = useState<'checkout' | 'checkin' | 'transfer' | null>(null);
  const [actionSaving, setActionSaving] = useState(false);
  const [actionErr, setActionErr] = useState<string | null>(null);
  const [actionForm, setActionForm] = useState({
    user_id: '',
    location_id: '',
    department_id: '',
    status_id: '',
    condition: 'good',
    notes: '',
  });

  const [reloadTick, setReloadTick] = useState(0);

  const triggerReload = () => {
    setLoading(true);
    setError(null);
    setReloadTick((t) => t + 1);
  };

  useEffect(() => {
    if (!id) return;
    let active = true;

    Promise.allSettled([
      api.get(`/assets/${id}`),
      api.get(`/assets/${id}/history`),
      api.get(`/assets/${id}/label-data`),
      api.get(`/assets/${id}/qr`, { responseType: 'text' }),
      api.get(`/audit-logs/EquipmentAsset/${id}`),
    ])
      .then(([assetRes, historyRes, labelRes, qrRes, auditRes]) => {
        if (!active) return;

        if (assetRes.status === 'fulfilled') {
          setAsset(assetRes.value.data.data);
          setError(null);
        } else {
          setError('Unable to load equipment asset.');
        }

        if (historyRes.status === 'fulfilled') {
          setMovements(historyRes.value.data.data?.data || []);
        }
        if (labelRes.status === 'fulfilled') {
          setLabelData(labelRes.value.data.data);
        }
        if (qrRes.status === 'fulfilled') {
          setQrSvg(qrRes.value.data);
        }
        if (auditRes.status === 'fulfilled') {
          setAuditLogs(auditRes.value.data.data || []);
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [id, reloadTick]);

  useEffect(() => {
    Promise.allSettled([
      api.get('/users', { params: { per_page: 100 } }),
      api.get('/locations'),
      api.get('/departments'),
      api.get('/asset-statuses'),
    ]).then(([u, l, d, s]) => {
      if (u.status === 'fulfilled') setUsers(u.value.data.data?.data || []);
      if (l.status === 'fulfilled') setLocations(l.value.data.data || []);
      if (d.status === 'fulfilled') setDepartments(d.value.data.data || []);
      if (s.status === 'fulfilled') setStatuses(s.value.data.data || []);
    });
  }, []);

  const openActionModal = (type: 'checkout' | 'checkin' | 'transfer') => {
    setActionType(type);
    setActionErr(null);
    setActionForm({
      user_id: '',
      location_id: asset?.location?.id || '',
      department_id: '',
      status_id: statuses.find((s) => s.code === 'AVAILABLE')?.id || '',
      condition: asset?.condition || 'good',
      notes: '',
    });
  };

  const handleLifecycleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!id || !actionType) return;
    setActionSaving(true);
    setActionErr(null);

    const payload = Object.fromEntries(Object.entries(actionForm).map(([k, v]) => [k, v === '' ? null : v]));

    try {
      await api.post(`/assets/${id}/${actionType}`, payload);
      setActionType(null);
      triggerReload();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } };
      setActionErr(axiosErr.response?.data?.message || 'Lifecycle action failed.');
    } finally {
      setActionSaving(false);
    }
  };

  if (loading) {
    return <div className="p-8 text-sm text-slate-500">Loading asset telemetry...</div>;
  }

  if (error || !asset) {
    return (
      <div className="space-y-4">
        <Link to="/assets" className="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:underline">
          <ArrowLeft className="w-4 h-4" /> Back to Assets
        </Link>
        <div className="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
          {error || 'Asset not found.'}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <Link to="/assets" className="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-indigo-600 mb-2">
            <ArrowLeft className="w-3.5 h-3.5" /> Back to Equipment Registry
          </Link>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{asset.asset_tag}</h1>
            {asset.status?.name && (
              <Badge>
                <span
                  className="w-2 h-2 rounded-full"
                  style={{ backgroundColor: asset.status.color || '#64748b' }}
                />
                {asset.status.name}
              </Badge>
            )}
          </div>
          <p className="text-sm text-slate-500">
            {asset.name || asset.model?.name} • Serial: <span className="font-mono">{asset.serial_number}</span>
          </p>
        </div>

        {hasPermission('assets.update') && (
          <div className="flex flex-wrap gap-2">
            <Button size="sm" onClick={() => openActionModal('checkout')}>
              <UserCheck className="w-4 h-4" /> Checkout
            </Button>
            <Button variant="outline" size="sm" onClick={() => openActionModal('checkin')}>
              <RotateCcw className="w-4 h-4" /> Checkin
            </Button>
            <Button variant="outline" size="sm" onClick={() => openActionModal('transfer')}>
              <ArrowLeftRight className="w-4 h-4" /> Transfer
            </Button>
          </div>
        )}
      </div>

      {/* Main Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 cols: Specs & Procurement */}
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader className="flex flex-row items-center gap-2">
              <Cpu className="w-4 h-4 text-indigo-600" />
              <CardTitle>Hardware &amp; Assignment Profile</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
              <div>
                <p className="text-xs text-slate-500">Category</p>
                <p className="font-medium text-slate-800">{asset.category?.name || '—'}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Manufacturer / Model</p>
                <p className="font-medium text-slate-800">
                  {asset.manufacturer?.name || '—'} {asset.model?.name || ''}
                </p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Location</p>
                <p className="font-medium text-slate-800">{asset.location?.name || '—'}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Assigned Custodian</p>
                <p className="font-medium text-slate-800">{asset.assigned_to?.name || 'Unassigned'}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Operating System</p>
                <p className="font-medium text-slate-800">{asset.specs?.operating_system || '—'}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Processor</p>
                <p className="font-medium text-slate-800">{asset.specs?.processor || '—'}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Memory / Storage</p>
                <p className="font-medium text-slate-800">
                  {asset.specs?.ram || '—'} / {asset.specs?.storage || '—'}
                </p>
              </div>
              <div>
                <p className="text-xs text-slate-500">MAC / IP Address</p>
                <p className="font-mono text-xs text-slate-700 mt-0.5">
                  {asset.specs?.mac_address || '—'} / {asset.specs?.ip_address || '—'}
                </p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Condition</p>
                <p className="font-medium text-slate-800 capitalize">{asset.condition}</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center gap-2">
              <DollarSign className="w-4 h-4 text-emerald-600" />
              <CardTitle>Procurement &amp; Warranty</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
              <div>
                <p className="text-xs text-slate-500">Purchase Cost</p>
                <p className="font-semibold text-slate-900">{formatCurrency(asset.procurement?.purchase_cost)}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Purchase Date</p>
                <p className="font-medium text-slate-800">{formatDate(asset.procurement?.purchase_date)}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Warranty Expiry</p>
                <p className="font-medium text-slate-800">{formatDate(asset.procurement?.warranty_expiry_date)}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500">Supplier / PO</p>
                <p className="font-medium text-slate-800">
                  {asset.procurement?.supplier || '—'} {asset.procurement?.order_number ? `(${asset.procurement.order_number})` : ''}
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Right Col: SVG QR Code & Thermal Print Preview */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <div className="flex items-center gap-2">
              <QrCode className="w-4 h-4 text-indigo-600" />
              <div>
                <CardTitle>Asset Tag &amp; QR Label</CardTitle>
                <CardDescription>Thermal label print preview</CardDescription>
              </div>
            </div>
            <Button variant="outline" size="sm" onClick={() => window.print()}>
              <Printer className="w-3.5 h-3.5" /> Print Label
            </Button>
          </CardHeader>
          <CardContent>
            <div className="printable-label p-4 rounded-xl border-2 border-dashed border-slate-300 bg-white space-y-3">
              <div className="flex items-center justify-between border-b border-slate-200 pb-2">
                <span className="text-xs font-black tracking-wider text-indigo-700">ASSETFORGE ITAM</span>
                <span className="text-[11px] font-mono text-slate-500">{labelData?.category || asset.category?.name}</span>
              </div>

              <div className="flex items-center gap-4">
                <div
                  className="w-28 h-28 shrink-0 bg-white p-1 border border-slate-200 rounded-lg [&>svg]:w-full [&>svg]:h-full"
                  dangerouslySetInnerHTML={{ __html: qrSvg }}
                />
                <div className="min-w-0 space-y-1 text-xs">
                  <p className="font-bold text-slate-900 text-sm">{labelData?.asset_tag || asset.asset_tag}</p>
                  <p className="text-slate-600 truncate">
                    {labelData?.manufacturer || asset.manufacturer?.name} {labelData?.model || asset.model?.name}
                  </p>
                  <p className="font-mono text-[11px] text-slate-500">
                    SN: {labelData?.serial_number || asset.serial_number}
                  </p>
                  <p className="text-[11px] text-slate-600 truncate">
                    Custodian: {labelData?.assigned_to || asset.assigned_to?.name || 'In Storage'}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Custody Movement History & Audit Log */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center gap-2">
            <History className="w-4 h-4 text-indigo-600" />
            <CardTitle>Custody &amp; Movement History</CardTitle>
          </CardHeader>
          <CardContent>
            {movements.length === 0 ? (
              <p className="text-xs text-slate-400">No lifecycle movements recorded for this asset.</p>
            ) : (
              <div className="space-y-3">
                {movements.map((m) => (
                  <div key={m.id} className="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                    <div className="flex items-center justify-between font-semibold text-slate-800">
                      <span className="capitalize">{m.action_type.replace(/_/g, ' ')}</span>
                      <span className="text-slate-400 font-normal">{formatDateTime(m.movement_date || m.created_at)}</span>
                    </div>
                    <p className="text-slate-600 mt-1">{m.notes || '—'}</p>
                    <p className="text-[11px] text-slate-400 mt-1">
                      Performed by {m.performer?.name || 'System'}
                      {m.to?.user?.name ? ` • Assigned to ${m.to.user.name}` : ''}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center gap-2">
            <Shield className="w-4 h-4 text-emerald-600" />
            <CardTitle>Entity Audit Trail</CardTitle>
          </CardHeader>
          <CardContent>
            {auditLogs.length === 0 ? (
              <p className="text-xs text-slate-400">No activity logs recorded for this asset.</p>
            ) : (
              <div className="space-y-3">
                {auditLogs.map((log) => (
                  <div key={log.id} className="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                    <div className="flex items-center justify-between">
                      <Badge variant="purple" className="capitalize">
                        {log.event || log.description}
                      </Badge>
                      <span className="text-slate-400">{log.timestamp}</span>
                    </div>
                    <p className="text-slate-700 mt-1.5">{log.description}</p>
                    <p className="text-[11px] text-slate-400 mt-1">By {log.causer?.name || 'System'}</p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Lifecycle Modal */}
      <Modal
        open={Boolean(actionType)}
        onClose={() => setActionType(null)}
        title={
          actionType === 'checkout'
            ? `Checkout Asset ${asset.asset_tag}`
            : actionType === 'checkin'
            ? `Checkin Asset ${asset.asset_tag}`
            : `Transfer Asset ${asset.asset_tag}`
        }
        size="md"
      >
        <form onSubmit={handleLifecycleSubmit} className="space-y-4">
          {actionErr && (
            <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">{actionErr}</div>
          )}

          {actionType === 'checkout' && (
            <Select
              label="Assign To User"
              value={actionForm.user_id}
              onChange={(e) => setActionForm({ ...actionForm, user_id: e.target.value })}
              required
            >
              <option value="">Select employee...</option>
              {users.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.name} ({u.email})
                </option>
              ))}
            </Select>
          )}

          <Select
            label="Location"
            value={actionForm.location_id}
            onChange={(e) => setActionForm({ ...actionForm, location_id: e.target.value })}
          >
            <option value="">Keep current location</option>
            {locations.map((l) => (
              <option key={l.id} value={l.id}>
                {l.name}
              </option>
            ))}
          </Select>

          {(actionType === 'checkout' || actionType === 'transfer') && (
            <Select
              label="Department"
              value={actionForm.department_id}
              onChange={(e) => setActionForm({ ...actionForm, department_id: e.target.value })}
            >
              <option value="">Keep current department</option>
              {departments.map((d) => (
                <option key={d.id} value={d.id}>
                  {d.name}
                </option>
              ))}
            </Select>
          )}

          {actionType === 'checkin' && (
            <Select
              label="Return Status"
              value={actionForm.status_id}
              onChange={(e) => setActionForm({ ...actionForm, status_id: e.target.value })}
            >
              <option value="">Default (Available / In Storage)</option>
              {statuses.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </Select>
          )}

          {actionType !== 'transfer' && (
            <Select
              label="Condition"
              value={actionForm.condition}
              onChange={(e) => setActionForm({ ...actionForm, condition: e.target.value })}
            >
              <option value="new">New</option>
              <option value="good">Good</option>
              <option value="fair">Fair</option>
              <option value="poor">Poor</option>
              <option value="damaged">Damaged</option>
            </Select>
          )}

          <Textarea
            label="Notes"
            rows={2}
            value={actionForm.notes}
            onChange={(e) => setActionForm({ ...actionForm, notes: e.target.value })}
          />

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={() => setActionType(null)}>
              Cancel
            </Button>
            <Button type="submit" disabled={actionSaving}>
              {actionSaving ? 'Processing...' : 'Confirm'}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
