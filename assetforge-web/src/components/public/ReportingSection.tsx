import React from 'react';
import { BarChart3, Download, History, ShieldCheck } from 'lucide-react';
import { Badge } from '../ui/Badge';
import { Card, CardContent } from '../ui/Card';

const reportingHighlights = [
  {
    title: 'Executive Dashboard Summaries',
    description:
      'Monitor total hardware volume, status breakdowns, recent repair activity, and upcoming warranty expirations inside the authenticated dashboard.',
    icon: BarChart3,
  },
  {
    title: 'Structured CSV, XLSX & PDF Exports',
    description:
      'Generate downloadable inventory, warranty coverage, and repair reports on demand for finance reviews, IT planning, and asset reconciliations.',
    icon: Download,
  },
  {
    title: 'System & Entity Audit Trails',
    description:
      'Filter tenant-scoped activity records by log category, event type, actor, entity, or date range—supporting internal governance and auditor oversight.',
    icon: History,
  },
  {
    title: 'Warranty Coverage Alerts',
    description:
      'Surface active warranties and remaining contract days before support expires so teams can act on manufacturer claims in time.',
    icon: ShieldCheck,
  },
];

export const ReportingSection: React.FC = () => {
  return (
    <section id="reporting" className="bg-slate-50 py-16 sm:py-20 border-b border-slate-200/80">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-2xl text-center">
          <Badge variant="purple">Reporting &amp; Analytics</Badge>
          <h2 className="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Turn asset data into reliable operational reporting
          </h2>
          <p className="mt-3 text-base text-slate-600">
            Equip IT leadership, finance stakeholders, and internal auditors with consistent exports
            and traceable change histories.
          </p>
        </div>

        <div className="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
          {reportingHighlights.map((item) => {
            const Icon = item.icon;
            return (
              <Card key={item.title} className="bg-white">
                <CardContent className="flex items-start gap-4">
                  <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </div>
                  <div>
                    <h3 className="text-base font-semibold text-slate-900">{item.title}</h3>
                    <p className="mt-1.5 text-sm leading-relaxed text-slate-600">
                      {item.description}
                    </p>
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      </div>
    </section>
  );
};
