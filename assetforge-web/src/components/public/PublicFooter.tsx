import React from 'react';
import { Link } from 'react-router-dom';
import { ShieldCheck } from 'lucide-react';

export const PublicFooter: React.FC = () => {
  return (
    <footer className="bg-slate-950 text-slate-300 border-t border-slate-800">
      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-8 md:grid-cols-4">
          <div className="md:col-span-2">
            <Link to="/" className="inline-flex items-center gap-2.5">
              <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                <ShieldCheck className="h-5 w-5" aria-hidden="true" />
              </span>
              <span className="text-base font-bold tracking-wider text-white">ASSETFORGE</span>
            </Link>
            <p className="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
              Enterprise multi-tenant IT Asset Management platform built for strict tenant isolation,
              controlled equipment custody, warranty visibility, repair lifecycle tracking, and
              auditable governance.
            </p>
          </div>

          <div>
            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-200">
              Platform
            </h2>
            <ul className="mt-4 space-y-2.5 text-sm">
              <li>
                <a href="/#capabilities" className="text-slate-400 hover:text-white transition-colors">
                  Core Capabilities
                </a>
              </li>
              <li>
                <a href="/#lifecycle" className="text-slate-400 hover:text-white transition-colors">
                  Lifecycle Management
                </a>
              </li>
              <li>
                <a href="/#security" className="text-slate-400 hover:text-white transition-colors">
                  Security &amp; RBAC
                </a>
              </li>
              <li>
                <a href="/#reporting" className="text-slate-400 hover:text-white transition-colors">
                  Reporting &amp; Audit
                </a>
              </li>
              <li>
                <a href="/#multi-tenant" className="text-slate-400 hover:text-white transition-colors">
                  Multi-Tenant Architecture
                </a>
              </li>
            </ul>
          </div>

          <div>
            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-200">
              Workspace Access
            </h2>
            <ul className="mt-4 space-y-2.5 text-sm">
              <li>
                <Link to="/login" className="text-slate-400 hover:text-white transition-colors">
                  Sign In to Workspace
                </Link>
              </li>
              <li>
                <Link to="/register" className="text-slate-400 hover:text-white transition-colors">
                  Organisation Onboarding
                </Link>
              </li>
              <li>
                <Link to="/dashboard" className="text-slate-400 hover:text-white transition-colors">
                  Operations Dashboard
                </Link>
              </li>
            </ul>
          </div>
        </div>

        <div className="mt-10 flex flex-col items-center justify-between gap-4 border-t border-slate-800/80 pt-8 sm:flex-row">
          <p className="text-xs text-slate-400">© 2026 AssetForge™. All rights reserved.</p>
          <p className="text-xs text-slate-500">
            Multi-Tenant IT Asset Management • Role-Based Access Control • Audit Governance
          </p>
        </div>
      </div>
    </footer>
  );
};
