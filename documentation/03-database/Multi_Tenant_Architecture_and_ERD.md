# AssetForge Multi-Tenant Architecture and ERD

## Document Information

| Field    | Value                                                      |
| -------- | ---------------------------------------------------------- |
| Project  | AssetForge                                                 |
| Document | Multi-Tenant Architecture and ERD                          |
| Version  | 1.0                                                        |
| Status   | MVP Architecture Design                                    |
| Purpose  | Define the database foundation for AssetForge SaaS tenancy |

---

# 1. Introduction

AssetForge is designed as a multi-tenant Enterprise IT Asset Management and Service Operations SaaS platform.

The purpose of this document is to define the initial database architecture required to support multiple customer organizations using a single AssetForge application instance.

The architecture must ensure:

* Secure separation of customer data.
* Scalability as customer numbers increase.
* Flexible organizational structures.
* Support for different company types.
* Future SaaS expansion.

---

# 2. Multi-Tenant Strategy

## Selected Approach

AssetForge will use a:

**Shared Database, Shared Schema, Tenant ID Isolation Model**

Meaning:

* All customers share the same PostgreSQL database.
* All tenant-owned records contain a `tenant_id`.
* Application logic ensures users only access data belonging to their tenant.

Example:

```
AssetForge Database

tenants
    |
    |
    +---- Tenant A (ZRA)
    |
    +---- Tenant B (MTN)
    |
    +---- Tenant C (ABC Technologies)
```

---

# 3. Why This Approach?

## Advantages

### Cost Effective

Suitable for SaaS MVP development because:

* Lower hosting costs.
* Easier database management.
* Faster development.

---

### Scalable

Can support many organizations before requiring architectural changes.

---

### Easier Maintenance

One database means:

* Single migration process.
* Easier backups.
* Easier upgrades.

---

## Future Option

For enterprise customers requiring strict isolation, AssetForge can later support:

```
Dedicated Database per Tenant
```

without changing the application concept.

---

# 4. Core Tenancy Hierarchy

The initial organizational hierarchy:

```
AssetForge Platform

        |
        |
        v

Tenant

        |
        |
        v

Organization (Optional)

        |
        |
        v

Company

        |
        |
        v

Department

        |
        |
        v

Team

        |
        |
        v

Users

        |
        |
        v

Assets
```

---

# 5. Entity Definitions

---

# 5.1 Tenant

## Purpose

Represents a customer organization using AssetForge.

Examples:

* Zambia Revenue Authority
* MTN Zambia
* ABC Corporation

Each tenant owns its own:

* Users
* Companies
* Departments
* Assets
* Repairs
* Reports

---

## Table

```
tenants
```

Initial fields:

| Field      | Type      | Description       |
| ---------- | --------- | ----------------- |
| id         | bigint    | Primary key       |
| name       | varchar   | Tenant name       |
| slug       | varchar   | Unique identifier |
| email      | varchar   | Contact email     |
| phone      | varchar   | Contact number    |
| status     | boolean   | Active/inactive   |
| created_at | timestamp | Creation date     |
| updated_at | timestamp | Update date       |

---

# 5.2 Organization

## Purpose

Optional parent grouping.

Some customers may have:

* Multiple subsidiaries.
* Multiple business units.
* Regional divisions.

Example:

```
Tenant

Zambia Revenue Authority

        |
        |

Organization

Head Office Operations
```

---

## Table

```
organizations
```

Fields:

| Field       | Type      |
| ----------- | --------- |
| id          | bigint    |
| tenant_id   | bigint    |
| name        | varchar   |
| description | text      |
| created_at  | timestamp |
| updated_at  | timestamp |

---

# 5.3 Company

## Purpose

Represents a legal company or operational business entity.

Example:

```
Tenant:

ABC Holdings

Companies:

ABC Technology Division
ABC Finance Division
```

---

## Table

```
companies
```

Fields:

| Field               | Type             |
| ------------------- | ---------------- |
| id                  | bigint           |
| tenant_id           | bigint           |
| organization_id     | bigint nullable  |
| name                | varchar          |
| registration_number | varchar nullable |
| created_at          | timestamp        |
| updated_at          | timestamp        |

---

# 5.4 Department

## Purpose

Represents internal departments.

Examples:

```
ICT Department

Finance Department

Human Resources
```

---

## Table

```
departments
```

Fields:

| Field       | Type          |
| ----------- | ------------- |
| id          | bigint        |
| tenant_id   | bigint        |
| company_id  | bigint        |
| name        | varchar       |
| description | text nullable |
| created_at  | timestamp     |
| updated_at  | timestamp     |

---

# 5.5 Team

## Purpose

Represents smaller operational groups.

Example:

```
ICT Department

    |
    |

Network Team

Hardware Support Team
```

---

## Table

```
teams
```

Fields:

| Field         | Type      |
| ------------- | --------- |
| id            | bigint    |
| tenant_id     | bigint    |
| department_id | bigint    |
| name          | varchar   |
| created_at    | timestamp |
| updated_at    | timestamp |

---

# 5.6 User Relationship

Users already exist in AssetForge.

The User model will evolve to include organizational ownership.

Future fields:

```
users

tenant_id

company_id

department_id nullable

team_id nullable
```

---

# 6. Entity Relationship Diagram

MVP ERD:

```
+----------------+
|    tenants     |
+----------------+
        |
        |
        | 1:M
        |
+----------------+
| organizations  |
+----------------+
        |
        |
        | 1:M
        |
+----------------+
|   companies    |
+----------------+
        |
        |
        | 1:M
        |
+----------------+
| departments    |
+----------------+
        |
        |
        | 1:M
        |
+----------------+
|     teams      |
+----------------+
        |
        |
        |
+----------------+
|     users      |
+----------------+

```

---

# 7. Data Isolation Rules

Every tenant-owned table must include:

```
tenant_id
```

Examples:

Assets:

```
assets
------
id
tenant_id
serial_number
model
```

Repairs:

```
repairs
-------
id
tenant_id
asset_id
status
```

Users:

```
users
-----
id
tenant_id
email
```

---

# 8. Security Considerations

Tenant isolation must be enforced at multiple levels.

## Application Level

Using:

* Middleware
* Policies
* Query filtering
* Authorization rules

---

## Database Level

Future consideration:

* PostgreSQL Row Level Security (RLS)

---

# 9. Future Expansion

This architecture allows future modules:

## Asset Management

```
tenant
 |
assets
 |
asset_history
```

---

## Service Management

```
tenant
 |
service_requests
 |
repairs
 |
technicians
```

---

## SaaS Features

Future:

* Subscription plans
* Billing
* Usage tracking
* Tenant branding
* Customer portals

---

# 10. Design Principles

AssetForge tenancy follows:

## Separation

Customers must never access another customer's data.

---

## Scalability

The design should support growth without major redesign.

---

## Flexibility

Organizations have different structures.

The system should support different business models.

---

## Maintainability

The database should remain understandable as new modules are introduced.

---

# Conclusion

The multi-tenant foundation is the architectural backbone of AssetForge as a SaaS product.

Before implementing Asset Management, Service Operations, and Reporting modules, this organizational foundation must be implemented because all future business data depends on tenant ownership and organizational context.
