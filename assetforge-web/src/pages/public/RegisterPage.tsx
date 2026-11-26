import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowLeft,
  ArrowRight,
  Building2,
  CheckCircle2,
  ClipboardCheck,
  LayoutDashboard,
  Lock,
  ShieldCheck,
  Users,
} from 'lucide-react';
import { useAuth } from '../../context/useAuth';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';

export const RegisterPage: React.FC = () => {
  const { isAuthenticated, user } = useAuth();
  const [orgName, setOrgName] = useState('');
  const [contactEmail, setContactEmail] = useState('');
  const [teamSize, setTeamSize] = useState('');
  const [prepared, setPrepared] = useState(false);

  const handlePrepareBrief = (e: React.FormEvent) => {
    e.preventDefault();
    setPrepared(true);
  };

  return (
    <div className="bg-slate-50 py-12 sm:py-16">
      <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div className="mb-8">
          <Link
            to="/"
            className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors"
          >
            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
            Back to AssetForge Home
          </Link>
        </div>

        {isAuthenticated && (
          <div className="mb-8 rounded-xl border border-indigo-200 bg-indigo-50 p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-indigo-950">
              <span className="font-semibold">Signed in as {user?.name || 'Active User'}.</span>{' '}
              Your workspace session is already active.
            </div>
            <Link
              to="/dashboard"
              className="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
            >
              <LayoutDashboard className="h-4 w-4" aria-hidden="true" />
              Go to Dashboard
            </Link>
          </div>
        )}

        <div className="max-w-3xl">
          <Badge variant="purple">
            <Lock className="h-3.5 w-3.5" aria-hidden="true" />
            Controlled Enterprise Onboarding
          </Badge>
          <h1 className="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            How Workspace Access &amp; Tenant Provisioning Work
          </h1>
          <p className="mt-4 text-base leading-relaxed text-slate-600">
            To protect multi-tenant data isolation and role boundaries, AssetForge does not enable
            unrestricted self-service account creation. Access follows two controlled pathways
            depending on whether your organisation already uses AssetForge.
          </p>
        </div>

        <div className="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card className="h-full flex flex-col justify-between">
            <div>
              <CardHeader>
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                  <Users className="h-5 w-5" aria-hidden="true" />
                </div>
                <CardTitle className="mt-4">Joining an Existing Organisation</CardTitle>
                <CardDescription>
                  For staff members, IT technicians, asset managers, and auditors
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4 text-sm text-slate-600">
                <p>
                  User accounts belong strictly to a provisioned tenant and are created by an
                  authorised tenant <strong className="text-slate-800">Administrator</strong> inside
                  System Settings.
                </p>
                <ul className="space-y-2.5">
                  <li className="flex items-start gap-2.5">
                    <CheckCircle2 className="h-4 w-4 shrink-0 text-indigo-600 mt-0.5" aria-hidden="true" />
                    <span>Ask your IT Administrator to provision your work email and role</span>
                  </li>
                  <li className="flex items-start gap-2.5">
                    <CheckCircle2 className="h-4 w-4 shrink-0 text-indigo-600 mt-0.5" aria-hidden="true" />
                    <span>Your account is automatically scoped to your organisation&apos;s tenant hierarchy</span>
                  </li>
                  <li className="flex items-start gap-2.5">
                    <CheckCircle2 className="h-4 w-4 shrink-0 text-indigo-600 mt-0.5" aria-hidden="true" />
                    <span>Once provisioned, sign in directly with your assigned credentials</span>
                  </li>
                </ul>
              </CardContent>
            </div>
            <div className="border-t border-slate-100 p-6">
              <Link
                to="/login"
                className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
              >
                Sign In to Your Workspace
                <ArrowRight className="h-4 w-4" aria-hidden="true" />
              </Link>
            </div>
          </Card>

          <Card className="h-full">
            <CardHeader>
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-indigo-300">
                <Building2 className="h-5 w-5" aria-hidden="true" />
              </div>
              <CardTitle className="mt-4">Onboarding a New Organisation</CardTitle>
              <CardDescription>
                Controlled tenant setup with platform Super User governance
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-sm text-slate-600">
                New tenants are provisioned under controlled platform administration so your
                organisation&apos;s workspace, initial Administrator role, and company structure start
                isolated from day one.
              </p>

              <form onSubmit={handlePrepareBrief} className="space-y-3.5 pt-2">
                <Input
                  label="Organisation Name"
                  name="organisation_name"
                  placeholder="Acme Enterprise Ltd."
                  value={orgName}
                  onChange={(e) => {
                    setOrgName(e.target.value);
                    setPrepared(false);
                  }}
                  required
                />
                <Input
                  label="Work Contact Email"
                  name="contact_email"
                  type="email"
                  placeholder="it-lead@organisation.com"
                  value={contactEmail}
                  onChange={(e) => {
                    setContactEmail(e.target.value);
                    setPrepared(false);
                  }}
                  required
                />
                <Input
                  label="Estimated Equipment / Team Scope"
                  name="team_scope"
                  placeholder="e.g. 250 laptops across 3 offices"
                  value={teamSize}
                  onChange={(e) => {
                    setTeamSize(e.target.value);
                    setPrepared(false);
                  }}
                />
                <Button type="submit" variant="secondary" className="w-full" size="lg">
                  <ClipboardCheck className="h-4 w-4" aria-hidden="true" />
                  Prepare Onboarding Request Summary
                </Button>
              </form>

              {prepared && (
                <div
                  role="status"
                  className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-950 space-y-1.5"
                >
                  <div className="flex items-center gap-1.5 font-semibold">
                    <ShieldCheck className="h-4 w-4 text-emerald-700" aria-hidden="true" />
                    Onboarding Request Summary Prepared
                  </div>
                  <p className="text-emerald-900">
                    No tenant account has been created automatically. Share these details with your
                    AssetForge platform administrator to provision your tenant workspace:
                  </p>
                  <div className="rounded bg-white/80 p-2.5 font-mono text-[11px] text-slate-800 border border-emerald-200">
                    Organisation: {orgName} | Contact: {contactEmail}
                    {teamSize ? ` | Scope: ${teamSize}` : ''}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
};
