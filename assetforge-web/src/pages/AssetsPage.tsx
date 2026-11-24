import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { type ColumnDef } from '@tanstack/react-table';
import {
  Plus,
  Upload,
  Download,
  Eye,
  Pencil,
  Trash2,
  Search,
  RotateCcw,
} from 'lucide-react';
import api, { downloadBlob } from '../services/api';
import { useAuth } from '../context/AuthContext';
import { DataTable } from '../components/ui/DataTable';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { AssetFormModal } from '../components/assets/AssetFormModal';
import { BulkImportModal } from '../components/assets/BulkImportModal';
import { formatCurrency } from '../lib/utils';
import type {
  AssetModel,
  AssetStatus,
  Category,
  Department,
  EquipmentAsset,
  Location,
  Manufacturer,
  PaginationMeta,
} from '../types';

export const AssetsPage: React.FC = () => {
  const { hasPermission } = useAuth();
  const [assets, setAssets] = useState<EquipmentAsset[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [loading, setLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [manufacturerId, setManufacturerId] = useState('');
  const [statusId, setStatusId] = useState('');
  const [locationId, setLocationId] = useState('');
  const [page, setPage] = useState(1);

  // Reference data
  const [categories, setCategories] = useState<Category[]>([]);
  const [manufacturers, setManufacturers] = useState<Manufacturer[]>([]);
  const [models, setModels] = useState<AssetModel[]>([]);
  const [locations, setLocations] = useState<Location[]>([]);
  const [statuses, setStatuses] = useState<AssetStatus[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);

  // Modals
  const [formOpen, setFormOpen] = useState(false);
  const [editingAsset, setEditingAsset] = useState<EquipmentAsset | null>(null);
  const [importOpen, setImportOpen] = useState(false);
  const [exporting, setExporting] = useState(false);

  const loadReferenceData = useCallback(async () => {
    try {
      const [catRes, manRes, modRes, locRes, statRes, depRes] = await Promise.allSettled([
        api.get('/categories'),
        api.get('/manufacturers'),
        api.get('/asset-models'),
        api.get('/locations'),
        api.get('/asset-statuses'),
        api.get('/departments'),
      ]);

      if (catRes.status === 'fulfilled') setCategories(catRes.value.data.data || []);
      if (manRes.status === 'fulfilled') setManufacturers(manRes.value.data.data || []);
      if (modRes.status === 'fulfilled') setModels(modRes.value.data.data || []);
      if (locRes.status === 'fulfilled') setLocations(locRes.value.data.data || []);
      if (statRes.status === 'fulfilled') setStatuses(statRes.value.data.data || []);
      if (depRes.status === 'fulfilled') setDepartments(depRes.value.data.data || []);
    } catch {
      // Non-blocking reference fetch
    }
  }, []);

  const fetchAssets = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get('/assets', {
        params: {
          page,
          per_page: 15,
          search: search || undefined,
          category_id: categoryId || undefined,
          manufacturer_id: manufacturerId || undefined,
          status_id: statusId || undefined,
          location_id: locationId || undefined,
        },
      });
      const payload = res.data.data;
      setAssets(payload.data || []);
      setMeta(payload.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 });
    } finally {
      setLoading(false);
    }
  }, [page, search, categoryId, manufacturerId, statusId, locationId]);

  useEffect(() => {
    loadReferenceData();
  }, [loadReferenceData]);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchAssets();
    }, 200);
    return () => clearTimeout(timer);
  }, [fetchAssets]);

  const handleDelete = async (asset: EquipmentAsset) => {
    if (!window.confirm(`Delete asset ${asset.asset_tag}? This performs a soft delete.`)) return;
    await api.delete(`/assets/${asset.id}`);
    fetchAssets();
  };

  const handleExport = async (format: 'xlsx' | 'csv') => {
    setExporting(true);
    try {
      await downloadBlob('/assets/export', { format }, `assetforge-assets.${format}`);
    } finally {
      setExporting(false);
    }
  };

  const resetFilters = () => {
    setSearch('');
    setCategoryId('');
    setManufacturerId('');
    setStatusId('');
    setLocationId('');
    setPage(1);
  };

  const columns = useMemo<ColumnDef<EquipmentAsset, unknown>[]>(
    () => [
      {
        accessorKey: 'asset_tag',
        header: 'Asset Tag',
        cell: ({ row }) => (
          <Link
            to={`/assets/${row.original.id}`}
            className="font-semibold text-indigo-600 hover:underline whitespace-nowrap"
          >
            {row.original.asset_tag}
          </Link>
        ),
      },
      {
        accessorKey: 'name',
        header: 'Device / Model',
        cell: ({ row }) => (
          <div>
            <p className="font-medium text-slate-800">{row.original.name || row.original.model?.name || '—'}</p>
            <p className="text-xs text-slate-500">
              {row.original.manufacturer?.name} {row.original.model?.name}
            </p>
          </div>
        ),
      },
      {
        accessorKey: 'serial_number',
        header: 'Serial #',
        cell: ({ row }) => <span className="font-mono text-xs">{row.original.serial_number}</span>,
      },
      {
        id: 'category',
        accessorFn: (row) => row.category?.name || '',
        header: 'Category',
        cell: ({ row }) => row.original.category?.name || '—',
      },
      {
        id: 'status',
        accessorFn: (row) => row.status?.name || '',
        header: 'Status',
        cell: ({ row }) =>
          row.original.status?.name ? (
            <Badge className="whitespace-nowrap">
              <span
                className="w-2 h-2 rounded-full"
                style={{ backgroundColor: row.original.status.color || '#64748b' }}
              />
              {row.original.status.name}
            </Badge>
          ) : (
            '—'
          ),
      },
      {
        id: 'location',
        accessorFn: (row) => row.location?.name || '',
        header: 'Location',
        cell: ({ row }) => row.original.location?.name || '—',
      },
      {
        id: 'assigned_to',
        accessorFn: (row) => row.assigned_to?.name || '',
        header: 'Assigned To',
        cell: ({ row }) => row.original.assigned_to?.name || <span className="text-slate-400">Unassigned</span>,
      },
      {
        id: 'purchase_cost',
        accessorFn: (row) => Number(row.procurement?.purchase_cost || 0),
        header: 'Cost',
        cell: ({ row }) => formatCurrency(row.original.procurement?.purchase_cost),
      },
      {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        cell: ({ row }) => (
          <div className="flex items-center gap-1">
            <Link
              to={`/assets/${row.original.id}`}
              className="p-1.5 rounded-md text-slate-500 hover:text-indigo-600 hover:bg-indigo-50"
              title="View Details & QR Label"
            >
              <Eye className="w-4 h-4" />
            </Link>
            {hasPermission('assets.update') && (
              <button
                type="button"
                onClick={() => {
                  setEditingAsset(row.original);
                  setFormOpen(true);
                }}
                className="p-1.5 rounded-md text-slate-500 hover:text-amber-600 hover:bg-amber-50"
                title="Edit Asset"
              >
                <Pencil className="w-4 h-4" />
              </button>
            )}
            {hasPermission('assets.delete') && (
              <button
                type="button"
                onClick={() => handleDelete(row.original)}
                className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50"
                title="Delete Asset"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            )}
          </div>
        ),
      },
    ],
    [hasPermission]
  );

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Equipment Assets</h1>
          <p className="text-sm text-slate-500">
            Manage enterprise hardware inventory, custody lifecycle, QR labels, and bulk onboarding.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Button variant="outline" size="sm" disabled={exporting} onClick={() => handleExport('csv')}>
            <Download className="w-3.5 h-3.5" /> CSV
          </Button>
          <Button variant="outline" size="sm" disabled={exporting} onClick={() => handleExport('xlsx')}>
            <Download className="w-3.5 h-3.5" /> Excel
          </Button>
          {hasPermission('assets.create') && (
            <>
              <Button variant="outline" size="sm" onClick={() => setImportOpen(true)}>
                <Upload className="w-3.5 h-3.5" /> Bulk Import
              </Button>
              <Button
                size="sm"
                onClick={() => {
                  setEditingAsset(null);
                  setFormOpen(true);
                }}
              >
                <Plus className="w-4 h-4" /> Register Asset
              </Button>
            </>
          )}
        </div>
      </div>

      {/* Filters Toolbar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
        <div className="lg:col-span-2 relative">
          <Input
            label="Search"
            placeholder="Tag, serial number, device name..."
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
            className="pl-8"
          />
          <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 bottom-2.5" />
        </div>

        <Select
          label="Category"
          value={categoryId}
          onChange={(e) => {
            setCategoryId(e.target.value);
            setPage(1);
          }}
        >
          <option value="">All Categories</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </Select>

        <Select
          label="Status"
          value={statusId}
          onChange={(e) => {
            setStatusId(e.target.value);
            setPage(1);
          }}
        >
          <option value="">All Statuses</option>
          {statuses.map((s) => (
            <option key={s.id} value={s.id}>
              {s.name}
            </option>
          ))}
        </Select>

        <Select
          label="Location"
          value={locationId}
          onChange={(e) => {
            setLocationId(e.target.value);
            setPage(1);
          }}
        >
          <option value="">All Locations</option>
          {locations.map((l) => (
            <option key={l.id} value={l.id}>
              {l.name}
            </option>
          ))}
        </Select>

        <Button variant="ghost" size="md" onClick={resetFilters} className="w-full">
          <RotateCcw className="w-3.5 h-3.5" /> Reset
        </Button>
      </div>

      <DataTable
        columns={columns}
        data={assets}
        loading={loading}
        emptyMessage="No equipment assets match the current filters."
        serverPagination={{
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
          onPageChange: (p) => setPage(p),
        }}
      />

      <AssetFormModal
        open={formOpen}
        onClose={() => setFormOpen(false)}
        asset={editingAsset}
        onSaved={fetchAssets}
        categories={categories}
        manufacturers={manufacturers}
        models={models}
        locations={locations}
        statuses={statuses}
        departments={departments}
      />

      <BulkImportModal open={importOpen} onClose={() => setImportOpen(false)} onImported={fetchAssets} />
    </div>
  );
};
