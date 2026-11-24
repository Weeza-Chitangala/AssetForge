import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { type ColumnDef } from '@tanstack/react-table';
import { History, Shield, Eye, Search, RotateCcw } from 'lucide-react';
import api from '../services/api';
import { DataTable } from '../components/ui/DataTable';
import { Badge, type BadgeVariant } from '../components/ui/Badge';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { formatDateTime } from '../lib/utils';
import type { AuditLog, PaginationMeta } from '../types';

export const AuditLogsPage: React.FC = () => {
  const [tab, setTab] = useState<'activity' | 'auth'>('activity');
  const [logs, setLogs] = useState<AuditLog[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [loading, setLoading] = useState(true);

  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [eventFilter, setEventFilter] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [selectedLog, setSelectedLog] = useState<AuditLog | null>(null);

  const fetchLogs = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get('/audit-logs', {
        params: {
          page,
          per_page: 15,
          log_name: tab === 'auth' ? 'auth' : undefined,
          event: eventFilter || undefined,
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
          search: search || undefined,
        },
      });
      setLogs(res.data.data || []);
      setMeta(res.data.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 });
    } finally {
      setLoading(false);
    }
  }, [page, tab, eventFilter, dateFrom, dateTo, search]);

  useEffect(() => {
    const timer = setTimeout(() => fetchLogs(), 200);
    return () => clearTimeout(timer);
  }, [fetchLogs]);

  const eventVariant = (ev: string | null): BadgeVariant => {
    switch (ev) {
      case 'created':
      case 'login':
        return 'success';
      case 'updated':
        return 'info';
      case 'deleted':
        return 'danger';
      case 'logout':
        return 'warning';
      default:
        return 'default';
    }
  };

  const columns = useMemo<ColumnDef<AuditLog, unknown>[]>(
    () => [
      {
        accessorKey: 'created_at',
        header: 'Timestamp',
        cell: ({ row }) => (
          <div className="text-xs">
            <p className="font-medium text-slate-800">{formatDateTime(row.original.created_at)}</p>
            <p className="text-slate-400">{row.original.timestamp}</p>
          </div>
        ),
      },
      {
        accessorKey: 'event',
        header: 'Event',
        cell: ({ row }) => (
          <Badge variant={eventVariant(row.original.event)} className="capitalize">
            {row.original.event || row.original.log_name}
          </Badge>
        ),
      },
      {
        accessorKey: 'subject_type',
        header: 'Module / Subject',
        cell: ({ row }) => (
          <div className="text-xs">
            <p className="font-medium text-slate-800">{row.original.subject_type || row.original.log_name}</p>
            {row.original.subject_id && <p className="font-mono text-[11px] text-slate-400">{row.original.subject_id}</p>}
          </div>
        ),
      },
      {
        accessorKey: 'description',
        header: 'Description',
        cell: ({ row }) => <span className="text-xs text-slate-700">{row.original.description}</span>,
      },
      {
        id: 'causer',
        accessorFn: (row) => row.causer?.name || 'System',
        header: 'Actor',
        cell: ({ row }) => (
          <div className="text-xs">
            <p className="font-medium text-slate-800">{row.original.causer?.name || 'System'}</p>
            <p className="text-slate-400">{row.original.causer?.email || ''}</p>
          </div>
        ),
      },
      {
        id: 'details',
        header: 'Payload',
        enableSorting: false,
        cell: ({ row }) => (
          <Button variant="ghost" size="sm" onClick={() => setSelectedLog(row.original)}>
            <Eye className="w-3.5 h-3.5" /> Inspect
          </Button>
        ),
      },
    ],
    []
  );

  const resetFilters = () => {
    setSearch('');
    setEventFilter('');
    setDateFrom('');
    setDateTo('');
    setPage(1);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <History className="w-6 h-6 text-indigo-600" />
          Audit Trails &amp; Security Logs
        </h1>
        <p className="text-sm text-slate-500">
          Inspect tenant-isolated entity mutations and user authentication events.
        </p>
      </div>

      {/* Tabs */}
      <div className="flex gap-2 border-b border-slate-200 pb-3">
        <button
          type="button"
          onClick={() => {
            setTab('activity');
            setEventFilter('');
            setPage(1);
          }}
          className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
            tab === 'activity'
              ? 'bg-indigo-600 text-white'
              : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
          }`}
        >
          <History className="w-4 h-4" /> Activity Trail
        </button>
        <button
          type="button"
          onClick={() => {
            setTab('auth');
            setEventFilter('');
            setPage(1);
          }}
          className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
            tab === 'auth'
              ? 'bg-indigo-600 text-white'
              : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
          }`}
        >
          <Shield className="w-4 h-4" /> Login &amp; Auth Logs
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div className="relative">
          <Input
            label="Search"
            placeholder="Event, description..."
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
          label="Event"
          value={eventFilter}
          onChange={(e) => {
            setEventFilter(e.target.value);
            setPage(1);
          }}
        >
          <option value="">All Events</option>
          {tab === 'auth' ? (
            <>
              <option value="login">Login</option>
              <option value="logout">Logout</option>
            </>
          ) : (
            <>
              <option value="created">Created</option>
              <option value="updated">Updated</option>
              <option value="deleted">Deleted</option>
              <option value="login">Login</option>
              <option value="logout">Logout</option>
            </>
          )}
        </Select>

        <Input
          label="Date From"
          type="date"
          value={dateFrom}
          onChange={(e) => {
            setDateFrom(e.target.value);
            setPage(1);
          }}
        />
        <Input
          label="Date To"
          type="date"
          value={dateTo}
          onChange={(e) => {
            setDateTo(e.target.value);
            setPage(1);
          }}
        />

        <Button variant="ghost" onClick={resetFilters}>
          <RotateCcw className="w-3.5 h-3.5" /> Reset
        </Button>
      </div>

      <DataTable
        columns={columns}
        data={logs}
        loading={loading}
        emptyMessage="No audit logs found for the selected criteria."
        serverPagination={{
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
          onPageChange: (p) => setPage(p),
        }}
      />

      <Modal
        open={Boolean(selectedLog)}
        onClose={() => setSelectedLog(null)}
        title="Audit Event Properties"
        description={selectedLog ? `${selectedLog.description} (${formatDateTime(selectedLog.created_at)})` : ''}
        size="lg"
      >
        {selectedLog && (
          <pre className="bg-slate-900 text-slate-100 p-4 rounded-xl text-xs overflow-x-auto">
            {JSON.stringify(selectedLog.properties, null, 2)}
          </pre>
        )}
      </Modal>
    </div>
  );
};
