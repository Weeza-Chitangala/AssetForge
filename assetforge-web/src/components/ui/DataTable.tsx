import { useState } from 'react';
import {
  flexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
  type ColumnDef,
  type SortingState,
} from '@tanstack/react-table';
import { ArrowUpDown, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from './Button';

interface ServerPagination {
  currentPage: number;
  lastPage: number;
  total: number;
  perPage: number;
  onPageChange: (page: number) => void;
}

interface DataTableProps<TData> {
  columns: ColumnDef<TData, unknown>[];
  data: TData[];
  loading?: boolean;
  emptyMessage?: string;
  serverPagination?: ServerPagination;
  pageSize?: number;
}

export function DataTable<TData>({
  columns,
  data,
  loading = false,
  emptyMessage = 'No records found.',
  serverPagination,
  pageSize = 10,
}: DataTableProps<TData>) {
  const [sorting, setSorting] = useState<SortingState>([]);

  const table = useReactTable({
    data,
    columns,
    state: { sorting },
    onSortingChange: setSorting,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    ...(serverPagination
      ? {}
      : {
          getPaginationRowModel: getPaginationRowModel(),
          initialState: { pagination: { pageSize } },
        }),
  });

  const rows = table.getRowModel().rows;

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full text-sm text-left">
          <thead className="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold">
            {table.getHeaderGroups().map((headerGroup) => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  const canSort = header.column.getCanSort();
                  return (
                    <th key={header.id} className="px-4 py-3 whitespace-nowrap">
                      {header.isPlaceholder ? null : canSort ? (
                        <button
                          type="button"
                          onClick={header.column.getToggleSortingHandler()}
                          className="inline-flex items-center gap-1 hover:text-slate-800 transition-colors"
                        >
                          {flexRender(header.column.columnDef.header, header.getContext())}
                          <ArrowUpDown className="w-3.5 h-3.5 text-slate-400" />
                        </button>
                      ) : (
                        flexRender(header.column.columnDef.header, header.getContext())
                      )}
                    </th>
                  );
                })}
              </tr>
            ))}
          </thead>
          <tbody className="divide-y divide-slate-100">
            {loading ? (
              Array.from({ length: 5 }).map((_, idx) => (
                <tr key={idx}>
                  <td colSpan={columns.length} className="px-4 py-4">
                    <div className="h-4 bg-slate-100 rounded animate-pulse w-full" />
                  </td>
                </tr>
              ))
            ) : rows.length === 0 ? (
              <tr>
                <td colSpan={columns.length} className="px-4 py-12 text-center text-slate-500">
                  {emptyMessage}
                </td>
              </tr>
            ) : (
              rows.map((row) => (
                <tr key={row.id} className="hover:bg-slate-50/70 transition-colors">
                  {row.getVisibleCells().map((cell) => (
                    <td key={cell.id} className="px-4 py-3 text-slate-700">
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination Bar */}
      {serverPagination ? (
        <div className="px-4 py-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
          <span>
            Showing page <strong className="text-slate-700">{serverPagination.currentPage}</strong> of{' '}
            <strong className="text-slate-700">{Math.max(1, serverPagination.lastPage)}</strong> ({serverPagination.total}{' '}
            total records)
          </span>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={serverPagination.currentPage <= 1}
              onClick={() => serverPagination.onPageChange(serverPagination.currentPage - 1)}
            >
              <ChevronLeft className="w-3.5 h-3.5" /> Prev
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={serverPagination.currentPage >= serverPagination.lastPage}
              onClick={() => serverPagination.onPageChange(serverPagination.currentPage + 1)}
            >
              Next <ChevronRight className="w-3.5 h-3.5" />
            </Button>
          </div>
        </div>
      ) : (
        table.getPageCount() > 1 && (
          <div className="px-4 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>
              Page <strong className="text-slate-700">{table.getState().pagination.pageIndex + 1}</strong> of{' '}
              <strong className="text-slate-700">{table.getPageCount()}</strong> ({data.length} records)
            </span>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={!table.getCanPreviousPage()}
                onClick={() => table.previousPage()}
              >
                <ChevronLeft className="w-3.5 h-3.5" /> Prev
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={!table.getCanNextPage()}
                onClick={() => table.nextPage()}
              >
                Next <ChevronRight className="w-3.5 h-3.5" />
              </Button>
            </div>
          </div>
        )
      )}
    </div>
  );
}
