import React, { useEffect, useState } from 'react';
import api from '../../services/api';
import { Modal } from '../ui/Modal';
import { Input } from '../ui/Input';
import { Select } from '../ui/Select';
import { Textarea } from '../ui/Textarea';
import { Button } from '../ui/Button';
import type {
  AssetModel,
  AssetStatus,
  Category,
  Department,
  EquipmentAsset,
  Location,
  Manufacturer,
} from '../../types';

interface AssetFormModalProps {
  open: boolean;
  onClose: () => void;
  asset?: EquipmentAsset | null;
  onSaved: () => void;
  categories: Category[];
  manufacturers: Manufacturer[];
  models: AssetModel[];
  locations: Location[];
  statuses: AssetStatus[];
  departments: Department[];
}

export const AssetFormModal: React.FC<AssetFormModalProps> = ({
  open,
  onClose,
  asset,
  onSaved,
  categories,
  manufacturers,
  models,
  locations,
  statuses,
  departments,
}) => {
  const isEdit = Boolean(asset);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [form, setForm] = useState({
    asset_tag: '',
    serial_number: '',
    name: '',
    category_id: '',
    manufacturer_id: '',
    asset_model_id: '',
    location_id: '',
    status_id: '',
    department_id: '',
    operating_system: '',
    processor: '',
    ram: '',
    storage: '',
    mac_address: '',
    ip_address: '',
    supplier: '',
    order_number: '',
    purchase_date: '',
    purchase_cost: '',
    warranty_expiry_date: '',
    condition: 'good',
    notes: '',
  });

  useEffect(() => {
    if (asset) {
      setForm({
        asset_tag: asset.asset_tag || '',
        serial_number: asset.serial_number || '',
        name: asset.name || '',
        category_id: asset.category?.id || '',
        manufacturer_id: asset.manufacturer?.id || '',
        asset_model_id: asset.model?.id || '',
        location_id: asset.location?.id || '',
        status_id: asset.status?.id || '',
        department_id: '',
        operating_system: asset.specs?.operating_system || '',
        processor: asset.specs?.processor || '',
        ram: asset.specs?.ram || '',
        storage: asset.specs?.storage || '',
        mac_address: asset.specs?.mac_address || '',
        ip_address: asset.specs?.ip_address || '',
        supplier: asset.procurement?.supplier || '',
        order_number: asset.procurement?.order_number || '',
        purchase_date: asset.procurement?.purchase_date || '',
        purchase_cost: asset.procurement?.purchase_cost ? String(asset.procurement.purchase_cost) : '',
        warranty_expiry_date: asset.procurement?.warranty_expiry_date || '',
        condition: asset.condition || 'good',
        notes: asset.notes || '',
      });
    } else {
      setForm({
        asset_tag: '',
        serial_number: '',
        name: '',
        category_id: '',
        manufacturer_id: '',
        asset_model_id: '',
        location_id: '',
        status_id: statuses.find((s) => s.code === 'AVAILABLE')?.id || '',
        department_id: '',
        operating_system: '',
        processor: '',
        ram: '',
        storage: '',
        mac_address: '',
        ip_address: '',
        supplier: '',
        order_number: '',
        purchase_date: '',
        purchase_cost: '',
        warranty_expiry_date: '',
        condition: 'good',
        notes: '',
      });
    }
    setError(null);
  }, [asset, open, statuses]);

  const filteredModels = form.manufacturer_id
    ? models.filter((m) => m.manufacturer?.id === form.manufacturer_id)
    : models;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    const payload: Record<string, unknown> = {};
    Object.entries(form).forEach(([key, val]) => {
      payload[key] = val === '' ? null : val;
    });

    try {
      if (isEdit && asset) {
        await api.put(`/assets/${asset.id}`, payload);
      } else {
        await api.post('/assets', payload);
      }
      onSaved();
      onClose();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      const firstErr = axiosErr.response?.data?.errors
        ? Object.values(axiosErr.response.data.errors)[0]?.[0]
        : null;
      setError(firstErr || axiosErr.response?.data?.message || 'Failed to save asset.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={isEdit ? `Edit Asset: ${asset?.asset_tag}` : 'Register Equipment Asset'}
      description="Capture hardware identifiers, specifications, organizational placement, and procurement details."
      size="xl"
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        {error && (
          <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">{error}</div>
        )}

        <div>
          <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Core Identifiers</h4>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Input
              label="Asset Tag"
              name="asset_tag"
              placeholder="AF-2026-000105"
              value={form.asset_tag}
              onChange={(e) => setForm({ ...form, asset_tag: e.target.value })}
              required
            />
            <Input
              label="Serial Number"
              name="serial_number"
              placeholder="5CD1234XYZ"
              value={form.serial_number}
              onChange={(e) => setForm({ ...form, serial_number: e.target.value })}
              required
            />
            <Input
              label="Hostname / Display Name"
              name="name"
              placeholder="HQ-ENG-LT01"
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
            />
          </div>
        </div>

        <div>
          <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
            Classification &amp; Placement
          </h4>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Select
              label="Category"
              value={form.category_id}
              onChange={(e) => setForm({ ...form, category_id: e.target.value })}
            >
              <option value="">Select category...</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </Select>

            <Select
              label="Manufacturer"
              value={form.manufacturer_id}
              onChange={(e) => setForm({ ...form, manufacturer_id: e.target.value, asset_model_id: '' })}
            >
              <option value="">Select manufacturer...</option>
              {manufacturers.map((m) => (
                <option key={m.id} value={m.id}>
                  {m.name}
                </option>
              ))}
            </Select>

            <Select
              label="Model"
              value={form.asset_model_id}
              onChange={(e) => setForm({ ...form, asset_model_id: e.target.value })}
            >
              <option value="">Select model...</option>
              {filteredModels.map((m) => (
                <option key={m.id} value={m.id}>
                  {m.name}
                </option>
              ))}
            </Select>

            <Select
              label="Location"
              value={form.location_id}
              onChange={(e) => setForm({ ...form, location_id: e.target.value })}
            >
              <option value="">Select location...</option>
              {locations.map((l) => (
                <option key={l.id} value={l.id}>
                  {l.name}
                </option>
              ))}
            </Select>

            <Select
              label="Status"
              value={form.status_id}
              onChange={(e) => setForm({ ...form, status_id: e.target.value })}
            >
              <option value="">Select status...</option>
              {statuses.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </Select>

            <Select
              label="Department"
              value={form.department_id}
              onChange={(e) => setForm({ ...form, department_id: e.target.value })}
            >
              <option value="">Select department...</option>
              {departments.map((d) => (
                <option key={d.id} value={d.id}>
                  {d.name}
                </option>
              ))}
            </Select>
          </div>
        </div>

        <div>
          <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
            Hardware Specifications
          </h4>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Input
              label="Operating System"
              value={form.operating_system}
              onChange={(e) => setForm({ ...form, operating_system: e.target.value })}
              placeholder="Windows 11 Pro"
            />
            <Input
              label="Processor"
              value={form.processor}
              onChange={(e) => setForm({ ...form, processor: e.target.value })}
              placeholder="Intel Core i7-1365U"
            />
            <Input
              label="RAM"
              value={form.ram}
              onChange={(e) => setForm({ ...form, ram: e.target.value })}
              placeholder="16GB DDR5"
            />
            <Input
              label="Storage"
              value={form.storage}
              onChange={(e) => setForm({ ...form, storage: e.target.value })}
              placeholder="512GB NVMe SSD"
            />
            <Input
              label="MAC Address"
              value={form.mac_address}
              onChange={(e) => setForm({ ...form, mac_address: e.target.value })}
              placeholder="00:1A:2B:3C:4D:5E"
            />
            <Input
              label="IP Address"
              value={form.ip_address}
              onChange={(e) => setForm({ ...form, ip_address: e.target.value })}
              placeholder="10.0.10.25"
            />
          </div>
        </div>

        <div>
          <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
            Procurement &amp; Condition
          </h4>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Input
              label="Supplier"
              value={form.supplier}
              onChange={(e) => setForm({ ...form, supplier: e.target.value })}
            />
            <Input
              label="Order / PO Number"
              value={form.order_number}
              onChange={(e) => setForm({ ...form, order_number: e.target.value })}
            />
            <Input
              label="Purchase Cost (USD)"
              type="number"
              step="0.01"
              min="0"
              value={form.purchase_cost}
              onChange={(e) => setForm({ ...form, purchase_cost: e.target.value })}
            />
            <Input
              label="Purchase Date"
              type="date"
              value={form.purchase_date}
              onChange={(e) => setForm({ ...form, purchase_date: e.target.value })}
            />
            <Input
              label="Warranty Expiry Date"
              type="date"
              value={form.warranty_expiry_date}
              onChange={(e) => setForm({ ...form, warranty_expiry_date: e.target.value })}
            />
            <Select
              label="Condition"
              value={form.condition}
              onChange={(e) => setForm({ ...form, condition: e.target.value })}
            >
              <option value="new">New</option>
              <option value="good">Good</option>
              <option value="fair">Fair</option>
              <option value="poor">Poor</option>
              <option value="damaged">Damaged</option>
            </Select>
          </div>
        </div>

        <Textarea
          label="Notes"
          rows={2}
          value={form.notes}
          onChange={(e) => setForm({ ...form, notes: e.target.value })}
        />

        <div className="flex justify-end gap-3 pt-2 border-t border-slate-100">
          <Button type="button" variant="outline" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" disabled={saving}>
            {saving ? 'Saving...' : isEdit ? 'Update Asset' : 'Register Asset'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
