# AssetForge Enterprise Deployment Guide

## 1. Deployment Overview

This document defines the official deployment strategy, environment management process, hosting architecture, operational procedures, and security requirements for **AssetForge**.

AssetForge is an enterprise IT Asset Management System (ITAM) consisting of three major application tiers:

| Component            | Technology           | Repository          |
| -------------------- | -------------------- | ------------------- |
| Backend API          | Laravel 12+ REST API | `assetforge-api`    |
| Frontend Application | React 19 + Vite SPA  | `assetforge-web`    |
| Database             | PostgreSQL 17        | AssetForge Database |

The deployment strategy is designed to provide:

* Secure application delivery
* Reliable releases
* Environment consistency
* Controlled database changes
* Operational monitoring
* Disaster recovery capability

---

# 2. Deployment Lifecycle

AssetForge follows a controlled promotion workflow:

```
Development
      |
      |
      ▼
Staging
      |
      |
      ▼
Production
```

Changes must progress through environments in order.

Direct deployment from local development machines to production is prohibited.

---

# 3. Environment Strategy

| Environment | Purpose                                             | Architecture                         | URL                                               |
| ----------- | --------------------------------------------------- | ------------------------------------ | ------------------------------------------------- |
| Development | Local development, debugging, unit testing          | Local PHP/Node or Docker             | `http://localhost:8000` / `http://localhost:5173` |
| Staging     | Integration testing, QA validation, client approval | Cloud environment                    | `https://staging-api.assetforge.com`              |
| Production  | Live enterprise operations                          | Secure isolated cloud infrastructure | `https://api.assetforge.com`                      |

---

# 4. Environment Rules

## Development Environment

Used for:

* Feature development
* Local testing
* Database migration testing
* API development

Requirements:

* Debug mode enabled
* Test database
* Development credentials only

---

## Staging Environment

Used for:

* Quality assurance
* User acceptance testing
* Release verification

Requirements:

* Production-like configuration
* Production build testing
* Restricted access

---

## Production Environment

Used for:

* Live business operations

Requirements:

* Debug disabled
* HTTPS enforced
* Encrypted secrets
* Monitoring enabled
* Automated backups active

---

# 5. Server & Runtime Requirements

## Backend Requirements (`assetforge-api`)

| Component  | Requirement    |
| ---------- | -------------- |
| PHP        | 8.3+           |
| Laravel    | 12+            |
| Composer   | 2.6+           |
| Database   | PostgreSQL 17+ |
| Web Server | Nginx / Apache |

Required PHP extensions:

```
pdo_pgsql
mbstring
openssl
tokenizer
xml
curl
zip
bcmath
```

---

## Frontend Requirements (`assetforge-web`)

| Component       | Requirement            |
| --------------- | ---------------------- |
| Node.js         | 20.x LTS+              |
| Package Manager | npm 10+, yarn, or pnpm |

---

# 6. Backend Deployment Procedure

## Step 1: Clone Application Repository

```bash
git clone https://github.com/organization/assetforge-api.git /var/www/assetforge-api

cd /var/www/assetforge-api

git checkout main
```

---

## Step 2: Install Production Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

Production deployments must not include development dependencies.

---

# Step 3: Environment Configuration

Create production environment file:

```bash
cp .env.example .env

nano .env
```

Example:

```ini
APP_NAME=AssetForge
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.assetforge.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=assetforge_prod
DB_USERNAME=assetforge_db_user
DB_PASSWORD=SecureComplexPassword123!

FRONTEND_URL=https://app.assetforge.com

SANCTUM_STATEFUL_DOMAINS=app.assetforge.com
```

Sensitive credentials must never be committed to Git.

---

# Step 4: Application Initialization

Generate application encryption key:

```bash
php artisan key:generate
```

Run database migrations:

```bash
php artisan migrate --force
```

Seed required system data:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

---

# Step 5: Production Optimization

Enable Laravel caching:

```bash
php artisan config:cache

php artisan route:cache

php artisan view:cache

php artisan event:cache
```

---

# Step 6: Storage Permissions

Configure ownership:

```bash
chown -R www-data:www-data storage bootstrap/cache
```

Apply permissions:

```bash
chmod -R 775 storage bootstrap/cache
```

---

# 7. Frontend Deployment Procedure

## Step 1: Install Dependencies

```bash
cd /var/www/assetforge-web

npm ci
```

---

## Step 2: Configure Production Environment

Create:

```
.env.production
```

Example:

```ini
VITE_API_BASE_URL=https://api.assetforge.com/api/v1

VITE_APP_NAME=AssetForge
```

---

## Step 3: Build Application

```bash
npm run build
```

Generated files:

```
dist/
├── index.html
├── assets/
│   ├── javascript bundles
│   └── css bundles
```

The `dist` directory is deployed to:

* Nginx
* Render
* Vercel
* AWS S3 + CloudFront

---

# 8. Database Deployment & Configuration

## PostgreSQL Requirements

The production database must have:

* Restricted network access
* Dedicated application user
* Encrypted connections
* Automated backups

---

Create database:

```sql
CREATE DATABASE assetforge_prod;

CREATE USER assetforge_db_user
WITH ENCRYPTED PASSWORD 'SecureComplexPassword123!';

GRANT ALL PRIVILEGES
ON DATABASE assetforge_prod
TO assetforge_db_user;
```

---

Enable SSL:

```ini
DB_SSLMODE=require
```

---

# 9. Database Migration Rules

Database changes must follow:

```
Migration Created
        |
        ▼
Tested in Development
        |
        ▼
Verified in Staging
        |
        ▼
Applied in Production
```

Production database changes must never be performed manually without approval.

---

# 10. CI/CD Deployment Workflow

AssetForge deployments should follow:

```
Developer
    |
Feature Branch
    |
Pull Request
    |
CI Tests
    |
dev Branch
    |
Staging Deployment
    |
Approval
    |
main Branch
    |
Production Deployment
```

Automated checks should include:

* Backend tests
* Frontend build verification
* Code quality checks
* Security scanning

---

# 11. Production Security Checklist

Before production release:

* [ ] HTTPS/TLS certificates active.
* [ ] `APP_DEBUG=false`.
* [ ] Application key configured.
* [ ] Database credentials secured.
* [ ] No default database users exist.
* [ ] CORS restricted to approved domains.
* [ ] Sanctum authentication tested.
* [ ] Spatie permissions verified.
* [ ] Storage permissions secured.
* [ ] Secrets stored outside source control.

---

# 12. Backup Strategy

## Database Backups

Production PostgreSQL requires:

* Daily automated backups.
* Encrypted offsite storage.
* Point-in-time recovery.

Recommended:

```
pg_dump
      |
      ▼
Encrypted Backup Storage
      |
      ▼
Recovery Testing
```

Retention:

* Minimum 30 days

---

## Application Backups

Maintain:

* Deployment scripts
* Infrastructure configuration
* `.env.example`
* Database migration files

---

# 13. Monitoring & Logging

## Application Logs

Laravel logging:

```
storage/logs/laravel.log
```

Requirements:

* Daily rotation
* Error monitoring
* Access control

---

## Health Monitoring

Expose:

```
GET /api/v1/health
```

Checks:

* API availability
* Database connection
* Application status

---

## Error Tracking

Production should integrate with:

* Sentry
* Bugsnag
* Equivalent monitoring tools

---

# 14. Rollback Procedure

If deployment fails:

## Application Rollback

Return to previous release:

```bash
git checkout <previous-release-tag>
```

---

## Database Rollback

Only execute when safe:

```bash
php artisan migrate:rollback
```

Database backups must exist before destructive migrations.

---

# 15. Release Documentation Requirements

Every production release must include:

* Version number
* Release date
* Changes included
* Database changes
* Configuration changes
* Known issues

Example:

```
Release: v1.0.0

Features:
- Authentication module
- Asset registration
- RBAC permissions

Database:
- Added assets table
- Added audit_logs table
```

---

# 16. Deployment Completion Checklist

Before marking deployment complete:

* [ ] Application deployed successfully.
* [ ] Database migrations completed.
* [ ] Health endpoint verified.
* [ ] Authentication tested.
* [ ] Permissions verified.
* [ ] Logs checked.
* [ ] Backups confirmed.
* [ ] Monitoring active.
* [ ] Documentation updated.

---

# 17. Summary

The AssetForge deployment process ensures:

* Secure releases
* Repeatable deployments
* Controlled environment promotion
* Reliable operations
* Disaster recovery readiness

Deployment is considered complete only when the application, infrastructure, security controls, and documentation are all verified.
