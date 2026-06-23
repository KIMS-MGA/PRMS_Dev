# Branch Summary: `bugfix/hocuspocus-data-sync-loss` → `dev_staging`

**Branch:** `bugfix/hocuspocus-data-sync-loss`
**Target:** `dev_staging`
**Commits ahead of `main`:** 52
**Files changed vs `main`:** 82 files — 6,519 insertions, 1,172 deletions

---

## What This Branch Adds

This branch contains two major bodies of work:

1. **A complete backend service layer and Livewire UI** built on top of the existing PRMS dynamic record system — covering the full proposal lifecycle from submission through multi-stage approval.
2. **A series of bugfixes for the Hocuspocus collaborative text editor** that caused editor content to be wiped on save, reviewer edits to be lost, and the WebSocket server to fail authentication.

---

## New Features

### 1. Approval Workflow Service Layer

Six focused services were extracted from the Livewire components to handle the business logic cleanly and make each piece independently testable.

| Service | What It Does |
|---|---|
| `ApprovalService` | Submits records, approves/rejects stages, returns for revision, and forwards to branch stages |
| `NotificationService` | Sends in-app and email notifications to roles, specific users, or the record submitter |
| `FileVersioningService` | Stores and prepends file uploads with full version history |
| `RecordPersistenceService` | Saves record data and writes a history entry on every create/update |
| `EditorTokenService` | Mints and revokes per-session Hocuspocus authentication tokens |
| `TextEditorReviewService` | Tracks which reviewers have marked each editor field as done |

### 2. Livewire UI Components

| Component | Who Uses It | What It Does |
|---|---|---|
| `DynamicRecordForm` | Proponents | Create/edit proposals; attach files; submit for approval; see approval log and comments |
| `DynamicRecordShow` | Reviewers / Approvers | View record; edit stage fields; approve, return for revision, or forward to a branch stage |
| `NotificationBell` | All users | Notification bell in the nav bar with unread count; polls for updates automatically |

### 3. Role-Based Authorization

A `RecordPolicy` was added and registered with Laravel's `Gate`. It controls:

- **View** — users with `view-{module}` permission (or super admin)
- **Update** — users with `edit-{module}` permission
- **Approve** — users with `approve-{module}` permission or the stage's assigned role
- **Review** — users with `review-{module}` permission (super admin explicitly blocked here to prevent unintended quorum saturation)
- **View Editor** — any user with any module-level permission, or a matching stage approver role (used by the Hocuspocus token endpoint)

### 4. API Hardening (Phase 4 Refactoring)

- Extracted a `RecordRepository` from `DynamicApiController` to separate query logic from HTTP concerns
- Replaced inline `validate()` calls with `StoreRecordRequest` and `UpdateRecordRequest` Form Requests
- Wired `TextEditorController` to `RecordPolicy::viewEditor` so the Hocuspocus token endpoint is properly gated

### 5. Performance Improvements (Phase 5 Refactoring)

- Webhook dispatches are now queued via a `DispatchWebhook` job instead of firing synchronously in a request
- Role-to-user resolution in `ProcessWorkflows` is cached per role name to avoid duplicate queries when multiple workflow actions share the same role
- Console commands slimmed to thin wrappers — business logic moved into services

### 6. Shared Support Classes

| Class | Purpose |
|---|---|
| `ApprovalAction` / `RecordStatus` enums | Type-safe constants for approval actions and record statuses |
| `RecordValidationRuleFactory` | Generates validation rules for both the Livewire form and the REST API from the same field definitions |
| `Module::resolvedModuleId()` / `resolvedFields()` | Handles source-module linking so aliased modules inherit fields from a parent |
| `BranchOption`, `NotifyRecipient`, `WorkflowCondition` DTOs | Typed value objects for JSON columns on workflow models |

### 7. Database Seeders

A complete set of seeders was added for local development and staging:

- `RoleAndPermissionSeeder` — roles and permissions
- `UserSeeder` — default system users
- `ModuleSeeder` / `ModuleFieldSeeder` — module definitions and field schemas
- `WorkflowSeeder` — approval workflow stages and actions
- `PolicyProposalsSeeder` / `LoginSlideSeeder` / `SuperAdminSeeder` — system-specific data

---

## Bugs Fixed

### Bug 1 — Proponent Save / Submit Wipes Editor Content

**What was happening:** Saving a draft or submitting a proposal cleared the text editor content in the database.

**Why:** `$editorTokens` was declared `protected` in the Livewire components. Livewire only serialises `public` properties into its snapshot. On every save/submit, the snapshot deserialized with empty `$editorTokens`, which triggered `EditorTokenService::mint()` again — and `mint()` revokes all existing tokens before creating new ones. The browser's Hocuspocus connection became invalid mid-session, `isSynced` dropped to `false`, and the commit hook was blocked from injecting the editor content into the save request.

**Fix:** Changed `protected array $editorTokens` → `public array $editorTokens` in both Livewire components.

---

### Bug 2 — Reviewer Edits Lost on Mark Review Done

**What was happening:** Editor changes made by a Reviewer were not saved when "Mark Review Done" was clicked.

**Why:** `DynamicRecordShow` had no `$editorData` property and no hidden input binding for text editor fields. Content lived only in the Hocuspocus CRDT binary state and never reached the PHP component.

**Fix:** Added `public array $editorData` to `DynamicRecordShow`, seeded from `$record->data` on mount. Added a hidden text input (`wire:model="editorData.{slug}"`) in the blade view for each text editor field. `markReviewDone()` now merges `$editorData` into `$record->data` before recording the review.

---

### Bug 3 — Partial Reviews Not Persisting Content

**What was happening:** When a reviewer marked a field done but was not the last reviewer, no data was saved to the database, leaving the record in a stale state.

**Why:** The `records.data` save inside `markReviewDone()` was placed inside the "all reviewers done" quorum check, so it only ran when the final reviewer acted.

**Fix:** Moved the save unconditionally before the quorum check in both `DynamicRecordForm` and `DynamicRecordShow`.

---

### Bug 4 — Livewire Commit Hook Removed (Regression)

**What was happening:** Editor HTML was not being injected into Livewire requests, making all saves dependent on Alpine's `wire:model` binding — which Livewire's DOM morphing could reset between the last editor update and the actual save.

**Why:** A prior commit removed the `Livewire.hook('commit', ...)` hook entirely, citing it as buggy. The bug was actually a missing `isSynced` guard, not the hook itself.

**Fix:** Reinstated the commit hook in `resources/js/app.js` with:
- `isSynced` guard — prevents an unsynced editor from overwriting saved content
- `isNew` bypass — new records have no saved content to protect
- `data-readonly` check — skips editors that the current user has already marked done
- Dynamic `getAttribute('wire:model')` — works for both form (`data.{slug}`) and show (`editorData.{slug}`) without hardcoding

---

### Bug 5 — Hocuspocus WebSocket Authentication Always Failing

**What was happening:** Hocuspocus WebSocket connections failed immediately. The editor showed "Connecting…" and never synced.

**Why:** Three environment variables in `hocuspocus/server.js` fell back to Docker-era defaults (`APP_URL=http://app`, `DB_USER=prms`, `TABLE_PREFIX=jea_`) because the Node.js process does not read Laravel's `.env` automatically.

**Fix:** Added `dotenv` to `hocuspocus/package.json`. `server.js` now loads `../.env` at startup. DB user resolution uses `DB_USERNAME || DB_USER || 'root'` to handle both Laravel and Docker conventions.

---

### Bug 6 — DOM Morphing Data Loss on Remount

**What was happening:** When Livewire re-rendered the page (e.g., after a comment was posted), the editor lost its unsaved content because Livewire's DOM morph replaced the container with a fresh empty element.

**Fix:** TipTap editor now reads initial content from the container's `data-content` dataset attribute on mount, ensuring a re-mount re-seeds from the last known server value rather than starting blank.

---

### Bug 7 — isSynced State Tracking / Offline Save Protection

**What was happening:** If the Hocuspocus WebSocket was not yet connected when the user clicked Save, an empty string could overwrite the saved content in the database.

**Fix:** Added `isSynced` state to the editor initialization. The commit hook checks `isSynced` and skips injecting editor content if the WebSocket has never confirmed sync. New records bypass this guard (no existing server content to protect).

---

### Bug 8 — Auto-Approve 403 for Reviewers in markReviewDone

**What was happening:** When the last reviewer marked a field done, the auto-approve triggered by `markReviewDone()` threw a 403 because the reviewer role does not hold the `approve` permission.

**Fix:** `approvalService->approve()` is called with `checkGate: false` inside `markReviewDone()` since the quorum check already confirmed all reviewers are done.

---

### Bug 9 — Missing Save Changes Button for Proponent After Review

**What was happening:** After all reviewers marked their review done, the record auto-advanced to the next stage (e.g., `Submitted` status). The proponent could still open the edit form but only saw a Cancel button — no way to save a newly attached document.

**Why:** The save buttons in `dynamic-record-form.blade.php` were only rendered for `Draft` and `Returned` statuses. After auto-approval advanced the record, the status no longer matched that condition.

**Fix:** Added a "Save Changes" button that appears whenever the record is editable and not in `Draft` or `Returned` status. It calls the existing `save()` method which preserves the current workflow status without disrupting the approval chain.

---

## What Changed vs `main` Branch

### Added (not in `main`)

- Full approval workflow with multi-stage, multi-reviewer support
- 6 extracted service classes under `app/Services/`
- `RecordPolicy` with 6 gate methods
- `RecordRepository` for API query separation
- `StoreRecordRequest` / `UpdateRecordRequest` Form Requests
- `DispatchWebhook` queued job
- `NotificationBell` Livewire component
- Backed enums: `ApprovalAction`, `RecordStatus`
- DTOs: `BranchOption`, `DateReminder`, `NotifyRecipient`, `WorkflowCondition`
- `RecordValidationRuleFactory` support class
- `Module::resolvedModuleId()` / `resolvedFields()` helpers
- Hocuspocus `dotenv` integration
- Full database seeders suite
- `tests/Feature/Livewire/DynamicRecordFormSaveButtonTest.php` (new)
- 19 new test files across Unit and Feature — covering all services, policies, jobs, controllers, and Livewire components

### Modified (existing files changed significantly)

| File | What Changed |
|---|---|
| `app/Livewire/Builder/DynamicRecordForm.php` | Fully rebuilt — all approval/review/save logic delegated to services; editor tokens public; save buttons fixed |
| `app/Livewire/Builder/DynamicRecordShow.php` | Fully rebuilt — `$editorData` added; reviewer flow wired to services |
| `resources/js/app.js` | Commit hook reinstated with sync guard, readonly check, isNew bypass |
| `resources/views/layouts/app.blade.php` | Notification bell added; layout updated |
| `hocuspocus/server.js` | dotenv loaded; env var defaults corrected |
| `app/Listeners/ProcessWorkflows.php` | Role resolution cached |
| `app/Http/Controllers/Api/DynamicApiController.php` | Queries moved to RecordRepository |

### Removed

- Placeholder test stubs (`tests/Unit/ExampleTest.php`, empty `tests/Pest.php` unit section)
- Dead-code in console commands (moved to services)

---

## Regression Test Results

Tests were run on `2026-06-23` against this branch with a fresh SQLite test database.

```
Tests:    188 passed, 3 skipped, 0 failed
Assertions: 431
Duration: ~22 seconds
```

| Test Suite | Result |
|---|---|
| Unit — ApprovalService (19 cases) | PASS |
| Unit — EditorTokenService (11 cases) | PASS |
| Unit — FileVersioningService (11 cases) | PASS |
| Unit — NotificationService (8 cases) | PASS |
| Unit — RecordPersistenceService (13 cases) | PASS |
| Unit — TextEditorReviewService (13 cases) | PASS |
| Unit — RecordValidationRuleFactory (16 cases) | PASS |
| Unit — Enums (6 cases) | PASS |
| Feature — ApprovalService integration (6 cases) | PASS |
| Feature — DynamicApiController (8 cases, 1 skipped*) | PASS |
| Feature — AdvanceDeadlineStages console (5 cases) | PASS |
| Feature — SendDateFieldReminders console (1 case, 2 skipped*) | PASS |
| Feature — TextEditorController (4 cases) | PASS |
| Feature — DispatchWebhook job (6 cases) | PASS |
| Feature — ProcessWorkflows listener (2 cases) | PASS |
| Feature — RecordPolicy (18 cases) | PASS |
| Feature — Module model (4 cases) | PASS |
| Feature — TextEditorCommentThread (4 cases) | PASS |
| Feature — SecurityHardening (7 cases) | PASS |
| Feature — Livewire DynamicRecordFormSaveButton (4 cases) | PASS |
| Feature — Auth (all flows) | PASS |
| Feature — Profile | PASS |

> *3 tests skipped: require MySQL `JSON_UNQUOTE` function, not available on the SQLite test driver. These tests pass when run against a real MySQL database.

**No regressions. All existing tests continue to pass.**

---

## Deployment Checklist

The following one-time steps are required after pulling this branch into `dev_staging`:

- [ ] **`npm install`** inside `prms/hocuspocus/` — installs the new `dotenv` dependency
- [ ] **`npm run build`** inside `prms/` — recompiles `resources/js/app.js` with the reinstated commit hook
- [ ] **Restart the Hocuspocus server** — picks up the new `server.js` with dotenv loading
- [ ] Verify `prms/.env` has `APP_URL=http://localhost:{port}` set to your dev/staging URL
- [ ] Run `php artisan db:seed` if setting up a fresh database with the new seeders

No database migrations required. No breaking changes to existing data or API contracts.

---

## Data Flow After Fix

```
[User types in TipTap editor]
        │
        ▼
[HocuspocusProvider syncs via WebSocket → isSynced = true]
        │
        ▼
[onUpdate → syncToLivewire() sets hiddenInput.value + dispatches input event]
        │
        ▼
[Livewire commit hook fires before every request]
   → checks isSynced (skips if not yet synced)
   → reads editor.getHTML()
   → injects into commit.updates[wire:model]
        │
        ▼
[Livewire request carries editor HTML in payload]
        │
        ▼
[PHP: $data[slug] or $editorData[slug] updated in component]
        │
        ▼
[Save action: persistRecord() / markReviewDone() → records.data written to DB]
        │
        ▼ (parallel)
[Hocuspocus: CRDT binary_state written to text_editor_documents]
```
