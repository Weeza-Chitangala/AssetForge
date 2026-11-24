# AssetForge Coding Standards & Engineering Guidelines

## 1. Purpose

This document defines the official coding standards, documentation requirements, architectural principles, and engineering practices for **AssetForge**, an enterprise IT Asset Management System (ITAM).

These standards apply to:

* `assetforge-api` — Laravel backend
* `assetforge-web` — React frontend

The objective is to maintain a secure, scalable, readable, and professionally documented codebase that can be understood and maintained by current and future developers.

All production code must follow these standards.

---

# 2. Core Engineering Principles

## 2.1 SOLID Principles

Developers must follow SOLID design principles:

* **Single Responsibility:** Classes and functions should have one clear responsibility.
* **Open/Closed:** Code should be extendable without unnecessary modification.
* **Liskov Substitution:** Implementations must correctly replace their abstractions.
* **Interface Segregation:** Avoid forcing classes to depend on unused methods.
* **Dependency Inversion:** Depend on abstractions instead of concrete implementations.

---

## 2.2 DRY Principle

Avoid duplicated logic.

Shared functionality must be extracted into:

* Service classes
* Utility classes
* Reusable React components
* Custom hooks
* Helper functions

---

## 2.3 Self-Documenting Code

Code must be written clearly enough that its purpose is obvious.

Developers should prefer:

```php
$assignedAssetCount
```

over:

```php
$x
```

Comments must explain **why something exists**, not simply describe what the code does.

Avoid:

```php
// Loop through assets
foreach ($assets as $asset)
```

Prefer:

```php
// Assets are processed individually because each assignment
// requires a separate audit log entry.
foreach ($assets as $asset)
```

---

# 3. Code Documentation Requirements

## 3.1 General Documentation Rules

Every major code component must contain documentation explaining:

* Purpose
* Responsibility
* Important business rules
* Parameters
* Expected outputs
* Exceptions or failure scenarios

Documentation must be maintained whenever code changes.

---

# 3.2 PHP Documentation Standards

Laravel classes, services, controllers, and public methods must use PHPDoc.

Example:

```php
/**
 * Assigns an asset to a user and records the movement history.
 *
 * This operation creates an asset assignment record,
 * updates the asset status, and generates an audit log entry.
 *
 * @param Asset $asset Asset being assigned
 * @param User $user User receiving the asset
 *
 * @return AssetMovement Created movement record
 *
 * @throws Exception When assignment cannot be completed
 */
public function assignAsset(Asset $asset, User $user): AssetMovement
{
    //
}
```

---

# 4. Laravel Backend Standards

## 4.1 Naming Conventions

| Artifact  | Standard          | Example         |
| --------- | ----------------- | --------------- |
| Classes   | PascalCase        | AssetController |
| Methods   | camelCase         | assignAsset()   |
| Variables | camelCase         | $assetOwner     |
| Tables    | snake_case plural | asset_movements |
| Columns   | snake_case        | serial_number   |
| Routes    | kebab-case        | /api/v1/assets  |

---

# 5. Laravel Architecture Rules

AssetForge follows a layered architecture:

```
Request
   |
Form Request
   |
Controller
   |
Service Layer
   |
Model / Repository
   |
API Resource
   |
JSON Response
```

## Controllers

Controllers must:

* Receive HTTP requests
* Call services
* Return responses

Controllers must not:

* Contain business logic
* Perform complex calculations
* Execute database workflows directly

---

## Services

Services contain business operations.

Example:

```
app/
 └── Services/
      ├── AssetAssignmentService.php
      ├── WarrantyService.php
      └── UserPermissionService.php
```

Each service must include documentation describing:

* Business purpose
* Dependencies
* Workflow performed

---

# 6. React Frontend Documentation Standards

## Component Documentation

Complex components must include documentation.

Example:

```javascript
/**
 * AssetTable displays registered company assets.
 *
 * Responsibilities:
 * - Display asset information
 * - Handle pagination
 * - Trigger asset selection events
 *
 * Data is retrieved through the assetService API layer.
 */
function AssetTable() {
}
```

---

## Custom Hooks

Hooks must document:

* Purpose
* Parameters
* Returned values

Example:

```javascript
/**
 * Retrieves and manages asset listing data.
 *
 * @returns {
 *  assets: Array,
 *  loading: Boolean,
 *  refreshAssets: Function
 * }
 */
export function useAssets() {
}
```

---

# 7. Database Documentation Standards

Database migrations must explain unclear business decisions.

Example:

```php
$table->string('asset_tag')
      ->unique()
      ->comment('Unique identifier assigned to company equipment');
```

Every table must have:

* Clear naming
* Foreign key relationships
* Required indexes
* Audit fields

---

# 8. API Documentation Requirements

All APIs must be documented using OpenAPI/Swagger.

Documentation must include:

* Endpoint purpose
* Authentication requirements
* Request parameters
* Validation rules
* Response examples
* Error responses

Example:

```
POST /api/v1/assets

Purpose:
Creates a new IT asset record.

Authentication:
Bearer Token Required

Permission:
CREATE_ASSET
```

API documentation must be updated whenever endpoints change.

---

# 9. Security Documentation Requirements

Security-sensitive code must explain:

* Why protection exists
* Which permissions are required
* Expected security behaviour

Examples:

* Authentication middleware
* Authorization policies
* Permission checks
* Encryption logic
* Audit logging

---

# 10. File-Level Documentation

Important files should include a header describing their responsibility.

Example:

```php
/**
 * AssetAssignmentService
 *
 * Handles all business operations related to assigning
 * IT assets to users and departments.
 *
 * Responsibilities:
 * - Validate assignment rules
 * - Update asset ownership
 * - Create audit records
 */
```

---

# 11. Code Review Documentation Checklist

Before merging code:

* [ ] Public classes and methods have documentation.
* [ ] Complex business logic has explanatory comments.
* [ ] API changes include Swagger updates.
* [ ] Database changes include migration comments where needed.
* [ ] No undocumented business rules exist.
* [ ] Variable and method names explain their purpose.
* [ ] Debug statements have been removed.
* [ ] Security-sensitive logic is documented.

---

# 12. Documentation Ownership

Documentation is part of the software, not an optional activity.

Developers are responsible for keeping:

* Source code documentation
* API documentation
* Database documentation
* Architecture documentation

updated throughout the project lifecycle.

A feature is considered incomplete until its code and documentation are both complete.
