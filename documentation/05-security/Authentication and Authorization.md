# Authentication and Authorization

## 1. Purpose

This document defines the authentication and authorization security model for AssetForge.

It establishes:

* How users authenticate into the system
* How access permissions are assigned
* How roles control system capabilities
* How authorization decisions are made

The **RBAC Permission Matrix (`RBAC_Permission_Matrix.xlsx`)** is the official source of truth for:

* Roles
* Permissions
* Access decisions

Any changes to system access must be reflected in the RBAC Permission Matrix before implementation.

---

# 2. Authentication

Authentication verifies the identity of a user before allowing access to AssetForge.

The authentication process includes:

* User login using registered credentials
* Password verification
* Secure password storage
* Token/session management
* Logout and session invalidation
* Protection against unauthorized login attempts

AssetForge will use Laravel authentication services and API security mechanisms to protect user accounts.

---

# 3. Authorization

Authorization determines what actions an authenticated user is allowed to perform.

AssetForge uses **Role-Based Access Control (RBAC).**

The authorization relationship is:

```
User
  |
  v
Role
  |
  v
Permissions
  |
  v
Allowed Actions
```

Users receive permissions through assigned roles.

Direct permission assignment to individual users should only be allowed as a documented exception.

---

# 4. RBAC Principles

## Least Privilege

Users should receive only the permissions required to perform their responsibilities.

## Separation of Duties

Sensitive operations should be restricted to appropriate roles to reduce unauthorized changes.

## Auditability

Changes to:

* Users
* Roles
* Permissions
* Access assignments

must be recorded for accountability.

## Consistency

All access decisions must follow the approved RBAC Permission Matrix.

---

# 5. AssetForge Roles

| Role                | Description                                                               |
| ------------------- | ------------------------------------------------------------------------- |
| Super Administrator | Full control of the AssetForge platform, including security configuration |
| Administrator       | Manages operational settings, users, and system activities                |
| Inventory Manager   | Manages assets, asset lifecycle, assignments, and inventory records       |
| Department Manager  | Oversees assets belonging to their department                             |
| Standard User       | Views and interacts only with assigned assets                             |

---

# 6. Permission Naming Convention

Permissions use the following format:

```
ACTION_RESOURCE
```

Examples:

```
CREATE_ASSET
VIEW_ASSET
UPDATE_ASSET
DELETE_ASSET
ASSIGN_ASSET
VIEW_REPORT
MANAGE_USERS
```

---

# 7. Authorization Enforcement

Authorization must be enforced at multiple levels:

## Frontend

Used to:

* Hide unavailable features
* Improve user experience
* Prevent invalid actions

## Backend API

The backend is the final authority for all authorization decisions.

Every protected API endpoint must verify:

* User authentication
* User role
* Required permission

## Database

Sensitive operations should include appropriate database protection where required.

---

# 8. Access Management Process

When introducing a new permission:

1. Define the permission.
2. Add it to the RBAC Permission Matrix.
3. Assign it to appropriate roles.
4. Review security impact.
5. Implement authorization checks.
6. Test access behaviour.
7. Document the change.

---

# 9. RBAC Governance

The RBAC Permission Matrix must be reviewed whenever:

* A new department is introduced
* A new feature requires access control
* A user's responsibilities change
* Security requirements change

The approved matrix remains the official reference for all authorization decisions.
