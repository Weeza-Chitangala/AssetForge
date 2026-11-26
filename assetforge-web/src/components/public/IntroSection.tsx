import React from 'react';
import { CheckCircle2, AlertTriangle } from 'lucide-react';
import { Badge } from '../ui/Badge';
import dashboardShowcaseImg from '../../assets/landing/assetforge-dashboard-showcase.webp';

const challengesSolved = [
  'Replace fragmented spreadsheets with a central, searchable hardware registry',
  'Maintain clear custodian accountability across departments and office locations',
  'Track active warranties and prevent missed vendor coverage before repair costs rise',
  'Capture structured repair progress, maintenance costs, and verifiable audit trails',
];

export const IntroSection: React.FC = () => {
  return (
    <section id="overview" className="bg-white py-16 sm:py-20 border-b border-slate-200/80">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
          <div className="lg:col-span-6">
            <Badge variant="purple">Why Enterprise ITAM Matters</Badge>
            <h2 className="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
              Operational visibility and accountability for every device your organisation owns
            </h2>
            <p className="mt-4 text-base leading-relaxed text-slate-600">
              Growing organisations lose time and budget when laptops, workstations, network gear,
              warranties, and repair histories live in disconnected spreadsheets. AssetForge brings
              equipment tracking, lifecycle custody, maintenance, and reporting into a single
              multi-tenant workspace.
            </p>

            <div className="mt-6 rounded-xl border border-amber-200/80 bg-amber-50/50 p-4 flex items-start gap-3">
              <AlertTriangle className="h-5 w-5 shrink-0 text-amber-600 mt-0.5" aria-hidden="true" />
              <p className="text-sm text-slate-700">
                Without controlled custody handovers and permissioned audit records, organisations face
                unaccounted hardware losses, unauthorised reassignments, and delayed compliance reviews.
              </p>
            </div>

            <ul className="mt-6 space-y-3">
              {challengesSolved.map((item) => (
                <li key={item} className="flex items-start gap-3 text-sm text-slate-700">
                  <CheckCircle2 className="h-5 w-5 shrink-0 text-indigo-600 mt-0.5" aria-hidden="true" />
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>

          <div className="lg:col-span-6">
            <figure className="overflow-hidden rounded-2xl border border-slate-200 bg-slate-900 shadow-xl">
              <img
                src={dashboardShowcaseImg}
                alt="Illustrative AssetForge dashboard interface displayed on an enterprise laptop showing asset distribution charts and inventory status indicators"
                width={1600}
                height={900}
                loading="lazy"
                decoding="async"
                className="aspect-[16/9] w-full object-cover"
              />
              <figcaption className="border-t border-slate-800 bg-slate-950 px-5 py-3 text-xs text-slate-400">
                Illustrative marketing preview — live metrics are tenant-isolated inside your authenticated workspace.
              </figcaption>
            </figure>
          </div>
        </div>
      </div>
    </section>
  );
};
