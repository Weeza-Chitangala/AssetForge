import React from 'react';
import { ClipboardCheck, UserCheck, Wrench, Archive } from 'lucide-react';
import { Badge } from '../ui/Badge';

const lifecycleStages = [
  {
    step: '01',
    title: 'Register & Label',
    description:
      'Log new hardware individually or via validated bulk import with serial verification and instant printable QR asset tags.',
    icon: ClipboardCheck,
  },
  {
    step: '02',
    title: 'Assign & Transfer',
    description:
      'Issue equipment to employees or relocate assets across offices and departments through authorised checkout, check-in, and transfer actions.',
    icon: UserCheck,
  },
  {
    step: '03',
    title: 'Maintain & Protect',
    description:
      'Open repair tickets, track technician diagnostics without breaking existing custody records, and check vendor warranty coverage.',
    icon: Wrench,
  },
  {
    step: '04',
    title: 'Verify & Report',
    description:
      'Review complete asset movement histories, inspect entity audit logs, and export lifecycle documentation cleanly.',
    icon: Archive,
  },
];

export const LifecycleSection: React.FC = () => {
  return (
    <section id="lifecycle" className="bg-white py-16 sm:py-20 border-b border-slate-200/80">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
          <div className="max-w-2xl">
            <Badge variant="success">End-to-End Accountability</Badge>
            <h2 className="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
              Asset lifecycle management built around verifiable custody
            </h2>
            <p className="mt-3 text-base text-slate-600">
              Track every state transition from onboarding in your IT storeroom through active staff
              assignment, repair bench servicing, and administrative verification.
            </p>
          </div>
        </div>

        <div className="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
          {lifecycleStages.map((stage) => {
            const Icon = stage.icon;
            return (
              <div
                key={stage.step}
                className="relative rounded-xl border border-slate-200 bg-slate-50/70 p-6 flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span className="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-indigo-300">
                      <Icon className="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span className="text-xs font-bold tracking-widest text-indigo-600">
                      STAGE {stage.step}
                    </span>
                  </div>
                  <h3 className="mt-5 text-base font-semibold text-slate-900">{stage.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-600">{stage.description}</p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
};
