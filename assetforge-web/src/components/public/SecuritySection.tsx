import React from 'react';
import { FileSearch, KeyRound, Lock, ShieldAlert, Users } from 'lucide-react';
import { Badge } from '../ui/Badge';

const securityControls = [
  {
    title: 'Strict Multi-Tenant Data Isolation',
    description:
      'Every organisational record, search result, validation rule, and audit query enforces authenticated tenant boundaries on the server.',
    icon: Lock,
  },
  {
    title: 'Granular Role-Based Access Control',
    description:
      'Structured roles separate Super User, Administrator, Asset Manager, Technician, and Auditor responsibilities with privilege-escalation guards.',
    icon: Users,
  },
  {
    title: 'Dedicated Custody & Audit Permissions',
    description:
      'Custody assignments require assets.transfer while audit trail inspection requires audit.view—keeping field maintenance and read-only compliance cleanly separated.',
    icon: ShieldAlert,
  },
  {
    title: 'Session Hardening & Activity Trails',
    description:
      'Token revocation on account or tenant suspension, failed-login throttling, full logout-all support, and tenant-scoped model and role-sync logging.',
    icon: KeyRound,
  },
];

export const SecuritySection: React.FC = () => {
  return (
    <section id="security" className="bg-slate-900 text-white py-16 sm:py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-start">
          <div className="lg:col-span-5">
            <Badge className="bg-indigo-950 text-indigo-200 border-indigo-700/60">
              <FileSearch className="h-3.5 w-3.5 text-indigo-400" aria-hidden="true" />
              Security &amp; Governance
            </Badge>
            <h2 className="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">
              Engineered for tenant isolation, RBAC discipline, and traceability
            </h2>
            <p className="mt-4 text-base leading-relaxed text-slate-300">
              Security in AssetForge is enforced at the API, database query, and observer layers—not
              merely hidden in interface buttons.
            </p>
            <div className="mt-6 rounded-xl border border-slate-800 bg-slate-950/70 p-4 text-xs leading-relaxed text-slate-300">
              Powered by Laravel Sanctum Bearer authentication, active-session verification,
              Spatie permission enforcement, and tenant-tagged activity logs.
            </div>
          </div>

          <div className="lg:col-span-7 grid grid-cols-1 gap-5 sm:grid-cols-2">
            {securityControls.map((control) => {
              const Icon = control.icon;
              return (
                <div
                  key={control.title}
                  className="rounded-xl border border-slate-800 bg-slate-950/60 p-6"
                >
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600/20 text-indigo-400">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </div>
                  <h3 className="mt-4 text-base font-semibold text-white">{control.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-400">
                    {control.description}
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
};
