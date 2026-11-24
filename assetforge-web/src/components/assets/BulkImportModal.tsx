import React, { useState } from 'react';
import { Upload, Download, CheckCircle2, AlertCircle } from 'lucide-react';
import api from '../../services/api';
import { Modal } from '../ui/Modal';
import { Button } from '../ui/Button';

interface BulkImportModalProps {
  open: boolean;
  onClose: () => void;
  onImported: () => void;
}

export const BulkImportModal: React.FC<BulkImportModalProps> = ({ open, onClose, onImported }) => {
  const [file, setFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  const downloadTemplate = () => {
    const headers =
      'asset_tag,serial_number,name,category,manufacturer,model,location,purchase_cost,purchase_date,warranty_expiry_date,processor,ram,storage,operating_system,condition,notes';
    const sampleRow =
      'AF-2026-000201,SN-DEMO-201,ENG-LT-201,Laptops,HP,EliteBook 840 G8,Lusaka HQ - Main Building,1450.00,2026-11-01,2029-11-01,Intel Core i7,16GB,512GB NVMe,Windows 11 Pro,new,Bulk onboarding sample';
    const blob = new Blob([`${headers}\n${sampleRow}\n`], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'assetforge-import-template.csv';
    a.click();
    URL.revokeObjectURL(url);
  };

  const handleUpload = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) {
      setError('Please choose a .csv or .xlsx file.');
      return;
    }

    setUploading(true);
    setError(null);
    setSuccessMsg(null);

    const formData = new FormData();
    formData.append('file', file);

    try {
      const res = await api.post('/assets/import', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      setSuccessMsg(res.data.message || 'Assets imported successfully.');
      setFile(null);
      onImported();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } };
      setError(axiosErr.response?.data?.message || 'Import failed. Verify spreadsheet columns.');
    } finally {
      setUploading(false);
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Bulk Import Equipment Assets"
      description="Upload an Excel (.xlsx) or CSV spreadsheet. Required columns: serial_number, category, manufacturer, model."
      size="md"
    >
      <form onSubmit={handleUpload} className="space-y-4">
        <div className="p-3 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-between gap-3">
          <span className="text-xs text-indigo-800">Need the expected column headers?</span>
          <Button type="button" variant="outline" size="sm" onClick={downloadTemplate}>
            <Download className="w-3.5 h-3.5" /> Download CSV Template
          </Button>
        </div>

        {error && (
          <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2">
            <AlertCircle className="w-4 h-4 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        {successMsg && (
          <div className="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-700 flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 shrink-0" />
            <span>{successMsg}</span>
          </div>
        )}

        <label className="block border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 transition-colors">
          <Upload className="w-8 h-8 text-slate-400 mx-auto mb-2" />
          <p className="text-sm font-medium text-slate-700">
            {file ? file.name : 'Click to select .xlsx or .csv file'}
          </p>
          <p className="text-xs text-slate-400 mt-1">Maximum file size: 10 MB</p>
          <input
            type="file"
            accept=".xlsx,.csv,.txt"
            className="hidden"
            onChange={(e) => setFile(e.target.files?.[0] || null)}
          />
        </label>

        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose}>
            Close
          </Button>
          <Button type="submit" disabled={uploading || !file}>
            {uploading ? 'Importing...' : 'Start Import'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
