# Refactor Phase 1 — Summary of Implemented Changes

**Date:** 2026-06-22
**Branch:** `refactor/phase-1-foundation` (from `dev_staging`)
**Scope:** Purely additive foundation layer — no functional behavior changes

---

## Overview

Phase 1 establishes four new constructs and two method additions to an existing file. No existing behavior was altered. Every existing test suite run remained green before and after each commit.

---

## Changes by Task

### Task 1 — ApprovalAction and RecordStatus Backed Enums

**Commit:** `34e0ec3f` — `refactor: add ApprovalAction and RecordStatus backed enums`

**New files:**
- `app/Enums/ApprovalAction.php` — PHP 8.1 string-backed enum with 8 cases: `Created`, `Updated`, `Submitted`, `Approved`, `Returned`, `Forwarded`, `AutoAdvanced`, `AutoApproved`. Replaces scattered string literals in `RecordApproval` and `RecordHistory` (to be wired in Phase 2).
- `app/Enums/RecordStatus.php` — PHP 8.1 string-backed enum with 5 cases: `Draft`, `Submitted`, `UnderReview`, `Returned`, `Completed`. Replaces scattered string literals in `Record` and `WorkflowStage` (to be wired in Phase 2).
- `tests/Unit/Enums/ApprovalActionTest.php` — 3 unit tests
- `tests/Unit/Enums/RecordStatusTest.php` — 3 unit tests

---

### Task 2 — Module Model Helpers

**Commit:** `9178951b` — `refactor: add Module::resolvedModuleId() and resolvedFields() helpers`

**Modified file:**
- `app/Models/Module.php` — Added two methods after `workflows()`:
  - `resolvedModuleId(): int` — Returns `source_module_id ?? id`, centralising the mirror-module pattern used in 4+ places.
  - `resolvedFields(): Collection` — Returns own fields when no source module; merges source module fields (first) with own fields (last) when a source module is set.

**New file:**
- `tests/Feature/Models/ModuleTest.php` — 4 feature tests covering both methods for own-module and mirror-module scenarios.

---

### Task 3 — RecordValidationRuleFactory

**Commit:** `a3ec0ad4` — `refactor: extract RecordValidationRuleFactory for form and API rule generation`

**New files:**
- `app/Support/RecordValidationRuleFactory.php` — Static factory class with two methods:
  - `forForm(Collection $fields, ?object $record = null): array` — Generates Livewire form validation rules. Handles `multi_select` (array), versioned `attachment` (presence check against existing versions), and all other field types.
  - `forApi(Collection $fields): array` — Generates REST API validation rules with type-appropriate Laravel constraints (`email`, `numeric`, `date`, `url`, `boolean`, `array`).
- `tests/Unit/Support/RecordValidationRuleFactoryTest.php` — 16 unit tests covering all 13 field types and both methods (no DB required).

---

### Task 4 — RecordPolicy + AppServiceProvider Registration

**Commit:** `c14b977e` — `refactor: add RecordPolicy and register with Gate`

**New file:**
- `app/Policies/RecordPolicy.php` — Authorization contracts for all Record operations:
  - `view(User, Record): bool` — super admin or `view-{slug}` permission
  - `create(User, string $moduleSlug): bool` — super admin or `create-{slug}` permission (non-standard signature: module-scoped, no Record instance at create-time)
  - `update(User, Record): bool` — super admin or `edit-{slug}` permission
  - `approve(User, Record): bool` — super admin, stage approver role, or `approve-{slug}` permission
  - `review(User, Record): bool` — **super admin is intentionally blocked**; `review-{slug}` permission only
  - `viewEditor(User, Record): bool` — super admin or any module-level permission or stage approver role

**Modified file:**
- `app/Providers/AppServiceProvider.php` — Added two `use` imports and `Gate::policy(Record::class, RecordPolicy::class)` in `boot()`.

**New file:**
- `tests/Feature/Policies/RecordPolicyTest.php` — 18 feature tests covering all 6 policy methods × (super admin / permitted user / stranger) combinations.

> **Note:** The plan projected 15 tests; 18 were written to give each of the 6 methods a full 3-scenario matrix (super admin, permitted user, stranger). The `viewEditor` test set follows the same pattern.

---

## Files Created / Modified

### New Files (9)

| File | Purpose |
|---|---|
| `app/Enums/ApprovalAction.php` | Backed enum — action strings |
| `app/Enums/RecordStatus.php` | Backed enum — status strings |
| `app/Support/RecordValidationRuleFactory.php` | Static rule factory |
| `app/Policies/RecordPolicy.php` | Record authorization policy |
| `tests/Unit/Enums/ApprovalActionTest.php` | 3 unit tests |
| `tests/Unit/Enums/RecordStatusTest.php` | 3 unit tests |
| `tests/Unit/Support/RecordValidationRuleFactoryTest.php` | 16 unit tests |
| `tests/Feature/Policies/RecordPolicyTest.php` | 18 feature tests |
| `tests/Feature/Models/ModuleTest.php` | 4 feature tests |

### Modified Files (2)

| File | Change |
|---|---|
| `app/Models/Module.php` | +16 lines: `resolvedModuleId()` and `resolvedFields()` |
| `app/Providers/AppServiceProvider.php` | +3 lines: 2 use imports + `Gate::policy()` |

---

## Deviations from the Approved Plan

| # | Plan spec | Actual | Reason |
|---|---|---|---|
| 1 | `created_by: 1` in policy test `beforeEach` | `created_by: $this->superAdmin->id` | SQLite FK constraint fails with hardcoded ID 1 (no user exists yet in :memory: DB) |
| 2 | `$user->givePermissionTo("perm-name")` | `Permission::firstOrCreate(...)` then `givePermissionTo($permission)` | Spatie throws `PermissionDoesNotExist` if permission isn't created in DB first |
| 3 | `uses(RefreshDatabase::class)` not mentioned in plan | Added to `RecordPolicyTest.php` | Required for clean test DB state matching `SecurityHardeningTest` pattern |
| 4 | 15 policy tests projected | 18 tests written | `viewEditor` gets the same 3-scenario matrix as the other 5 methods |

---

## Potential Risks / Follow-up Tasks

### Known Issues (from plan — unchanged)

1. **`review()` + `Gate::before()` conflict** — `Gate::before()` returns `true` for super admin before the policy fires. `RecordPolicy::review()` correctly blocks super admin in isolation, but `Gate::allows('review', $record)` would incorrectly grant super admin access. Phase 3 must resolve before replacing `canReview()` with a Gate call.

2. **`create()` non-standard signature** — `create(User, string $moduleSlug)` deviates from Laravel convention (`create` normally receives only User). Phase 3 should evaluate `Gate::check('create', [Record::class, $moduleSlug])`.

3. **No model factories for Module/Record** — Tests use `Model::create([...])` directly. Phase 2 should add `ModuleFactory` and `RecordFactory`.

4. **Eager-loading assumption** — `view/update/approve` use `$record->module->slug`, triggering a lazy load if the record wasn't loaded with `->load('module')`. Acceptable now; Phase 3 repositories will address eager loading.

---
