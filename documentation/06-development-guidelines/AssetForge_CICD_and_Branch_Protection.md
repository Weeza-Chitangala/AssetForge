# AssetForge CI/CD Pipeline, Quality Baseline & Branch Protection Guide

**Last Updated:** 25 November 2026  
**Repository:** `https://github.com/Weeza-Chitangala/AssetForge.git`  
**Phase:** Phase 0 — CI/CD Foundation & Repository Baseline

---

## 1. Branching & Deployment Policy

AssetForge enforces the promotion flow:

```text
feature/* (or bugfix/*) ──PR──► dev ──PR──► main ──► Render Production
```

| Branch | Purpose | CI Trigger | Render Deployment |
|---|---|---|---|
| `feature/*`, `bugfix/*` | Isolated task development | `AssetForge CI` (Backend + Frontend + Security Audit) | **Never** deploys to production |
| `dev` | Integration & regression testing | `AssetForge CI` on push & PR | Staging service only (if provisioned later; currently not configured in `render.yaml`) |
| `main` | Production-ready releases | `AssetForge CI` + `AssetForge Production Release Check` | Auto-deploys `assetforge-api` (Docker) & `assetforge-web` (Static Site) per `render.yaml` |

---

## 2. Implemented GitHub Actions Workflows

### A. `.github/workflows/ci.yml` (`AssetForge CI`)
Runs on pushes to `dev`, `main`, `feature/**`, `bugfix/**`, `hotfix/**` and on PRs targeting `dev` or `main`:
1. **`backend-ci` (Ubuntu + PHP 8.3 + PostgreSQL 17 service):**
   - Validates `composer.json` (`composer validate --strict --no-check-publish`)
   - Caches Composer download archives via `composer config cache-files-dir` and runs `composer install --prefer-dist --no-interaction`
   - Runs `php artisan migrate --force` and `php artisan db:seed --class=RolesAndPermissionsSeeder --force` against a live `postgres:17-alpine` service
   - Runs `./vendor/bin/phpunit --testdox` (including `MultiTenantIsolationTest`, `RbacAuthorizationTest`, `ReportingAndSettingsTest`)
2. **`frontend-ci` (Ubuntu + Node.js 22):**
   - `npm ci`
   - `npm run lint` (strict ESLint check — zero errors allowed, no rules disabled)
   - `npx tsc -b` (strict TypeScript build check)
   - `npm run build` (Vite production bundle)
3. **`security-audit`:**
   - Runs `composer audit --no-interaction --locked` and `npm audit --audit-level=high` (`continue-on-error: true` so advisories are surfaced transparently in CI logs for tracking).
4. **`ci-gate` (`CI Gate`):**
   - Aggregated required status check requiring both `backend-ci` and `frontend-ci` to succeed.

### B. `.github/workflows/production-release-check.yml` (`AssetForge Production Release Check`)
Runs on PRs targeting `main` and pushes to `main`:
- Parses and validates `render.yaml`, `.github/workflows/ci.yml`, and `.github/workflows/production-release-check.yml` via `ruby -ryaml`
- Validates `assetforge-api/docker/start.sh` syntax (`bash -n`)
- Builds the production container (`assetforge-api/Dockerfile`)

---

## 3. Verified Phase 0 Quality Baseline (25 November 2026)

| Check | Scope | Result | Details |
|---|---|---|---|
| `composer validate --strict --no-check-publish` | `assetforge-api` | **PASS** | `./composer.json is valid` |
| `./vendor/bin/phpunit --testdox` | `assetforge-api` (SQLite `:memory:`) | **PASS** | **11 tests, 92 assertions** (Example Unit/Feature, Multi-Tenant Isolation, 5-Role RBAC, Reports/Settings) |
| `npm run lint` (`eslint .`) | `assetforge-web` | **PASS** | Exit `0`: **0 errors, 1 informational upstream warning** (`src/components/ui/DataTable.tsx` — `react-hooks/incompatible-library` on TanStack Table v8 `useReactTable()`) |
| `npx tsc -b` | `assetforge-web` | **PASS** | Exit `0` (0 TypeScript errors) |
| `npm run build` | `assetforge-web` | **PASS** | Exit `0`; production bundle compiled (`dist/assets/index-Dd3XEYwA.js` ~888.8 kB; Vite chunk-size warning >500 kB) |
| Frontend Unit/E2E Tests | `assetforge-web` | **NOT CONFIGURED** | No Vitest/Jest/Playwright runner installed yet |

### ESLint Cleanup Summary (Zero Rules Disabled)
All **10 ESLint errors and 3 hook-dependency warnings** from the initial audit were resolved cleanly:
- Extracted `AuthContext`, `AuthContextValue`, and `useAuth()` into `assetforge-web/src/context/useAuth.ts` so `AuthContext.tsx` exports only `AuthProvider` (`react-refresh/only-export-components`) and avoids synchronous `setState` in `useEffect` (also removing a redundant `/auth/me` call after login).
- Replaced `useEffect` form-reset in `AssetFormModal.tsx` with `createInitialFormState()` initializer and keyed `<AssetFormModal />` in `AssetsPage.tsx`.
- Moved synchronous search-reset logic in `DashboardLayout.tsx` into `handleSearchChange()`.
- Converted data-fetching effects in `AssetsPage.tsx`, `AssetDetailPage.tsx`, `WarrantiesPage.tsx`, `RepairsPage.tsx`, `ReportsPage.tsx`, and `SettingsPage.tsx` to cancelled/guarded promise chains inside `useEffect`, and memoized `handleDelete` callbacks with `useCallback` included in `useMemo` dependency arrays.
- Retained the single informational upstream warning on `useReactTable()` in `DataTable.tsx` (`eslint-plugin-react-hooks@7` flags TanStack Table v8's `useReactTable()` even without React Compiler enabled) rather than suppressing ESLint rules.

---

## 4. Render Deployment Configuration Audit (`render.yaml`)

Verified against root `render.yaml`:
- **PostgreSQL 17:** `assetforge-db` (`postgresMajorVersion: "17"`, `databaseName: assetforge`, `user: assetforge_user`).
- **Backend Web Service:** `assetforge-api` (`runtime: docker`, `rootDir: assetforge-api`, `branch: main`, `healthCheckPath: /api/v1/health`, `APP_DEBUG=false`, `DB_SSLMODE=require`).
- **Frontend Static Site:** `assetforge-web` (`runtime: static`, `rootDir: assetforge-web`, `branch: main`, `buildCommand: npm ci && npm run build`, `staticPublishPath: dist`, SPA rewrite `/* -> /index.html`).
- **Policy Compliance:** Feature branches do not deploy; `main` deploys to production; no staging service is currently defined on `dev`.

---

## 5. Manual GitHub Branch Protection Setup (Required)

Because `gh` CLI is not installed locally and repository admin settings require GitHub web permissions, configure the following in **GitHub → `Weeza-Chitangala/AssetForge` → Settings → Branches (or Rulesets)**:

### Rule 1: `main` Branch
1. Target branch: `main`.
2. Enable **Require a pull request before merging** (minimum **1 approval**, dismiss stale approvals on new commits).
3. Enable **Require status checks to pass before merging** and **Require branches to be up to date before merging**.
4. Select required status checks:
   - `CI Gate`
   - `Render & Docker Release Verification`
5. Enable **Block force pushes** and disable direct pushes to `main` (do not allow bypassing).

### Rule 2: `dev` Branch
1. Target branch: `dev`.
2. Enable **Require a pull request before merging**.
3. Enable **Require status checks to pass before merging** and select:
   - `CI Gate`
4. Enable **Block force pushes** and disable direct pushes to `dev`.
