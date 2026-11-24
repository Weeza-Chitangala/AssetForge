# AssetForge Git & Branching Workflow Specification

## 1. Overview & Branching Strategy

AssetForge enforces a structured Git workflow designed for team collaboration, codebase stability, controlled releases, and seamless continuous integration and deployment.

The project follows a modified **Gitflow / Feature-Branch development model**.

This workflow ensures:

* Controlled code integration
* Clear ownership of development tasks
* Stable production releases
* Traceable changes
* Consistent documentation practices

All developers contributing to:

* `assetforge-api` (Laravel Backend)
* `assetforge-web` (React Frontend)

must follow this workflow.

---

# 2. Branching Architecture

```
[ main ]      ═════════════════════════════════════════● (v1.0.0 Release)
                 ▲                              ▲
                 │ (Release PR)                 │ (Hotfix PR)
[ dev ]       ───┴───────●──────────────●───────┴──────
                         ▲              ▲
                         │ (Feature PR) │ (Bugfix PR)
[ feature/* ] ───────────┴──────────────┴──────────────

[ hotfix/* ] ───────────────────────────────► main
```

---

# 3. Branch Roles & Responsibilities

| Branch      | Lifecycle | Protection Level  | Description                                                                                              |
| ----------- | --------- | ----------------- | -------------------------------------------------------------------------------------------------------- |
| `main`      | Permanent | Protected         | Production branch. Contains only approved, tested, and deployed releases. Direct commits are prohibited. |
| `dev`       | Permanent | Protected         | Integration branch. All completed features and fixes are merged here after review and testing.           |
| `feature/*` | Temporary | Developer Managed | Created for new features, modules, or development tasks. Merged into `dev` through Pull Requests.        |
| `bugfix/*`  | Temporary | Developer Managed | Used for fixing non-critical issues discovered during development or testing.                            |
| `hotfix/*`  | Temporary | Developer Managed | Created from `main` for urgent production fixes.                                                         |

---

# 4. Branch Protection Rules

The following rules apply:

## main branch

* No direct commits allowed.
* All changes require Pull Request approval.
* Production releases must originate from reviewed code.
* All merges require successful CI/CD checks.

## dev branch

* No direct commits allowed.
* All changes must originate from feature or bugfix branches.
* Code review is required before merging.

---

# 5. Branch Naming Rules

Branch names must clearly describe the purpose of the work.

## Feature Branches

Format:

```
feature/<feature-name>
```

Examples:

```
feature/authentication
feature/authorization
feature/user-management
feature/assets-module
feature/database-design
feature/api-documentation
```

---

## Bugfix Branches

Format:

```
bugfix/<issue-name>
```

Examples:

```
bugfix/sanctum-token-expiration
bugfix/asset-search-filter
bugfix/database-connection-error
```

---

## Hotfix Branches

Format:

```
hotfix/<issue-name>
```

Examples:

```
hotfix/db-connection-pool
hotfix/security-vulnerability
```

---

# 6. Starting a New Development Task

Before creating a feature branch:

```bash
# Switch to integration branch
git checkout dev

# Update local branch
git pull origin dev

# Create feature branch
git checkout -b feature/user-management
```

Developers must always branch from the latest `dev`.

---

# 7. Development Workflow

During development:

Check changed files:

```bash
git status
```

Stage changes:

```bash
git add .
```

or stage specific files:

```bash
git add app/Services/UserService.php
git add app/Http/Controllers/UserController.php
```

---

# 8. Commit Message Standards

AssetForge follows **Conventional Commit standards**.

Commit messages must:

* Be written in the imperative mood.
* Clearly describe the change.
* Explain the purpose of the commit.

## Commit Format

```
type(scope): description
```

---

## Allowed Commit Types

| Type     | Purpose                   |
| -------- | ------------------------- |
| feat     | New feature               |
| fix      | Bug correction            |
| docs     | Documentation changes     |
| refactor | Code restructuring        |
| test     | Adding or modifying tests |
| chore    | Maintenance tasks         |
| perf     | Performance improvements  |

---

## Good Commit Examples

```
feat(auth): implement Sanctum login endpoint

feat(asset): add asset creation workflow

fix(asset): correct asset assignment validation

docs(api): update OpenAPI documentation

refactor(service): improve AssetService transactions

test(user): add user permission tests
```

---

## Prohibited Commit Examples

Avoid:

```
update
fix
changes
wip
bug fixed
testing
```

These messages do not provide meaningful history.

---

# 9. Documentation Requirements

Documentation is part of every development task.

Before creating a Pull Request, developers must review whether documentation requires updating.

## Documentation Impact Assessment

| Change Type         | Required Documentation          |
| ------------------- | ------------------------------- |
| New API Endpoint    | OpenAPI / Swagger Documentation |
| Database Migration  | ERD and Database Documentation  |
| New Permission      | RBAC Permission Matrix          |
| New Feature         | Feature Documentation           |
| Architecture Change | System Architecture Document    |
| Coding Rule Change  | Coding Standards Document       |

A feature is incomplete until both code and required documentation are updated.

---

# 10. Push Feature Branch

After completing development:

```bash
git push -u origin feature/user-management
```

---

# 11. Pull Request Process

All changes must enter `dev` through a Pull Request.

## Pull Request Requirements

### Target Branch

Feature work:

```
feature/* → dev
```

Release work:

```
dev → main
```

Never merge feature branches directly into `main`.

---

## Pull Request Title

Use descriptive titles.

Example:

```
feat(users): Add User CRUD endpoints and validation
```

---

## Pull Request Description

Must include:

* Summary of changes
* Related issue/task
* Database changes
* API changes
* Testing performed
* Documentation updates

Example:

```
Summary:
Implemented user management module.

Changes:
- Added UserController
- Added UserService
- Added validation requests
- Added API resources

Testing:
- Verified CRUD operations
- Tested authorization permissions

Documentation:
- Updated API specification
```

---

# 12. Code Review Process

Every Pull Request requires:

* Minimum one reviewer approval.
* Passing automated tests.
* Successful CI checks.

Reviewers verify:

* Coding standards compliance.
* Security implementation.
* Authorization rules.
* Database design.
* API consistency.
* Documentation completeness.

---

# 13. Merge Strategy

## Feature → Dev

Recommended:

* Squash Merge
* Rebase Merge

Purpose:

* Maintain clean history.
* Keep commits meaningful.

---

## Dev → Main

Use:

* Merge Commit

Purpose:

* Preserve release history.
* Maintain deployment milestones.

---

# 14. Release Management

Production releases must use Git tags.

Version format:

```
MAJOR.MINOR.PATCH
```

Examples:

```
v1.0.0
v1.1.0
v1.1.1
```

Meaning:

| Version | Purpose          |
| ------- | ---------------- |
| Major   | Breaking changes |
| Minor   | New features     |
| Patch   | Bug fixes        |

---

# 15. Conflict Resolution

When a feature branch becomes outdated:

```bash
git checkout feature/user-management

git fetch origin

git merge origin/dev
```

Resolve conflicts locally.

Then:

```bash
git add .

git commit -m "fix: resolve merge conflicts with dev"

git push origin feature/user-management
```

Never force push to:

```
main
dev
```

---

# 16. Hotfix Workflow

For production emergencies:

Create branch from main:

```bash
git checkout main

git pull origin main

git checkout -b hotfix/security-patch
```

After fixing:

```
hotfix/* → main
```

and then:

```
main → dev
```

to keep environments synchronized.

---

# 17. Continuous Integration Requirements

Before merging:

Automated checks must pass:

* Application builds successfully.
* Automated tests pass.
* Code formatting checks pass.
* Static analysis passes.
* Security checks pass.

---

# 18. Branch Ownership Rules

Developers should only modify their assigned branches.

Rules:

* Do not commit directly to another developer's feature branch.
* Communicate before taking ownership of abandoned work.
* Keep feature branches focused on one objective.
* Delete merged branches.

---

# 19. Final Developer Checklist

Before submitting a Pull Request:

* [ ] Branch follows naming standards.
* [ ] Code follows Coding Standards.
* [ ] Business logic is correctly separated.
* [ ] Tests have been completed.
* [ ] API documentation updated.
* [ ] Database documentation updated if required.
* [ ] RBAC permissions updated if required.
* [ ] No debug code remains.
* [ ] Commit messages follow standards.
* [ ] Pull Request description is complete.

---

# 20. Summary

The AssetForge Git workflow ensures:

* Stable production releases
* Controlled development
* Clear collaboration
* Traceable changes
* Professional software engineering practices

Git management, code quality, and documentation are treated as essential parts of the development lifecycle.
