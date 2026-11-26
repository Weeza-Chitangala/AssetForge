import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Building2, LayoutDashboard, LogOut, Menu, ShieldCheck, X } from 'lucide-react';
import { useAuth } from '../../context/useAuth';
import { Button } from '../ui/Button';

const sectionLinks = [
  { href: '/#capabilities', id: 'capabilities', label: 'Capabilities' },
  { href: '/#lifecycle', id: 'lifecycle', label: 'Lifecycle' },
  { href: '/#security', id: 'security', label: 'Security' },
  { href: '/#reporting', id: 'reporting', label: 'Reporting' },
  { href: '/#multi-tenant', id: 'multi-tenant', label: 'Multi-Tenant' },
];

export const PublicNavbar: React.FC = () => {
  const { isAuthenticated, loading, user, logout } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();

  const handleSectionClick = (e: React.MouseEvent<HTMLAnchorElement>, sectionId: string) => {
    setMobileMenuOpen(false);
    if (location.pathname === '/') {
      e.preventDefault();
      const target = document.getElementById(sectionId);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      window.history.replaceState(null, '', `/#${sectionId}`);
    }
  };

  const handleLogout = async () => {
    setMobileMenuOpen(false);
    await logout();
    navigate('/');
  };

  return (
    <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <Link
          to="/"
          onClick={() => setMobileMenuOpen(false)}
          className="flex items-center gap-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg"
        >
          <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm shadow-indigo-600/25">
            <ShieldCheck className="h-5 w-5" aria-hidden="true" />
          </span>
          <span className="flex flex-col">
            <span className="text-sm font-bold tracking-wider text-slate-900 leading-none">
              ASSETFORGE
            </span>
            <span className="text-[11px] font-medium text-slate-500 mt-0.5">
              Enterprise ITAM
            </span>
          </span>
        </Link>

        <nav aria-label="Primary navigation" className="hidden lg:flex items-center gap-7">
          {sectionLinks.map((item) => (
            <a
              key={item.id}
              href={item.href}
              onClick={(e) => handleSectionClick(e, item.id)}
              className="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors"
            >
              {item.label}
            </a>
          ))}
        </nav>

        <div className="hidden lg:flex items-center gap-3">
          {loading ? (
            <span className="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">
              Checking session...
            </span>
          ) : isAuthenticated ? (
            <>
              <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                <Building2 className="h-3.5 w-3.5 text-indigo-600" aria-hidden="true" />
                <span>{user?.tenant?.name || user?.name || 'Active Workspace'}</span>
              </span>
              <Link
                to="/dashboard"
                className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
              >
                <LayoutDashboard className="h-4 w-4" aria-hidden="true" />
                Go to Dashboard
              </Link>
              <Button type="button" variant="outline" size="md" onClick={handleLogout}>
                <LogOut className="h-4 w-4" aria-hidden="true" />
                Logout
              </Button>
            </>
          ) : (
            <>
              <Link
                to="/login"
                className="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
              >
                Sign In
              </Link>
              <Link
                to="/register"
                className="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
              >
                Register
              </Link>
            </>
          )}
        </div>

        <button
          type="button"
          onClick={() => setMobileMenuOpen((prev) => !prev)}
          aria-expanded={mobileMenuOpen}
          aria-controls="public-mobile-menu"
          aria-label={mobileMenuOpen ? 'Close navigation menu' : 'Open navigation menu'}
          className="lg:hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          {mobileMenuOpen ? <X className="h-5 w-5" aria-hidden="true" /> : <Menu className="h-5 w-5" aria-hidden="true" />}
        </button>
      </div>

      {mobileMenuOpen && (
        <div
          id="public-mobile-menu"
          className="lg:hidden border-t border-slate-200 bg-white px-4 pb-5 pt-3 shadow-lg"
        >
          <nav aria-label="Mobile navigation" className="flex flex-col space-y-1">
            {sectionLinks.map((item) => (
              <a
                key={item.id}
                href={item.href}
                onClick={(e) => handleSectionClick(e, item.id)}
                className="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600"
              >
                {item.label}
              </a>
            ))}
          </nav>

          <div className="mt-4 flex flex-col gap-2.5 border-t border-slate-100 pt-4">
            {isAuthenticated ? (
              <>
                <Link
                  to="/dashboard"
                  onClick={() => setMobileMenuOpen(false)}
                  className="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700"
                >
                  <LayoutDashboard className="h-4 w-4" aria-hidden="true" />
                  Go to Dashboard
                </Link>
                <Button type="button" variant="outline" size="md" onClick={handleLogout} className="w-full">
                  <LogOut className="h-4 w-4" aria-hidden="true" />
                  Logout
                </Button>
              </>
            ) : (
              <>
                <Link
                  to="/login"
                  onClick={() => setMobileMenuOpen(false)}
                  className="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                  Sign In
                </Link>
                <Link
                  to="/register"
                  onClick={() => setMobileMenuOpen(false)}
                  className="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700"
                >
                  Register
                </Link>
              </>
            )}
          </div>
        </div>
      )}
    </header>
  );
};
