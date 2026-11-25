import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { type ColumnDef } from '@tanstack/react-table';
import { Settings, Plus, Pencil, Trash2, Search } from 'lucide-react';
import api from '../services/api';
import { DataTable } from '../components/ui/DataTable';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { Textarea } from '../components/ui/Textarea';
import { Modal } from '../components/ui/Modal';
import type {
  AssetModel,
  Category,
  Company,
  Department,
  Location,
  Manufacturer,
  Supplier,
} from '../types';

type EntityTab = 'categories' | 'manufacturers' | 'asset-models' | 'locations' | 'departments' | 'suppliers';

const TABS: Array<{ id: EntityTab; label: string }> = [
  { id: 'categories', label: 'Categories' },
  { id: 'manufacturers', label: 'Manufacturers' },
  { id: 'asset-models', label: 'Asset Models' },
  { id: 'locations', label: 'Locations' },
  { id: 'departments', label: 'Departments' },
  { id: 'suppliers', label: 'Suppliers' },
];

type AnyRecord = Category | Manufacturer | AssetModel | Location | Department | Supplier;

export const SettingsPage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<EntityTab>('categories');
  const [items, setItems] = useState<AnyRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  // Shared relational options
  const [categories, setCategories] = useState<Category[]>([]);
  const [manufacturers, setManufacturers] = useState<Manufacturer[]>([]);
  const [companies, setCompanies] = useState<Company[]>([]);

  // Modal state
  const [modalOpen, setModalOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<AnyRecord | null>(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState<Record<string, string>>({});

  const loadLookups = useCallback(async () => {
    const [catRes, manRes, compRes] = await Promise.allSettled([
      api.get('/categories'),
      api.get('/manufacturers'),
      api.get('/companies'),
    ]);
    if (catRes.status === 'fulfilled') setCategories(catRes.value.data.data || []);
    if (manRes.status === 'fulfilled') setManufacturers(manRes.value.data.data || []);
    if (compRes.status === 'fulfilled') setCompanies(compRes.value.data.data || []);
  }, []);

  const fetchItems = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get(`/${activeTab}`, {
        params: { search: search || undefined },
      });
      setItems(res.data.data || []);
    } finally {
      setLoading(false);
    }
  }, [activeTab, search]);

  useEffect(() => {
    let active = true;
    Promise.allSettled([
      api.get('/categories'),
      api.get('/manufacturers'),
      api.get('/companies'),
    ]).then(([catRes, manRes, compRes]) => {
      if (!active) return;
      if (catRes.status === 'fulfilled') setCategories(catRes.value.data.data || []);
      if (manRes.status === 'fulfilled') setManufacturers(manRes.value.data.data || []);
      if (compRes.status === 'fulfilled') setCompanies(compRes.value.data.data || []);
    });
    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => fetchItems(), 200);
    return () => clearTimeout(timer);
  }, [fetchItems]);

  const openCreate = () => {
    setEditingItem(null);
    setError(null);
    setForm({
      name: '',
      code: '',
      description: '',
      status: 'active',
      website: '',
      support_phone: '',
      support_email: '',
      manufacturer_id: manufacturers[0]?.id || '',
      category_id: '',
      model_number: '',
      company_id: companies[0]?.id || '',
      building: '',
      floor: '',
      room: '',
      address: '',
      contact_person: '',
      email: '',
      phone: '',
    });
    setModalOpen(true);
  };

  const openEdit = (item: AnyRecord) => {
    setEditingItem(item);
    setError(null);
    const rec = item as unknown as Record<string, unknown>;
    setForm({
      name: String(rec.name || ''),
      code: String(rec.code || ''),
      description: String(rec.description || ''),
      status: String(rec.status || 'active'),
      website: String(rec.website || ''),
      support_phone: String(rec.support_phone || ''),
      support_email: String(rec.support_email || ''),
      manufacturer_id: String((rec.manufacturer as { id?: string } | undefined)?.id || ''),
      category_id: String((rec.category as { id?: string } | undefined)?.id || ''),
      model_number: String(rec.model_number || ''),
      company_id: String((rec.company as { id?: string } | undefined)?.id || ''),
      building: String(rec.building || ''),
      floor: String(rec.floor || ''),
      room: String(rec.room || ''),
      address: String(rec.address || ''),
      contact_person: String(rec.contact_person || ''),
      email: String(rec.email || ''),
      phone: String(rec.phone || ''),
    });
    setModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    const payload: Record<string, string | null> = {};
    Object.entries(form).forEach(([k, v]) => {
      payload[k] = v === '' ? null : v;
    });

    try {
      if (editingItem) {
        await api.put(`/${activeTab}/${editingItem.id}`, payload);
      } else {
        await api.post(`/${activeTab}`, payload);
      }
      setModalOpen(false);
      fetchItems();
      loadLookups();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      const firstErr = axiosErr.response?.data?.errors
        ? Object.values(axiosErr.response.data.errors)[0]?.[0]
        : null;
      setError(firstErr || axiosErr.response?.data?.message || 'Failed to save record.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = useCallback(
    async (item: AnyRecord) => {
      if (!window.confirm(`Delete "${item.name}"?`)) return;
      await api.delete(`/${activeTab}/${item.id}`);
      fetchItems();
      loadLookups();
    },
    [activeTab, fetchItems, loadLookups]
  );

  const columns = useMemo<ColumnDef<AnyRecord, unknown>[]>(() => {
    const base: ColumnDef<AnyRecord, unknown>[] = [
      { accessorKey: 'name', header: 'Name' },
    ];

    if (activeTab === 'asset-models') {
      base.push(
        { accessorKey: 'model_number', header: 'Model #' },
        {
          id: 'manufacturer',
          accessorFn: (r) => (r as AssetModel).manufacturer?.name || '—',
          header: 'Manufacturer',
        },
        {
          id: 'category',
          accessorFn: (r) => (r as AssetModel).category?.name || '—',
          header: 'Category',
        }
      );
    } else if (activeTab === 'locations') {
      base.push(
        { accessorKey: 'code', header: 'Code' },
        { id: 'company', accessorFn: (r) => (r as Location).company?.name || '—', header: 'Company' },
        { accessorKey: 'building', header: 'Building' }
      );
    } else if (activeTab === 'departments') {
      base.push(
        { accessorKey: 'code', header: 'Code' },
        { id: 'company', accessorFn: (r) => (r as Department).company?.name || '—', header: 'Company' }
      );
    } else if (activeTab === 'suppliers') {
      base.push(
        { accessorKey: 'code', header: 'Code' },
        { accessorKey: 'contact_person', header: 'Contact Person' },
        { accessorKey: 'email', header: 'Email' },
        { accessorKey: 'phone', header: 'Phone' }
      );
    } else if (activeTab === 'manufacturers') {
      base.push(
        { accessorKey: 'code', header: 'Code' },
        { accessorKey: 'website', header: 'Website' },
        { accessorKey: 'support_email', header: 'Support Email' }
      );
    } else {
      base.push({ accessorKey: 'code', header: 'Code' }, { accessorKey: 'description', header: 'Description' });
    }

    base.push(
      {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => (
          <Badge variant={row.original.status === 'active' ? 'success' : 'default'} className="capitalize">
            {row.original.status || 'active'}
          </Badge>
        ),
      },
      {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        cell: ({ row }) => (
          <div className="flex items-center gap-1">
            <button
              type="button"
              onClick={() => openEdit(row.original)}
              className="p-1.5 rounded-md text-slate-500 hover:text-amber-600 hover:bg-amber-50"
              title="Edit"
            >
              <Pencil className="w-4 h-4" />
            </button>
            <button
              type="button"
              onClick={() => handleDelete(row.original)}
              className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50"
              title="Delete"
            >
              <Trash2 className="w-4 h-4" />
            </button>
          </div>
        ),
      }
    );

    return base;
  }, [activeTab, handleDelete]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Settings className="w-6 h-6 text-indigo-600" />
            System Settings &amp; Reference Data
          </h1>
          <p className="text-sm text-slate-500">
            Configure tenant-scoped categories, manufacturers, models, locations, departments, and suppliers.
          </p>
        </div>
        <Button size="sm" onClick={openCreate}>
          <Plus className="w-4 h-4" /> Add {TABS.find((t) => t.id === activeTab)?.label.slice(0, -1)}
        </Button>
      </div>

      {/* Tabs */}
      <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        {TABS.map((t) => (
          <button
            key={t.id}
            type="button"
            onClick={() => {
              setActiveTab(t.id);
              setSearch('');
            }}
            className={`px-3.5 py-2 rounded-lg text-xs font-medium transition-colors ${
              activeTab === t.id
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      <div className="max-w-sm relative">
        <Input
          placeholder={`Search ${activeTab.replace('-', ' ')}...`}
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="pl-8"
        />
        <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" />
      </div>

      <DataTable
        columns={columns}
        data={items}
        loading={loading}
        emptyMessage={`No ${activeTab.replace('-', ' ')} configured yet.`}
      />

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={`${editingItem ? 'Edit' : 'Create'} ${TABS.find((t) => t.id === activeTab)?.label}`}
        size="lg"
      >
        <form onSubmit={handleSave} className="space-y-4">
          {error && <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">{error}</div>}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input
              label="Name"
              value={form.name || ''}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              required
            />

            {activeTab !== 'asset-models' && (
              <Input
                label="Code"
                value={form.code || ''}
                onChange={(e) => setForm({ ...form, code: e.target.value })}
                required={activeTab === 'departments'}
              />
            )}

            {activeTab === 'asset-models' && (
              <>
                <Input
                  label="Model Number"
                  value={form.model_number || ''}
                  onChange={(e) => setForm({ ...form, model_number: e.target.value })}
                />
                <Select
                  label="Manufacturer"
                  value={form.manufacturer_id || ''}
                  onChange={(e) => setForm({ ...form, manufacturer_id: e.target.value })}
                  required
                >
                  <option value="">Select manufacturer...</option>
                  {manufacturers.map((m) => (
                    <option key={m.id} value={m.id}>
                      {m.name}
                    </option>
                  ))}
                </Select>
                <Select
                  label="Category"
                  value={form.category_id || ''}
                  onChange={(e) => setForm({ ...form, category_id: e.target.value })}
                >
                  <option value="">Optional category...</option>
                  {categories.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </Select>
              </>
            )}

            {activeTab === 'manufacturers' && (
              <>
                <Input
                  label="Website"
                  type="url"
                  value={form.website || ''}
                  onChange={(e) => setForm({ ...form, website: e.target.value })}
                  placeholder="https://www.hp.com"
                />
                <Input
                  label="Support Phone"
                  value={form.support_phone || ''}
                  onChange={(e) => setForm({ ...form, support_phone: e.target.value })}
                />
                <Input
                  label="Support Email"
                  type="email"
                  value={form.support_email || ''}
                  onChange={(e) => setForm({ ...form, support_email: e.target.value })}
                />
              </>
            )}

            {(activeTab === 'locations' || activeTab === 'departments') && (
              <Select
                label="Company"
                value={form.company_id || ''}
                onChange={(e) => setForm({ ...form, company_id: e.target.value })}
                required={activeTab === 'departments'}
              >
                <option value="">Select company...</option>
                {companies.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </Select>
            )}

            {activeTab === 'locations' && (
              <>
                <Input
                  label="Building"
                  value={form.building || ''}
                  onChange={(e) => setForm({ ...form, building: e.target.value })}
                />
                <Input
                  label="Floor"
                  value={form.floor || ''}
                  onChange={(e) => setForm({ ...form, floor: e.target.value })}
                />
                <Input
                  label="Room"
                  value={form.room || ''}
                  onChange={(e) => setForm({ ...form, room: e.target.value })}
                />
                <Input
                  label="Address"
                  value={form.address || ''}
                  onChange={(e) => setForm({ ...form, address: e.target.value })}
                />
              </>
            )}

            {activeTab === 'suppliers' && (
              <>
                <Input
                  label="Contact Person"
                  value={form.contact_person || ''}
                  onChange={(e) => setForm({ ...form, contact_person: e.target.value })}
                />
                <Input
                  label="Email"
                  type="email"
                  value={form.email || ''}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                />
                <Input
                  label="Phone"
                  value={form.phone || ''}
                  onChange={(e) => setForm({ ...form, phone: e.target.value })}
                />
                <Input
                  label="Website"
                  type="url"
                  value={form.website || ''}
                  onChange={(e) => setForm({ ...form, website: e.target.value })}
                />
              </>
            )}

            <Select
              label="Status"
              value={form.status || 'active'}
              onChange={(e) => setForm({ ...form, status: e.target.value })}
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </Select>
          </div>

          {(activeTab === 'categories' || activeTab === 'asset-models' || activeTab === 'departments') && (
            <Textarea
              label="Description"
              rows={2}
              value={form.description || ''}
              onChange={(e) => setForm({ ...form, description: e.target.value })}
            />
          )}

          {activeTab === 'suppliers' && (
            <Textarea
              label="Address"
              rows={2}
              value={form.address || ''}
              onChange={(e) => setForm({ ...form, address: e.target.value })}
            />
          )}

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
