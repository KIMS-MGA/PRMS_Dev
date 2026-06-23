# Phase 4 Refactor — Summary of Implemented Changes

**Date:** 2026-06-23
**Branch:** `refactor/phase-4-api-controller-cleanup`
**Base branch:** `refactor/phase-3-slim-livewire-components`

---

## Overview

Phase 4 extracted the remaining inline business logic from the HTTP layer into testable classes, closed a SQL injection surface (P3-A), and replaced `TextEditorController`'s duplicated inline authorization helper with the `RecordPolicy` established in Phase 1.

---

## Step 4.1 — RecordRepository (P2-B + P3-A)

### Created: `app/Repositories/RecordRepository.php`

Extracted the 50-line search/filter/sort query from `DynamicApiController::index()` into `RecordRepository::search(Module $module, array $filters): LengthAwarePaginator`.

**Behaviors preserved:**
- `status` exact-match filter
- Full-text JSON search across all module fields via `JSON_UNQUOTE/JSON_EXTRACT`
- `date_from` / `date_to` date range filters
- `assigned_to` / `created_by` user filters
- `sort_by` allow-list (`created_at`, `updated_at`, `status`) + `sort_dir`
- `per_page` capped at 100

**P3-A fix:** Added slug guard before JSON path interpolation:
```php
if (!preg_match('/^[a-z0-9_\-]+$/', $field->slug)) continue;
```

### Created: `tests/Feature/Api/DynamicApiControllerTest.php`

New feature tests (9 tests, 1 skipped on SQLite):
- `it returns paginated records for a module`
- `it filters by status`
- `it filters by date range` *(skipped on SQLite — requires MySQL JSON_UNQUOTE)*
- `it filters by created_by`
- `it applies default sorting (created_at desc)`
- `it respects per_page cap at 100`
- `it returns 403 when token lacks read ability`
- `it stores a record via api`
- `it updates a record via api`

---

## Step 4.2 — Form Requests (P2-E)

### Created: `app/Http/Requests/Api/StoreRecordRequest.php`
### Created: `app/Http/Requests/Api/UpdateRecordRequest.php`

Both Form Requests:
- Resolve the module via `$this->route('moduleSlug')` inside `rules()`
- Delegate to `RecordValidationRuleFactory::forApi()` (from `App\Support`)
- Return `authorize(): true` — authorization remains in the controller's `checkAbility()` (dual Sanctum + Spatie check, per Section 7.1 decision)

### Modified: `app/Http/Controllers/Api/DynamicApiController.php`

- Injected `RecordRepository` via constructor DI
- `index()` now calls `$this->repository->search($module, $request->only([...]))`
- `store()` type-hints `StoreRecordRequest`; removed inline `$request->validate()`
- `update()` type-hints `UpdateRecordRequest`; removed inline `$request->validate()`
- Removed private `fieldRules()` method (16 LOC) — superseded by `RecordValidationRuleFactory::forApi()`

**LOC:** 173 → 109 (−64)

---

## Step 4.3 — TextEditorController → RecordPolicy (P3-E)

### Modified: `app/Http/Controllers/TextEditorController.php`

Replaced the 20-line `authorizeRecordAccess()` helper with `$this->authorize('viewEditor', $record)` in all 8 public methods that accept a `Record` parameter:
- `getHistory`, `storeHistory`, `getComments`, `storeReply`, `storeComment`, `storeImage`, `resolveComment`, `getReviewStatus`

The private `authorizeRecordAccess()` method was removed entirely.

**LOC:** 273 → 251 (−22)

### Modified: `app/Http/Controllers/Controller.php`

Added `use Illuminate\Foundation\Auth\Access\AuthorizesRequests;` trait to the abstract base Controller, enabling `$this->authorize()` across all controllers. The base Controller previously had no traits.

### Created: `tests/Feature/Controllers/TextEditorControllerTest.php`

New feature tests (4 tests):
- `it allows a user with view permission to access editor endpoints`
- `it allows super admin to access editor endpoints`
- `it denies a user with no module permissions`
- `it allows a user with a matching stage approver role`

---

## Files Created, Modified, or Removed

| File | Action | Before LOC | After LOC |
|---|---|---|---|
| `app/Repositories/RecordRepository.php` | **Created** | — | 58 |
| `app/Http/Requests/Api/StoreRecordRequest.php` | **Created** | — | 22 |
| `app/Http/Requests/Api/UpdateRecordRequest.php` | **Created** | — | 22 |
| `tests/Feature/Api/DynamicApiControllerTest.php` | **Created** | — | 165 |
| `tests/Feature/Controllers/TextEditorControllerTest.php` | **Created** | — | 62 |
| `app/Http/Controllers/Api/DynamicApiController.php` | **Modified** | 173 | 109 |
| `app/Http/Controllers/TextEditorController.php` | **Modified** | 273 | 251 |
| `app/Http/Controllers/Controller.php` | **Modified** | 5 | 9 |

---

## Approach Decisions (Section 7)

### 7.1 — `checkAbility()` placement: Option A (kept in controller)
The dual Sanctum token + Spatie permission check is API-token specific and not reusable elsewhere. Splitting it between `Form Request::authorize()` and the controller would fragment related logic for no benefit.

### 7.2 — `UpdateRecordRequest` merge strategy: stays in controller
The `array_merge($record->data, $validated['data'])` merge remains in the controller action. The Form Request only validates incoming data.

---

## Commits

```
1c0c4834 refactor: extract RecordRepository from DynamicApiController (Phase 4, step 4.1)
0878b50c refactor: introduce StoreRecordRequest and UpdateRecordRequest (Phase 4, step 4.2)
e66b3cb6 refactor: wire TextEditorController to RecordPolicy::viewEditor (Phase 4, step 4.3)
```

---

## Potential Risks and Follow-up Notes

| Risk | Severity | Action |
|---|---|---|
| `RecordRepository::search()` uses MySQL `JSON_UNQUOTE/JSON_EXTRACT` | Low | Tests on SQLite skip (same pattern as `SendDateFieldRemindersTest`); no behavior change |
| `DynamicRecordIndex` Livewire component has a near-duplicate search query | Low | Out of scope for Phase 4; note for Phase 5 or dedicated query-layer phase |
| `DynamicRecordShow` stage-field methods (95 LOC) still inline | Low | Deferred from Phase 3; belongs in a StageFieldService (future phase) |
| `DynamicRecordController::exportCsv()` inline auth | Low | Minor; not in Phase 4 scope — can be addressed in Phase 5 hygiene pass |
| `AuthorizesRequests` trait now added to base Controller | Low | Enables `$this->authorize()` globally; no behavioral change to existing controllers that don't call it |
