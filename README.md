# Refactor Phase 5 — Summary of Implemented Changes

**Branch:** `refactor/phase-5-infrastructure-quality`
**Base branch:** `refactor/phase-4-api-controller-cleanup`
**Implementation date:** 2026-06-23
**Author:** KIMS-MGA
**Method:** Test-Driven Development (TDD) — failing test written before each production change

---

## Overview

Phase 5 addressed six infrastructure and quality items carried forward from the
refactoring assessment: one performance issue (synchronous webhook HTTP calls),
one N+1 query, three documentation gaps, two dead-code artifacts, and two
hygiene fixes. No new user-facing features were added.

---

## Pre-Phase Corrective Fixes (committed on Phase 4 branch)

Before Phase 5 work began, two bugs were discovered and fixed on
`refactor/phase-4-api-controller-cleanup` with TDD.

### Fix 1 — Livewire 3 Constructor Injection (DynamicRecordForm)

**Commit:** `57c7e320`

**Problem:** `DynamicRecordForm` used PHP constructor injection for four
services (`ApprovalService`, `RecordPersistenceService`, `EditorTokenService`,
`TextEditorReviewService`). Livewire 3 instantiates components with zero
arguments (`new ComponentClass()`), causing an `ArgumentCountError` at runtime.

**Fix:** Replaced `__construct()` promoted parameters with non-promoted
protected properties. Services are now resolved via `boot()`, which Livewire
calls through the Laravel service container after instantiation.

**Files changed:**
- `app/Livewire/Builder/DynamicRecordForm.php` — `__construct()` → `boot()`

---

### Fix 2 — Livewire 3 Constructor Injection (DynamicRecordShow)

**Commit:** `4ebe623f`

**Problem:** Same Livewire 3 constructor injection pattern found in
`DynamicRecordShow` (3 services). Discovered proactively during Phase 5 audit.

**Fix:** Same `boot()` pattern applied.

**Files changed:**
- `app/Livewire/Builder/DynamicRecordShow.php` — `__construct()` → `boot()`

---

### Fix 3 — Notification Gap: Permission-Based Approvers (TDD)

**Commit:** `2bdf7268`

**Problem:** Users with a direct Spatie `approve-{module}` permission (ApprovalQueue
Path B) never received notifications when a record entered their queue. The
`ApprovalService::notifyLegacyStage()` method returned early when
`approver_role_id` was null, ignoring the permission-based path entirely.

**Fix:** Rewrote `notifyLegacyStage()` to fall through to permission-based
notification when no role is configured. Added `getUsersWithPermission()` helper
wrapping `User::permission($permission)->get()` in a
`PermissionDoesNotExist` try/catch.

**TDD:** Feature test `tests/Feature/Services/ApprovalServiceTest.php` (4 tests).
The RED test `it notifies users with direct approve permission when stage has no approver_role_id`
failed before the fix and passed after.

**Files changed:**
- `app/Services/ApprovalService.php` — `notifyLegacyStage()` + `getUsersWithPermission()`
- `tests/Feature/Services/ApprovalServiceTest.php` — new (4 tests)
- `tests/Unit/Services/ApprovalServiceTest.php` — `getUsersWithPermission()` stub added to `TestableApprovalService`

---

## Phase 5 Steps

### Step 5.1 — Queue Webhook Dispatch

**Commit:** `ee063ee8`

**Problem:** `ProcessWorkflows::fireWebhooks()` called `Http::timeout(10)->post()`
synchronously inside an event listener. A slow webhook URL blocked the web
request for up to 10 seconds per webhook, with no retry on failure.

**Changes:**

**New file — `app/Jobs/DispatchWebhook.php`:**
- Implements `ShouldQueue`
- Constructor: `Webhook $webhook`, `string $event`, `array $payload`
- `$tries = 3` with `backoff()` returning `[30, 60, 120]` seconds
- `handle()` builds HTTP headers (including optional HMAC `X-PRMS-Signature`),
  posts to the webhook URL, writes a `WebhookLog` on success or failure, and
  re-throws on exception so the queue retries

**Modified — `app/Listeners/ProcessWorkflows.php`:**
- Removed inline `Http::post()` try/catch block from `fireWebhooks()`
- Replaced with `DispatchWebhook::dispatch($webhook, $event->trigger, $payload)`
- Removed now-unused `Http` and `WebhookLog` imports; added `DispatchWebhook` import

**New test — `tests/Feature/Jobs/DispatchWebhookTest.php`** (6 tests):
- Job is queued when webhook is active
- Inactive webhooks are skipped
- `WebhookLog` success entry written on 2xx response
- HMAC `X-PRMS-Signature` header present when secret is set
- HMAC header absent when no secret
- Failure `WebhookLog` written and exception re-thrown on HTTP error

> **Infrastructure note:** Webhooks now execute asynchronously. Production must
> run a queue worker. If `QUEUE_CONNECTION=sync`, behaviour is unchanged but
> no async benefit is gained.

---

### Step 5.2 — N+1 Fix: Role-to-User Resolution

**Commit:** `70a52b86`

**Problem:** `ProcessWorkflows` called `User::role($roleName)->get()` once per
action in both `handleNotifyRole()` and `handleSendEmail()`. Multiple actions
targeting the same role triggered redundant DB queries.

**Changes:**

**Modified — `app/Listeners/ProcessWorkflows.php`:**
- Added `private array $roleUserCache = []` property
- Added `getCachedUsersByRole(string $roleName): Collection` helper using
  `$this->roleUserCache[$roleName] ??= User::role($roleName)->get()`
- `handleNotifyRole()` and `handleSendEmail()` now call `getCachedUsersByRole()` instead of `User::role()->get()` directly

**Note:** `once()` (Laravel 11 helper) was evaluated but keys by closure object ID —
two inline `once(fn() => ...)` expressions create distinct closure objects and
never share the cache across loop iterations. The array cache is the correct pattern.

**New test — `tests/Feature/Listeners/ProcessWorkflowsTest.php`** (2 tests):
- Two actions with the same role name: 4 SQL statements (2 per `User::role()`) reduced to 2 (cached)
- Three actions with two distinct roles (editor, reviewer, editor): 6 SQL reduced to 4 (editor cached on 3rd action)

---

### Step 5.3 — JSON Column DTO Documentation

**Commit:** `3d90057a`

**Problem:** Five JSON columns across three Eloquent models had undocumented
shapes. Callers had to trace `ProcessWorkflows`, `ApprovalService`, and Livewire
components to understand expected keys.

**New files — `app/Data/`:**

| File | Purpose |
|---|---|
| `WorkflowCondition.php` | Single condition entry: `field`, `operator`, `value` |
| `WorkflowConditions.php` | Top-level `conditions_json` wrapper: `logic`, `conditions[]` |
| `BranchOption.php` | Entry in `branches_json`: `label`, `stage_id` |
| `NotifyRecipient.php` | Entry in `notify_on_enter_json`: `type`, `value` |
| `DateReminder.php` | Entry in `date_reminders_json`: `field_slug`, `days_before`, `recipients[]` |

All five classes are PHP 8.1 `readonly` value objects.

**Modified models (PHPDoc annotations only — Eloquent `'array'` casts unchanged):**

- `app/Models/Workflow.php` — `@property WorkflowConditions $conditions_json`
- `app/Models/WorkflowStage.php` — `@property` for `branches_json`, `notify_on_enter_json`, `date_reminders_json`
- `app/Models/WorkflowAction.php` — `@property array<string, mixed> $config_json`

> Eloquent casts remain `'array'`. Changing to custom cast classes would require
> updating all array-access syntax (`$json['key']` → `$json->key`) across
> `ProcessWorkflows`, `ApprovalService`, `DynamicRecordForm`, `DynamicRecordShow`,
> and console commands. Deferred to a future data-layer phase.

---

### Step 5.4 — Dead Code Removal

**Commit:** `831c5bfb`

**Changes:**

- `tests/Pest.php` — removed the empty `function something() { // .. }` placeholder
  (Laravel/Pest scaffolding leftover)
- `tests/Unit/ExampleTest.php` — deleted entirely (`test('that true is true', ...)` scaffold)

> `tests/Feature/ExampleTest.php` was **kept** — it contains a real project test
> (`it redirects root to login`).

---

### Step 5.5 — MIME Validation Cleanup

**Commit:** `275d540f` (combined with 5.6)

**Problem:** `DynamicRecordForm.php` applied both `mimes:` (validates by file
content) and `extensions:` (validates by filename suffix) to attachment fields.
`extensions:` is strictly redundant when `mimes:` is already present; on some
server configurations it also triggers a PHP warning.

**Change — `app/Livewire/Builder/DynamicRecordForm.php` line 296:**

```
Before: 'file|max:51200|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip|extensions:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip'
After:  'file|max:51200|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip'
```

---

### Step 5.6 — ExportCsv Authorization Hygiene

**Commit:** `275d540f` (combined with 5.5)

**Problem:** `DynamicRecordController::exportCsv()` used a raw
`if (!auth()->user()->can(...)) abort(403)` guard. The base `Controller` has had
`AuthorizesRequests` since Phase 4.3, making `$this->authorize()` available.

**Change — `app/Http/Controllers/DynamicRecordController.php` line 30:**

```php
// Before
if (!auth()->user()->can("view-{$moduleSlug}")) abort(403);

// After
$this->authorize("view-{$moduleSlug}");
```

`$this->authorize()` raises an `AuthorizationException` (rendered as 403 by
Laravel's exception handler) rather than a raw `abort()`, consistent with the
rest of the controller layer.

---

## Commit Log

| SHA | Message |
|---|---|
| `57c7e320` | `fix: replace constructor injection with boot() in DynamicRecordForm` |
| `4ebe623f` | `fix: replace constructor injection with boot() in DynamicRecordShow` |
| `2bdf7268` | `fix: notify permission-based approvers when stage has no approver_role_id` |
| `ee063ee8` | `refactor: queue webhook dispatch via DispatchWebhook job (Phase 5, step 5.1)` |
| `70a52b86` | `refactor: cache role-to-user resolution in ProcessWorkflows (Phase 5, step 5.2)` |
| `3d90057a` | `refactor: add Data DTOs and annotate JSON columns on workflow models (Phase 5, step 5.3)` |
| `831c5bfb` | `refactor: remove dead-code placeholder from test suite (Phase 5, step 5.4)` |
| `275d540f` | `refactor: harden validation and authorisation in record form and export (Phase 5, steps 5.5-5.6)` |

---

## Files Changed (Phase 5 branch only)

| File | Action | Step |
|---|---|---|
| `app/Jobs/DispatchWebhook.php` | Created | 5.1 |
| `tests/Feature/Jobs/DispatchWebhookTest.php` | Created | 5.1 |
| `tests/Feature/Listeners/ProcessWorkflowsTest.php` | Created | 5.2 |
| `app/Data/WorkflowCondition.php` | Created | 5.3 |
| `app/Data/WorkflowConditions.php` | Created | 5.3 |
| `app/Data/BranchOption.php` | Created | 5.3 |
| `app/Data/NotifyRecipient.php` | Created | 5.3 |
| `app/Data/DateReminder.php` | Created | 5.3 |
| `app/Listeners/ProcessWorkflows.php` | Modified | 5.1 + 5.2 |
| `app/Models/Workflow.php` | Modified | 5.3 |
| `app/Models/WorkflowStage.php` | Modified | 5.3 |
| `app/Models/WorkflowAction.php` | Modified | 5.3 |
| `tests/Pest.php` | Modified | 5.4 |
| `tests/Unit/ExampleTest.php` | Deleted | 5.4 |
| `app/Livewire/Builder/DynamicRecordForm.php` | Modified | 5.5 |
| `app/Http/Controllers/DynamicRecordController.php` | Modified | 5.6 |

---

*Generated: 2026-06-23*
