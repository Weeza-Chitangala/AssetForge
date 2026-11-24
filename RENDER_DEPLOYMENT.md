# AssetForge Production Deployment Guide (Render Cloud)

This guide documents deploying **AssetForge** (`assetforge-api` + `assetforge-web` + **PostgreSQL 17**) to Render from the `main` branch.

---

## 1. Option A: One-Click Blueprint Deployment (`render.yaml`)

1. Push the `main` branch to your Git repository:
   ```bash
   git push origin dev main
   ```
2. In the **Render Dashboard**, click **New + -> Blueprint**.
3. Connect your repository and select branch `main`. Render detects `/render.yaml` and provisions:
   - `assetforge-db` (PostgreSQL 17)
   - `assetforge-api` (Docker Web Service, `rootDir: assetforge-api`, health check `/api/v1/health`)
   - `assetforge-web` (Static Site, `rootDir: assetforge-web`, `buildCommand: npm ci && npm run build`, `staticPublishPath: dist`, SPA rewrite `/* -> /index.html`)
4. If Render assigns custom `.onrender.com` URLs (e.g., `https://assetforge-api-xxxx.onrender.com` and `https://assetforge-web-xxxx.onrender.com`), update:
   - On `assetforge-api`: `APP_URL`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`
   - On `assetforge-web`: `VITE_API_BASE_URL` (then trigger a manual redeploy of `assetforge-web` so Vite embeds the updated URL).

---

## 2. Option B: Manual Render Service Setup

### A. PostgreSQL 17 Database (`assetforge-db`)
- **Name:** `assetforge-db`
- **PostgreSQL Version:** `17`
- **Database Name:** `assetforge`
- **User:** `assetforge_user`

### B. Backend Web Service (`assetforge-api`)
- **Runtime:** Docker
- **Root Directory:** `assetforge-api`
- **Branch:** `main`
- **Health Check Path:** `/api/v1/health`
- **Dockerfile Path:** `./Dockerfile`
- **Container Entrypoint (`docker/start.sh`):** Binds Apache to `$PORT`, caches config/routes/views, runs `php artisan migrate --force`, and starts Apache.

### C. Frontend Static Site (`assetforge-web`)
- **Root Directory:** `assetforge-web`
- **Branch:** `main`
- **Build Command:** `npm ci && npm run build`
- **Publish Directory:** `dist`
- **Rewrite Rule:** Source `/*` -> Destination `/index.html` (Type: `Rewrite`)

---

## 3. Production Environment Variable Checklist

### `assetforge-api` (Web Service)
| Variable | Value / Notes |
|---|---|
| `APP_NAME` | `AssetForge` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate via `php artisan key:generate --show` (or Render `generateValue: true`) |
| `APP_URL` | `https://<your-api-service>.onrender.com` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Internal DB host from Render PostgreSQL |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `assetforge` |
| `DB_USERNAME` | `assetforge_user` |
| `DB_PASSWORD` | Render PostgreSQL password |
| `DB_SSLMODE` | `require` |
| `FRONTEND_URL` | `https://<your-web-service>.onrender.com` |
| `CORS_ALLOWED_ORIGINS` | `https://<your-web-service>.onrender.com` (comma-separated if multiple) |
| `CORS_SUPPORTS_CREDENTIALS` | `false` (using Bearer token auth) |
| `SANCTUM_STATEFUL_DOMAINS` | `<your-web-service>.onrender.com` |
| `SANCTUM_TOKEN_EXPIRATION` | `1440` (minutes = 24 hours) |
| `LOG_CHANNEL` | `stderr` |

### `assetforge-web` (Static Site)
| Variable | Value / Notes |
|---|---|
| `VITE_API_BASE_URL` | `https://<your-api-service>.onrender.com/api/v1` |
| `VITE_APP_NAME` | `AssetForge` |

---

## 4. Initial Production Seeding & Verification

Open the **Render Shell** on `assetforge-api` after the first deploy completes:

```bash
php artisan db:seed --class=OrganizationSeeder --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan db:seed --class=AssetReferenceDataSeeder --force
php artisan db:seed --class=FaultTypeSeeder --force
```

Then immediately rotate the seeded admin password or create a dedicated production Super Administrator account.

Verify live endpoints over HTTPS:
```bash
curl -i https://<your-api-service>.onrender.com/api/v1/health
curl -i https://<your-api-service>.onrender.com/api/documentation
```
