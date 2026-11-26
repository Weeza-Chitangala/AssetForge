import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { ProtectedRoute } from './components/ProtectedRoute';
import { DashboardLayout } from './components/layout/DashboardLayout';
import { LoginPage } from './pages/LoginPage';
import { Dashboard } from './pages/Dashboard';
import { AssetsPage } from './pages/AssetsPage';
import { AssetDetailPage } from './pages/AssetDetailPage';
import { WarrantiesPage } from './pages/WarrantiesPage';
import { RepairsPage } from './pages/RepairsPage';
import { ReportsPage } from './pages/ReportsPage';
import { AuditLogsPage } from './pages/AuditLogsPage';
import { SettingsPage } from './pages/SettingsPage';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />

          <Route
            path="/"
            element={
              <ProtectedRoute>
                <DashboardLayout />
              </ProtectedRoute>
            }
          >
            <Route
              index
              element={
                <ProtectedRoute requiredPermission="dashboard.view">
                  <Dashboard />
                </ProtectedRoute>
              }
            />
            <Route
              path="assets"
              element={
                <ProtectedRoute requiredPermission="assets.view">
                  <AssetsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="assets/:id"
              element={
                <ProtectedRoute requiredPermission="assets.view">
                  <AssetDetailPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="warranties"
              element={
                <ProtectedRoute requiredPermission="assets.view">
                  <WarrantiesPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="repairs"
              element={
                <ProtectedRoute requiredPermission="repairs.view">
                  <RepairsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="reports"
              element={
                <ProtectedRoute requiredPermission="reports.view">
                  <ReportsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="audit-logs"
              element={
                <ProtectedRoute requiredPermission="audit.view">
                  <AuditLogsPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="settings"
              element={
                <ProtectedRoute requiredPermission="settings.manage">
                  <SettingsPage />
                </ProtectedRoute>
              }
            />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
