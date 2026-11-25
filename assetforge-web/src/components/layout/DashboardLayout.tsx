import React, { useEffect, useRef, useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import {
  LayoutDashboard,
  Laptop,
  ShieldCheck,
  Wrench,
  FileBarChart2,
  History,
  Settings,
  Search,
  LogOut,
  Menu,
  X,
  Building2,
  User as UserIcon,
  Loader2,
} from 'lucide-react';
import { useAuth } from '../../context/useAuth';
import api from '../../services/api';
import { cn } from '../../lib/utils';
import type { GlobalSearchResponse } from '../../types';

interface NavItem {
  to: string;
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  permission?: string;
}

const navItems: NavItem[] = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard, permission: 'dashboard.view' },
  { to: '/assets', label: 'Equipment Assets', icon: Laptop, permission: 'assets.view' },
  { to: '/warranties', label: 'Warranties', icon: ShieldCheck, permission: 'assets.view' },
  { to: '/repairs', label: 'Repairs', icon: Wrench, permission: 'repairs.view' },
  { to: '/reports', label: 'Reports', icon: FileBarChart2, permission: 'reports.view' },
  { to: '/audit-logs', label: 'Audit Logs', icon: History, permission: 'reports.view' },
  { to: '/settings', label: 'System Settings', icon: Settings, permission: 'settings.manage' },
];

export const DashboardLayout: React.FC = () => {
  const { user, logout, hasPermission } = useAuth();
  const navigate = useNavigate();

  const [mobileOpen, setMobileOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState<GlobalSearchResponse | null>(null);
  const [searching, setSearching] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const searchBoxRef = useRef<HTMLDivElement>(null);

  const visibleNavItems = navItems.filter((item) => !item.permission || hasPermission(item.permission));

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (searchBoxRef.current && !searchBoxRef.current.contains(e.target as Node)) {
        setSearchOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  useEffect(() => {
    const q = searchTerm.trim();
    if (q.length < 2) {
      return;
    }

    let cancelled = false;
    const timer = setTimeout(async () => {
      setSearching(true);
      try {
        const res = await api.get('/search', { params: { q } });
        if (cancelled) return;
        setSearchResults(res.data.data);
        setSearchOpen(true);
      } catch {
        if (!cancelled) {
          setSearchResults(null);
        }
      } finally {
        if (!cancelled) {
          setSearching(false);
        }
      }
    }, 300);

    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, [searchTerm]);

  const handleSearchChange = (value: string) => {
    setSearchTerm(value);
    if (value.trim().length < 2) {
      setSearchResults(null);
      setSearching(false);
      setSearchOpen(false);
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const navigateFromSearch = (path: string) => {
    setSearchOpen(false);
    setSearchTerm('');
    navigate(path);
  };

  const renderNavLinks = (onClick?: () => void) => (
    <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
      {visibleNavItems.map((item) => {
        const Icon = item.icon;
        return (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.to === '/'}
            onClick={onClick}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                isActive
                  ? 'bg-indigo-600 text-white shadow-sm'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              )
            }
          >
            <Icon className="w-4 h-4 shrink-0" />
            <span>{item.label}</span>
          </NavLink>
        );
      })}
    </nav>
  );

  return (
    <div className="min-h-screen bg-slate-50 flex">
      {/* Desktop Sidebar */}
      <aside className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-slate-900 text-white border-r border-slate-800">
        <div className="h-16 px-5 flex items-center gap-3 border-b border-slate-800">
          <div className="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center font-black text-white">
            AF
          </div>
          <div>
            <span className="font-bold tracking-wider text-sm block leading-none">ASSETFORGE</span>
            <span className="text-[11px] text-slate-400">Enterprise ITAM</span>
          </div>
        </div>
        {renderNavLinks()}
        <div className="p-4 border-t border-slate-800 text-xs text-slate-400">
          <div className="flex items-center gap-2 text-slate-300 font-medium">
            <Building2 className="w-3.5 h-3.5 text-indigo-400" />
            <span className="truncate">{user?.tenant?.name || 'Active Tenant'}</span>
          </div>
          <p className="mt-1 text-[11px] text-slate-500 truncate">{user?.roles?.[0] || 'Standard User'}</p>
        </div>
      </aside>

      {/* Mobile Drawer */}
      {mobileOpen && (
        <div className="fixed inset-0 z-50 lg:hidden flex">
          <div className="fixed inset-0 bg-slate-900/60" onClick={() => setMobileOpen(false)} />
          <aside className="relative w-64 max-w-[80vw] bg-slate-900 text-white flex flex-col z-10">
            <div className="h-16 px-5 flex items-center justify-between border-b border-slate-800">
              <span className="font-bold tracking-wider text-sm">ASSETFORGE</span>
              <button
                type="button"
                onClick={() => setMobileOpen(false)}
                className="p-1 text-slate-400 hover:text-white"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            {renderNavLinks(() => setMobileOpen(false))}
          </aside>
        </div>
      )}

      {/* Main Content Shell */}
      <div className="flex-1 lg:pl-64 flex flex-col min-w-0">
        {/* Topbar */}
        <header className="sticky top-0 z-30 h-16 bg-white border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3 flex-1 max-w-xl">
            <button
              type="button"
              onClick={() => setMobileOpen(true)}
              className="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
              aria-label="Open menu"
            >
              <Menu className="w-5 h-5" />
            </button>

            {/* Global Search */}
            <div ref={searchBoxRef} className="relative flex-1">
              <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={searchTerm}
                onFocus={() => searchResults && setSearchOpen(true)}
                onChange={(e) => handleSearchChange(e.target.value)}
                placeholder="Global search: asset tag, serial, repair job, user..."
                className="w-full pl-9 pr-8 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
              />
              {searching && (
                <Loader2 className="w-4 h-4 animate-spin text-indigo-600 absolute right-3 top-1/2 -translate-y-1/2" />
              )}

              {searchOpen && searchResults && (
                <div className="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl max-h-96 overflow-y-auto z-50 divide-y divide-slate-100">
                  {searchResults.total === 0 ? (
                    <div className="p-4 text-xs text-slate-500 text-center">No matching records found.</div>
                  ) : (
                    <>
                      {searchResults.assets.length > 0 && (
                        <div className="p-2">
                          <p className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Equipment Assets
                          </p>
                          {searchResults.assets.map((a) => (
                            <button
                              key={a.id}
                              type="button"
                              onClick={() => navigateFromSearch(`/assets/${a.id}`)}
                              className="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-50 flex items-center justify-between gap-2"
                            >
                              <div>
                                <p className="text-xs font-semibold text-slate-800">{a.title}</p>
                                <p className="text-[11px] text-slate-500">{a.subtitle}</p>
                              </div>
                              {a.status && (
                                <span className="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium">
                                  {a.status}
                                </span>
                              )}
                            </button>
                          ))}
                        </div>
                      )}

                      {searchResults.repairs.length > 0 && (
                        <div className="p-2">
                          <p className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Repair Jobs
                          </p>
                          {searchResults.repairs.map((r) => (
                            <button
                              key={r.id}
                              type="button"
                              onClick={() => navigateFromSearch('/repairs')}
                              className="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-50 flex items-center justify-between gap-2"
                            >
                              <div>
                                <p className="text-xs font-semibold text-slate-800">
                                  {r.title} {r.asset_tag ? `(${r.asset_tag})` : ''}
                                </p>
                                <p className="text-[11px] text-slate-500 truncate max-w-sm">{r.subtitle}</p>
                              </div>
                              {r.status && (
                                <span className="text-[10px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium capitalize">
                                  {r.status.replace(/_/g, ' ')}
                                </span>
                              )}
                            </button>
                          ))}
                        </div>
                      )}

                      {searchResults.warranties.length > 0 && (
                        <div className="p-2">
                          <p className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Warranties
                          </p>
                          {searchResults.warranties.map((w) => (
                            <button
                              key={w.id}
                              type="button"
                              onClick={() => navigateFromSearch('/warranties')}
                              className="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-50 flex items-center justify-between gap-2"
                            >
                              <div>
                                <p className="text-xs font-semibold text-slate-800">{w.title}</p>
                                <p className="text-[11px] text-slate-500">
                                  {w.subtitle} {w.asset_tag ? `• ${w.asset_tag}` : ''}
                                </p>
                              </div>
                            </button>
                          ))}
                        </div>
                      )}

                      {searchResults.users.length > 0 && (
                        <div className="p-2">
                          <p className="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Users
                          </p>
                          {searchResults.users.map((u) => (
                            <div key={u.id} className="px-3 py-2 rounded-lg flex items-center gap-2 text-xs text-slate-700">
                              <UserIcon className="w-3.5 h-3.5 text-slate-400" />
                              <div>
                                <p className="font-medium">{u.title}</p>
                                <p className="text-[11px] text-slate-500">{u.subtitle}</p>
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* User Controls */}
          <div className="flex items-center gap-3">
            <span className="hidden md:inline-flex items-center gap-1.5 text-xs bg-slate-100 px-3 py-1.5 rounded-full text-slate-700 font-medium">
              <Building2 className="w-3.5 h-3.5 text-indigo-600" />
              {user?.tenant?.name || 'Tenant'}
            </span>
            <div className="hidden sm:block text-right">
              <p className="text-xs font-semibold text-slate-800 leading-tight">{user?.name}</p>
              <p className="text-[11px] text-slate-500">{user?.roles?.[0] || 'User'}</p>
            </div>
            <button
              type="button"
              onClick={handleLogout}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-100 transition-colors"
            >
              <LogOut className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Logout</span>
            </button>
          </div>
        </header>

        {/* Page Body */}
        <main className="flex-1 p-4 lg:p-8 max-w-7xl w-full mx-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
};
