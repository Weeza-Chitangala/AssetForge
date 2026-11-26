import React from 'react';
import {
  ArrowLeftRight,
  FileSpreadsheet,
  Laptop,
  QrCode,
  Search,
  ShieldCheck,
  Wrench,
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/Card';
import { Badge } from '../ui/Badge';

const capabilities = [
  {
    title: 'Centralised Asset Registry',
    description:
      'Record asset tags, serial numbers, models, manufacturers, categories, purchase dates, cost figures, and hardware specifications in one tenant-isolated catalog.',
    icon: Laptop,
  },
  {
    title: 'Bulk Import & Data Export',
    description:
      'Onboard equipment fleets cleanly with spreadsheet validation, permission checks on custody fields, atomic rollback on errors, and Excel/CSV exports.',
    icon: FileSpreadsheet,
  },
  {
    title: 'QR Asset Labelling',
    description:
      'Generate crisp QR codes and printable asset label payloads directly from every equipment record for rapid physical identification.',
    icon: QrCode,
  },
  {
    title: 'Controlled Custody Transfers',
    description:
      'Execute formal checkout, check-in, and location/department transfers with an audit-logged movement timeline gated by dedicated transfer permissions.',
    icon: ArrowLeftRight,
  },
  {
    title: 'Warranty & Repair Tracking',
    description:
      'Monitor active and expiring vendor warranties alongside structured repair tickets, fault types, diagnostic progress notes, and maintenance costs.',
    icon: Wrench,
  },
  {
    title: 'Permission-Scoped Search',
    description:
      'Search assets, serial numbers, repair jobs, warranties, and staff quickly from the workspace topbar while respecting module permissions and tenant boundaries.',
    icon: Search,
  },
];

export const CapabilitiesSection: React.FC = () => {
  return (
    <section id="capabilities" className="bg-slate-50 py-16 sm:py-20 border-b border-slate-200/80">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="max-w-2xl">
          <Badge variant="info">
            <ShieldCheck className="h-3.5 w-3.5" aria-hidden="true" />
            Core ITAM Capabilities
          </Badge>
          <h2 className="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Purpose-built modules for day-to-day enterprise IT operations
          </h2>
          <p className="mt-3 text-base text-slate-600">
            Every workflow in AssetForge maps directly to accountable hardware governance across
            inventory control, field technicians, and oversight teams.
          </p>
        </div>

        <div className="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {capabilities.map((item) => {
            const Icon = item.icon;
            return (
              <Card key={item.title} className="h-full hover:border-indigo-300 transition-colors">
                <CardHeader className="border-b-0 pb-2">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </div>
                  <CardTitle className="mt-4">{item.title}</CardTitle>
                </CardHeader>
                <CardContent className="pt-0">
                  <CardDescription className="text-sm leading-relaxed text-slate-600 mt-0">
                    {item.description}
                  </CardDescription>
                </CardContent>
              </Card>
            );
          })}
        </div>
      </div>
    </section>
  );
};
