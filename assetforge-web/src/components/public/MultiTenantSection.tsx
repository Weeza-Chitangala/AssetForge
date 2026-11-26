import React from 'react';
import { Building2, Layers, MapPin, Tags } from 'lucide-react';
import { Badge } from '../ui/Badge';

const hierarchyLevels = [
  {
    title: 'Tenant & Organisations',
    description:
      'Top-level workspace isolation supporting structured organisations with verified parent-child data consistency.',
    icon: Building2,
  },
  {
    title: 'Companies, Departments & Teams',
    description:
      'Model subsidiary companies, operational departments, and internal teams cleanly for accurate user and device attribution.',
    icon: Layers,
  },
  {
    title: 'Physical Office Locations',
    description:
      'Track buildings, floors, and rooms across company sites so every physical asset stays anchored to a validated site.',
    icon: MapPin,
  },
  {
    title: 'Tenant Reference Catalogs',
    description:
      'Standardise categories, manufacturers, asset models, and suppliers independently per tenant workspace.',
    icon: Tags,
  },
];

export const MultiTenantSection: React.FC = () => {
  return (
    <section id="multi-tenant" className="bg-white py-16 sm:py-20 border-b border-slate-200/80">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:items-center">
          <div className="lg:col-span-5">
            <Badge variant="default">Multi-Tenant Architecture</Badge>
            <h2 className="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
              Organisational structure designed for modern multi-site enterprises
            </h2>
            <p className="mt-4 text-base leading-relaxed text-slate-600">
              Whether managing a single headquarters or multiple subsidiary companies and regional
              offices, AssetForge validates every hierarchical relationship within your tenant
              boundary.
            </p>
          </div>

          <div className="lg:col-span-7 grid grid-cols-1 gap-5 sm:grid-cols-2">
            {hierarchyLevels.map((level) => {
              const Icon = level.icon;
              return (
                <div
                  key={level.title}
                  className="rounded-xl border border-slate-200 bg-slate-50/60 p-5"
                >
                  <div className="flex items-center gap-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                      <Icon className="h-4 w-4" aria-hidden="true" />
                    </span>
                    <h3 className="text-sm font-semibold text-slate-900">{level.title}</h3>
                  </div>
                  <p className="mt-3 text-sm leading-relaxed text-slate-600">{level.description}</p>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
};
