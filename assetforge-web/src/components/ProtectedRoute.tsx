import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { ShieldAlert, Loader2 } from 'lucide-react';
import { useAuth } from '../context/useAuth';
import { Card, CardContent } from './ui/Card';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requiredPermission?: string;
  allowedRoles?: string[];
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({
  children,
  requiredPermission,
  allowedRoles,
}) => {
  const { isAuthenticated, loading, hasPermission, hasRole } = useAuth();
  const location = useLocation();

  if (loading) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-slate-50 gap-3 text-slate-500">
        <Loader2 className="w-8 h-8 animate-spin text-indigo-600" />
        <p className="text-sm font-medium">Verifying AssetForge session...</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  const permissionAllowed = !requiredPermission || hasPermission(requiredPermission);
  const roleAllowed = !allowedRoles || allowedRoles.some((r) => hasRole(r)) || hasRole('Super User');

  if (!permissionAllowed || !roleAllowed) {
    return (
      <div className="p-8 max-w-xl mx-auto">
        <Card className="border-amber-200 bg-amber-50/40">
          <CardContent className="flex items-start gap-4">
            <div className="p-2.5 rounded-lg bg-amber-100 text-amber-700">
              <ShieldAlert className="w-6 h-6" />
            </div>
            <div>
              <h2 className="text-base font-semibold text-slate-900">Access Restricted</h2>
              <p className="text-sm text-slate-600 mt-1">
                Your current role does not include permission to access this module. Contact your Administrator if you
                need elevated access.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return <>{children}</>;
};
