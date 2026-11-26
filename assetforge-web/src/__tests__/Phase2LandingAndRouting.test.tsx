import { beforeEach, describe, expect, it, vi } from 'vitest';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import App from '../App';

const mockGet = vi.fn();
const mockPost = vi.fn();

vi.mock('../services/api', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
    post: (...args: unknown[]) => mockPost(...args),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
  downloadBlob: vi.fn(),
}));

const baseDashboardPayload = {
  kpis: {
    total_assets: 12,
    total_valuation: '24000.00',
    assets_in_repair: 1,
    total_repair_spend: '350.00',
    active_warranties: 10,
    expiring_warranties: 1,
    expired_warranties: 1,
  },
  breakdowns: {
    by_status: [{ id: 's1', name: 'Available', color: '#10b981', count: 8 }],
    by_category: [{ id: 'c1', name: 'Laptops', code: 'LAP', count: 12 }],
  },
  activity: {
    recent_repairs: [],
    recent_movements: [],
  },
};

const assetManagerUser = {
  id: 10,
  tenant_id: 'tenant-a',
  name: 'Alex Manager',
  email: 'manager@alpha.test',
  status: 'active',
  roles: ['Asset Manager'],
  permissions: [
    'dashboard.view',
    'users.view',
    'assets.view',
    'assets.create',
    'assets.update',
    'assets.transfer',
    'assets.delete',
    'repairs.view',
    'repairs.create',
    'repairs.update',
    'reports.view',
  ],
  tenant: { id: 'tenant-a', name: 'Alpha Enterprise', slug: 'alpha', status: 'active' },
};

const auditorUser = {
  id: 11,
  tenant_id: 'tenant-a',
  name: 'Morgan Auditor',
  email: 'auditor@alpha.test',
  status: 'active',
  roles: ['Auditor'],
  permissions: [
    'dashboard.view',
    'users.view',
    'assets.view',
    'repairs.view',
    'reports.view',
    'audit.view',
  ],
  tenant: { id: 'tenant-a', name: 'Alpha Enterprise', slug: 'alpha', status: 'active' },
};

describe('AssetForge Phase 2 Public Landing Page and Routing', () => {
  beforeEach(() => {
    cleanup();
    localStorage.clear();
    mockGet.mockReset();
    mockPost.mockReset();
    window.history.pushState({}, '', '/');
  });

  it('renders the public landing page on / with hero visuals, sections, and required copyright footer', () => {
    render(<App />);

    expect(screen.getAllByText('ASSETFORGE').length).toBeGreaterThanOrEqual(2);
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
      'Take Control of Your IT Assets.'
    );

    const images = screen.getAllByRole('img', { hidden: true });
    expect(images.length).toBeGreaterThanOrEqual(4);
    images.forEach((img) => {
      expect(img).toHaveAttribute('alt');
      expect(img.getAttribute('alt')?.trim().length).toBeGreaterThan(15);
    });

    expect(
      screen.getByText(/Operational visibility and accountability for every device/i)
    ).toBeInTheDocument();
    expect(screen.getByText(/Purpose-built modules for day-to-day enterprise IT operations/i)).toBeInTheDocument();
    expect(screen.getByText(/Asset lifecycle management built around verifiable custody/i)).toBeInTheDocument();
    expect(screen.getByText(/Engineered for tenant isolation, RBAC discipline, and traceability/i)).toBeInTheDocument();
    expect(screen.getByText(/Turn asset data into reliable operational reporting/i)).toBeInTheDocument();
    expect(screen.getByText(/Organisational structure designed for modern multi-site enterprises/i)).toBeInTheDocument();
    expect(screen.getByText('© 2026 AssetForge™. All rights reserved.')).toBeInTheDocument();

    expect(mockGet).not.toHaveBeenCalled();
  });

  it('navigates across all three hero slides via buttons, tab indicators, and keyboard arrows', () => {
    render(<App />);

    const carousel = screen.getByRole('region', { name: 'AssetForge platform highlights' });
    const heading = () => screen.getByRole('heading', { level: 1 });

    expect(heading()).toHaveTextContent('Take Control of Your IT Assets.');
    expect(screen.getByText('1 / 3')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: 'Next slide' }));
    expect(heading()).toHaveTextContent('Complete Visibility Across the Asset Lifecycle.');
    expect(screen.getByText('2 / 3')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: 'Next slide' }));
    expect(heading()).toHaveTextContent('Enterprise Asset Management. Simplified.');
    expect(screen.getByText('3 / 3')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: 'Previous slide' }));
    expect(heading()).toHaveTextContent('Complete Visibility Across the Asset Lifecycle.');

    fireEvent.keyDown(carousel, { key: 'ArrowLeft' });
    expect(heading()).toHaveTextContent('Take Control of Your IT Assets.');

    fireEvent.keyDown(carousel, { key: 'ArrowRight' });
    expect(heading()).toHaveTextContent('Complete Visibility Across the Asset Lifecycle.');

    fireEvent.click(
      screen.getByRole('tab', { name: /Go to slide 3: Enterprise Asset Management\. Simplified\./i })
    );
    expect(heading()).toHaveTextContent('Enterprise Asset Management. Simplified.');
  });

  it('supports controlled /register onboarding guidance without unauthenticated tenant signup calls', () => {
    render(<App />);

    const registerLinks = screen.getAllByRole('link', { name: /^Register$/i });
    fireEvent.click(registerLinks[0]);

    expect(window.location.pathname).toBe('/register');
    expect(
      screen.getByRole('heading', { level: 1, name: /How Workspace Access & Tenant Provisioning Work/i })
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /Sign In to Your Workspace/i })).toHaveAttribute('href', '/login');

    fireEvent.change(screen.getByPlaceholderText('Acme Enterprise Ltd.'), {
      target: { value: 'Zambia FinTech Group' },
    });
    fireEvent.change(screen.getByPlaceholderText('it-lead@organisation.com'), {
      target: { value: 'it@zambiafintech.co.zm' },
    });
    fireEvent.click(screen.getByRole('button', { name: /Prepare Onboarding Request Summary/i }));

    expect(screen.getByRole('status')).toHaveTextContent(
      /No tenant account has been created automatically/i
    );
    expect(mockPost).not.toHaveBeenCalled();
  });

  it('navigates to /login from landing page and redirects post-login to /dashboard by default', async () => {
    mockPost.mockResolvedValueOnce({
      data: {
        data: {
          token: 'valid-token',
          user: assetManagerUser,
        },
      },
    });
    mockGet.mockImplementation((url: string) => {
      if (url === '/dashboard/summary') {
        return Promise.resolve({ data: { data: baseDashboardPayload } });
      }
      return Promise.resolve({ data: { data: [] } });
    });

    render(<App />);

    fireEvent.click(screen.getAllByRole('link', { name: /^Sign In$/i })[0]);
    expect(window.location.pathname).toBe('/login');
    expect(screen.getByRole('link', { name: /Back to AssetForge Home/i })).toHaveAttribute('href', '/');

    fireEvent.change(screen.getByPlaceholderText('admin@assetforge.com'), {
      target: { value: 'manager@alpha.test' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'Password123!' },
    });
    fireEvent.click(screen.getByRole('button', { name: /Sign In to Workspace/i }));

    await waitFor(() => {
      expect(window.location.pathname).toBe('/dashboard');
      expect(screen.getByText('Executive ITAM Dashboard')).toBeInTheDocument();
    });

    const dashboardSidebarLink = screen.getByRole('link', { name: 'Dashboard' });
    expect(dashboardSidebarLink).toHaveAttribute('href', '/dashboard');
  });

  it('switches public navbar controls cleanly for authenticated users and supports logout', async () => {
    localStorage.setItem('assetforge_token', 'active-token');
    mockGet.mockImplementation((url: string) => {
      if (url === '/auth/me') {
        return Promise.resolve({ data: { data: assetManagerUser } });
      }
      return Promise.resolve({ data: { data: [] } });
    });
    mockPost.mockResolvedValueOnce({ data: { success: true } });

    render(<App />);

    await waitFor(() => {
      expect(screen.getAllByRole('link', { name: /Go to Dashboard/i })[0]).toHaveAttribute(
        'href',
        '/dashboard'
      );
    });
    expect(screen.queryByRole('link', { name: /^Sign In$/i })).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /Logout/i }));

    await waitFor(() => {
      expect(mockPost).toHaveBeenCalledWith('/auth/logout');
      expect(localStorage.getItem('assetforge_token')).toBeNull();
      expect(screen.getAllByRole('link', { name: /^Sign In$/i })[0]).toHaveAttribute('href', '/login');
    });
  });

  it('redirects unauthenticated requests for protected routes to /login and preserves audit.view restriction', async () => {
    for (const protectedPath of ['/dashboard', '/assets', '/audit-logs', '/settings']) {
      cleanup();
      localStorage.clear();
      window.history.pushState({}, '', protectedPath);
      render(<App />);
      expect(window.location.pathname).toBe('/login');
      expect(screen.getByRole('button', { name: /Sign In to Workspace/i })).toBeInTheDocument();
    }

    // Asset Manager holds reports.view but lacks audit.view -> Access Restricted on /audit-logs
    cleanup();
    localStorage.setItem('assetforge_token', 'mgr-token');
    mockGet.mockImplementation((url: string) => {
      if (url === '/auth/me') {
        return Promise.resolve({ data: { data: assetManagerUser } });
      }
      return Promise.resolve({ data: { data: [] } });
    });
    window.history.pushState({}, '', '/audit-logs');
    render(<App />);

    await waitFor(() => {
      expect(screen.getByText('Access Restricted')).toBeInTheDocument();
      expect(screen.queryByRole('link', { name: 'Audit Logs' })).not.toBeInTheDocument();
    });

    // Auditor holds audit.view -> AuditLogsPage loads cleanly
    cleanup();
    localStorage.setItem('assetforge_token', 'auditor-token');
    mockGet.mockImplementation((url: string) => {
      if (url === '/auth/me') {
        return Promise.resolve({ data: { data: auditorUser } });
      }
      if (url === '/audit-logs') {
        return Promise.resolve({
          data: {
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
          },
        });
      }
      return Promise.resolve({ data: { data: [] } });
    });
    window.history.pushState({}, '', '/audit-logs');
    render(<App />);

    await waitFor(() => {
      expect(screen.queryByText('Access Restricted')).not.toBeInTheDocument();
      expect(screen.getByRole('link', { name: 'Audit Logs' })).toBeInTheDocument();
      expect(screen.getByRole('heading', { name: /Audit Trails & Security Logs/i })).toBeInTheDocument();
    });
  });

  it('toggles password visibility without submitting the form and preserves input value', () => {
    window.history.pushState({}, '', '/login');
    render(<App />);

    const passwordInput = screen.getByPlaceholderText('••••••••') as HTMLInputElement;
    expect(passwordInput.type).toBe('password');

    fireEvent.change(passwordInput, { target: { value: 'SecretPass123!' } });
    expect(passwordInput.value).toBe('SecretPass123!');

    const showButton = screen.getByRole('button', { name: 'Show password' });
    expect(showButton).toHaveAttribute('aria-pressed', 'false');
    fireEvent.click(showButton);

    expect(passwordInput.type).toBe('text');
    expect(passwordInput.value).toBe('SecretPass123!');
    const hideButton = screen.getByRole('button', { name: 'Hide password' });
    expect(hideButton).toHaveAttribute('aria-pressed', 'true');
    expect(mockPost).not.toHaveBeenCalled();

    fireEvent.click(hideButton);
    expect(passwordInput.type).toBe('password');
    expect(passwordInput.value).toBe('SecretPass123!');
    expect(screen.getByRole('button', { name: 'Show password' })).toHaveAttribute('aria-pressed', 'false');
    expect(mockPost).not.toHaveBeenCalled();
  });

  it('displays specific backend credential errors and distinguishes API connectivity failures', async () => {
    window.history.pushState({}, '', '/login');
    render(<App />);

    fireEvent.change(screen.getByPlaceholderText('admin@assetforge.com'), {
      target: { value: 'admin@assetforge.com' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'wrong-password' },
    });

    mockPost.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'Invalid email or password.',
          errors: { email: ['Invalid email or password.'] },
        },
      },
    });
    fireEvent.click(screen.getByRole('button', { name: /Sign In to Workspace/i }));

    await waitFor(() => {
      expect(screen.getByText('Invalid email or password.')).toBeInTheDocument();
    });

    mockPost.mockRejectedValueOnce(new Error('Network Error'));
    fireEvent.click(screen.getByRole('button', { name: /Sign In to Workspace/i }));

    await waitFor(() => {
      expect(
        screen.getByText(/Unable to reach the AssetForge API server/i)
      ).toBeInTheDocument();
    });
  });
});
