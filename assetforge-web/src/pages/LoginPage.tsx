import React, { useState } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ShieldCheck, Lock, Mail, AlertCircle, Loader2, ArrowLeft, Eye, EyeOff } from 'lucide-react';
import { useAuth } from '../context/useAuth';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';

export const LoginPage: React.FC = () => {
  const { login, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const requestedPath = (location.state as { from?: { pathname: string } } | null)?.from?.pathname;
  const from =
    requestedPath && requestedPath !== '/' && requestedPath !== '/login' && requestedPath !== '/register'
      ? requestedPath
      : '/dashboard';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (isAuthenticated) {
    return <Navigate to={from} replace />;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await login(email.trim(), password);
      navigate(from, { replace: true });
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      if (!axiosErr.response) {
        setError('Unable to reach the AssetForge API server. Verify the backend is running and reachable.');
      } else {
        const validationMsg = axiosErr.response.data?.errors?.email?.[0];
        setError(validationMsg || axiosErr.response.data?.message || 'Unable to sign in. Check your credentials.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center p-4">
      <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div className="px-6 pt-4 bg-slate-50">
          <Link
            to="/"
            className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-indigo-600 transition-colors"
          >
            <ArrowLeft className="w-3.5 h-3.5" aria-hidden="true" />
            Back to AssetForge Home
          </Link>
        </div>
        <div className="px-8 pt-4 pb-6 bg-slate-50 border-b border-slate-100 text-center">
          <div className="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-indigo-600/30">
            <ShieldCheck className="w-6 h-6" />
          </div>
          <h1 className="mt-4 text-2xl font-bold tracking-tight text-slate-900">ASSETFORGE</h1>
          <p className="text-xs text-slate-500 mt-1">Enterprise Multi-Tenant IT Asset Management</p>
        </div>

        <form onSubmit={handleSubmit} className="p-8 space-y-5">
          {error && (
            <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          <div className="relative">
            <Input
              label="Work Email"
              type="email"
              name="email"
              placeholder="admin@assetforge.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="email"
              className="pl-9"
            />
            <Mail className="w-4 h-4 text-slate-400 absolute left-3 top-[30px]" />
          </div>

          <div className="relative">
            <Input
              label="Password"
              type={showPassword ? 'text' : 'password'}
              name="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              autoComplete="current-password"
              className="pl-9 pr-10"
            />
            <Lock className="w-4 h-4 text-slate-400 absolute left-3 top-[30px]" aria-hidden="true" />
            <button
              type="button"
              onClick={() => setShowPassword((prev) => !prev)}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
              aria-pressed={showPassword}
              className="absolute right-2.5 top-[26px] p-1 rounded text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
            >
              {showPassword ? (
                <EyeOff className="w-4 h-4" aria-hidden="true" />
              ) : (
                <Eye className="w-4 h-4" aria-hidden="true" />
              )}
            </button>
          </div>

          <Button type="submit" className="w-full" size="lg" disabled={submitting}>
            {submitting ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Signing in...
              </>
            ) : (
              'Sign In to Workspace'
            )}
          </Button>

          <div className="text-center space-y-2 pt-2">
            <p className="text-xs text-slate-600">
              Need workspace onboarding?{' '}
              <Link to="/register" className="font-medium text-indigo-600 hover:underline">
                View registration options
              </Link>
            </p>
            <p className="text-[11px] text-slate-400">
              Protected by Sanctum Bearer Auth &amp; Spatie RBAC Tenant Isolation
            </p>
          </div>
        </form>
      </div>
    </div>
  );
};
