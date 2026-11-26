import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, LayoutDashboard, ShieldCheck } from 'lucide-react';
import { useAuth } from '../../context/useAuth';

export const CallToActionSection: React.FC = () => {
  const { isAuthenticated } = useAuth();

  return (
    <section className="bg-white py-16 sm:py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 px-6 py-12 text-center shadow-xl sm:px-12 sm:py-16">
          <div className="mx-auto max-w-2xl">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-900/50">
              <ShieldCheck className="h-6 w-6" aria-hidden="true" />
            </div>
            <h2 className="mt-5 text-2xl font-bold tracking-tight text-white sm:text-3xl">
              Ready to standardise IT asset governance across your organisation?
            </h2>
            <p className="mt-4 text-base text-slate-300">
              Sign in to an existing tenant workspace or review AssetForge&apos;s controlled
              onboarding process for provisioning your organisation.
            </p>

            <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
              {isAuthenticated ? (
                <Link
                  to="/dashboard"
                  className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-indigo-500 transition-colors"
                >
                  <LayoutDashboard className="h-4 w-4" aria-hidden="true" />
                  Go to Dashboard
                </Link>
              ) : (
                <>
                  <Link
                    to="/login"
                    className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-indigo-500 transition-colors"
                  >
                    Sign In to Workspace
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                  </Link>
                  <Link
                    to="/register"
                    className="inline-flex items-center gap-2 rounded-lg border border-slate-500 bg-slate-900/60 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition-colors"
                  >
                    Request Organisation Access
                  </Link>
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
