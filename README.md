# Branch: `feature_TRC-Scheduling` — Changes vs `main`

**Branch:** `feature_TRC-Scheduling`
**Author:** KIMS-MGA
**Total Changes:** 16 files changed, 589 insertions(+), 74 deletions(-)

---

## Commits Overview

| # | Hash | Date | Message |
|---|------|------|---------|
| 1 | `0c228c64` | Jun 03, 2026 | Fix collaborative editor: Hocuspocus not syncing between users (Offline status) |
| 2 | `821edc05` | Jun 03, 2026 | Collaborative Text Editor - Debugging & Fix |
| 3 | `dc5dac51` | Jun 03, 2026 | Add TRC/Ad Referendum meeting schedule module |
| 4 | `5f557ae7` | Jun 03, 2026 | Fix Dashboard TRC Schedule to read from record_schedules table |
| 5 | `40b1335f` | Jun 03, 2026 | Enforce Proponent ownership: read-only for non-owner proposals |

---

## Commit 1 — `0c228c64` | Jun 03, 2026

### Fix: Hocuspocus Collaborative Editor Not Syncing (Offline Status)

**Files changed:**
- `prms/app/Livewire/Builder/DynamicRecordShow.php` — 18 lines changed
- `prms/app/Services/TokenMintingService.php` — 32 lines changed
- `prms/hocuspocus/server.js` — 41 lines added
- `prms/resources/js/text-editor.js` — 9 lines changed

**What changed:**

The collaborative text editor was displaying "Offline" status for concurrent users because the previous token minting strategy deleted **all** tokens for a given record prefix whenever any user loaded the show page — revoking tokens still being actively used by other browser sessions.

**`TokenMintingService.php`:**
- Changed from "delete all tokens for this prefix" to "delete only **expired** tokens (older than 8 hours) for this prefix" — preserves live tokens from concurrent collaborative sessions.
- Added detection of existing valid tokens via `pluck('name')->flip()` before issuing new ones.
- Switched to an explicit **delete-then-recreate** strategy per individual field token: since Sanctum only stores the hash (not the raw token), a fresh plain-text token must always be issued for the Blade view — but only the targeted field token is revoked, not all tokens.

**`DynamicRecordShow.php`:**
- Removed inline token minting loop from `mount()`.
- Delegated entirely to `TokenMintingService::mintEditorTokens()` — consistent with the safe per-field strategy.

**`hocuspocus/server.js`:**
- Added automatic `.env` loader at startup: reads the Laravel project's `.env` file one directory up and injects unset environment variables into `process.env`. Fixes the silent DB/API connection failures when the Node process is started without explicit env injection (e.g. `node server.js` locally).
- Updated MySQL pool to support `DB_USERNAME` (Laravel convention) as well as `DB_USER` for compatibility.
- Changed `DB_HOST` default from `'mysql'` to `'127.0.0.1'` and `APP_URL` from `'http://app'` to `'http://localhost'` to match local development environments.
- Added explicit `DB_PORT` support.

**`text-editor.js`:**
- `WS_URL` resolution now checks three sources in priority order:
  1. `window.HOCUSPOCUS_URL` (set by Blade for multi-environment support)
  2. `import.meta.env.VITE_HOCUSPOCUS_URL` (baked in at build time from `.env`)
  3. Derived fallback from current hostname + port 1234

---

## Commit 2 — `821edc05` | Jun 03, 2026

### Fix: Collaborative Text Editor — Debugging & Editor Token Serialization

**Files changed:**
- `prms/app/Livewire/Builder/DynamicRecordShow.php` — 3 lines changed
- `prms/hocuspocus/server.js` — 35 lines changed

**What changed:**

**`DynamicRecordShow.php`:**
- Changed `$editorTokens` from `protected array` to `public array` with the `#[\Livewire\Attributes\Locked]` attribute. This allows Livewire to serialize the tokens in its encrypted snapshot (so they survive page re-hydration) while the `Locked` attribute prevents client-side mutation of the token values via Livewire's wire protocol.

**`hocuspocus/server.js`:**
- Expanded `onAuthenticate` with detailed step-by-step diagnostic logging: logs the document name, first 8 characters of the token, the target validation URL, and the full HTTP response body (truncated to 300 chars).
- Separated the network fetch from the response body parsing into two distinct `try/catch` blocks — previously, a network error and an auth rejection were handled by the same catch clause, making it impossible to distinguish between "Laravel is unreachable" and "Laravel rejected the token."
- Changed body consumption from `res.json()` to `res.text()` followed by explicit `JSON.parse()` — ensures the raw response body is always captured for logging before any parse attempt.
- Added explicit `console.error` for HTTP non-OK responses with the status code.

---

## Commit 3 — `dc5dac51` | Jun 03, 2026

### Feature: TRC/Ad Referendum Meeting Schedule Module

**Files changed:**
- `prms/app/Livewire/Builder/RecordScheduler.php` — new (173 lines)
- `prms/app/Models/RecordSchedule.php` — new (29 lines)
- `prms/database/migrations/2026_06_03_000001_create_record_schedules_table.php` — new (29 lines)
- `prms/resources/views/livewire/builder/dynamic-record-show.blade.php` — 3 lines added
- `prms/resources/views/livewire/builder/record-scheduler.blade.php` — new (128 lines)

**What changed:**

A new meeting schedule module was added to the proposal detail page, displayed in the right column after the Approval Log. The feature allows authorized users to schedule, reschedule, and cancel TRC or Ad Referendum meeting dates for proposals currently at those workflow stages.

**New database table — `record_schedules`:**

| Column | Type | Description |
|--------|------|-------------|
| `record_id` | FK | Links to the proposal record |
| `stage_id` | FK (nullable) | The workflow stage at time of scheduling |
| `scheduled_by` | FK | User who created the schedule entry |
| `scheduled_at` | datetime | The meeting date and time |
| `notes` | text (nullable) | Optional location/link notes |
| `action` | string | `scheduled` \| `rescheduled` \| `cancelled` |

**`RecordSchedule` model** (`app/Models/RecordSchedule.php`):
- Belongs to `Record`, `WorkflowStage` (via `stage_id`), and `User` (via `scheduled_by` as `scheduler()`).
- Casts `scheduled_at` as `datetime`.

**`RecordScheduler` Livewire component** (`app/Livewire/Builder/RecordScheduler.php`):
- Visibility: Panel only renders when `record->currentStage->name` is `TRC Deliberation` or `Ad Referendum Review` (or when schedule history already exists for the record).
- **Role access**: `canSchedule()` returns `true` only for `TRC Secretariat` role or `super admin` — all other roles are read-only.
- **`saveSchedule()`**: Validates date (`after_or_equal:today`), time (`H:i`), and optional notes. Determines `action` as `scheduled` or `rescheduled` based on whether a prior non-cancelled entry exists. Notifies all active users via `DynamicNotification` (database + email) on each save.
- **`cancelSchedule()`**: Creates a new `cancelled` history row referencing the last active `scheduled_at`.
- **`startEditing()` / `cancelEditing()`**: Toggle the inline form, pre-filling with the current active schedule.
- **Schedule history**: All entries (scheduled, rescheduled, cancelled) are displayed in reverse chronological order with actor name, meeting date/time, and notes.

**`record-scheduler.blade.php`** (view):
- Root `<div>` is always rendered (satisfies Livewire's single-root-tag requirement); the panel is hidden via `style="display:none"` when neither condition applies.
- Shows the current scheduled date/time in a purple-accented card.
- Editable form with date, time, and notes fields, plus a Reschedule / Cancel Schedule button pair for authorized users.
- Schedule history log at the bottom of the panel.

**`dynamic-record-show.blade.php`:**
- Added `<livewire:builder.record-scheduler :record-id="$record->id" :module-slug="$moduleSlug" />` in the right column between the Approval Log and the Remarks section.

---

## Commit 4 — `5f557ae7` | Jun 03, 2026

### Fix: Dashboard TRC Schedule Table Reading from `record_schedules`

**Files changed:**
- `prms/app/Livewire/Builder/Dashboard.php` — 20 lines changed
- `prms/resources/views/livewire/builder/dashboard.blade.php` — 39 lines changed

**What changed:**

The "Upcoming TRC Schedule" table on the Dashboard was reading from `record.data['date_scheduled']` — a legacy JSON field approach — while the new scheduler writes to the `record_schedules` table. The two systems were disconnected, so newly saved schedules never appeared on the Dashboard.

**`Dashboard.php`:**
- Removed the `whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.date_scheduled')) IS NOT NULL")` query.
- Replaced with a `RecordSchedule` query using `MAX(id)` per `record_id` to identify the most recently inserted row for each record (auto-increment guarantees this is the latest entry).
- Filters out entries where `action = 'cancelled'` — cancelled schedules are excluded from the table.
- Applies the `showPastTrc` toggle via `where('scheduled_at', '>=', now()->startOfDay())`.
- Eager-loads `record.module`, `stage`, and `scheduler` relations for display.
- Added `use App\Models\RecordSchedule;` import.

**`dashboard.blade.php`:**
- Updated table headers: replaced `Date Scheduled` with `Date & Time`; added `Stage` column.
- Updated row rendering to use `$entry->scheduled_at` (full datetime with time displayed separately), `$entry->record->data['title']`, `$entry->stage->name` (shown as a purple badge), and `$entry->notes` (shown beneath the title in italics).
- Updated `View →` link to safely use `$entry->record->module->slug` and `$entry->record_id` with null guards.
- Updated `colspan` on empty state row from `3` to `4` to match the new column count.

---

## Commit 5 — `40b1335f` | Jun 03, 2026

### Feature: Enforce Proponent Ownership — Read-Only for Non-Owner Proposals

**Files changed:**
- `prms/app/Livewire/Builder/Dashboard.php` — 8 lines changed
- `prms/app/Livewire/Builder/DynamicRecordForm.php` — 2 lines changed
- `prms/app/Livewire/Builder/DynamicRecordIndex.php` — 13 lines changed
- `prms/app/Livewire/Builder/DynamicRecordShow.php` — 7 lines changed
- `prms/app/Services/RecordApprovalService.php` — 19 lines changed
- `prms/resources/views/livewire/builder/dynamic-record-index.blade.php` — 9 lines changed

**What changed:**

Proponent-role users could previously edit or delete any record in the system as long as they held the `edit-{module}` or `delete-{module}` permission. This update enforces ownership: a Proponent may only modify records they originally created. All other Proponent users receive read-only access.

**Access control matrix (post-fix):**

| Role | View | Edit own | Edit others | Delete own | Delete others |
|------|------|----------|-------------|------------|---------------|
| Proponent | ✓ | ✓ | ✗ | ✓ | ✗ |
| TRC Secretariat | ✓ | ✓ | ✓ | ✓ | ✓ |
| Super Admin | ✓ | ✓ | ✓ | ✓ | ✓ |

**`RecordApprovalService::canEditRecord()`:**
- Added optional `?Record $record` parameter to the method signature.
- Proponents who pass the `can("edit-{slug}")` check are now additionally checked against `$record->created_by !== $user->id` — returns `false` if they do not own the record.
- Privileged roles (`super admin`, `TRC Secretariat`) bypass the ownership check entirely.

**`DynamicRecordForm::mount()`:**
- Passes `$this->record` (the loaded record object) to `canEditRecord()` — server-side 403 is now thrown if a non-owner Proponent navigates to the edit URL directly.

**`DynamicRecordIndex::deleteRecord()`:**
- After confirming `can("delete-{slug}")`, checks `$record->created_by !== auth()->id()` for Proponent role — aborts 403 for non-owners even if they hold the delete permission.

**`DynamicRecordIndex::render()`:**
- Added `$isProponent = $user->hasRole('Proponent') && !$user->hasRole('super admin')` passed to the view.

**`dynamic-record-index.blade.php`:**
- Computes per-row `$isOwner = $rec->created_by === auth()->id()` and derives `$canEditThis` / `$canDeleteThis` with the ownership gate applied.
- Edit and Delete buttons are now conditionally hidden per row for non-owner Proponents.

**`DynamicRecordShow::render()`:**
- Added `$isOwner = $this->record->created_by === $user->id`.
- `$canEdit` now includes `(!$user->hasRole('Proponent') || $user->hasRole('super admin') || $isOwner)` — the Edit button in the header is hidden for non-owner Proponents.

**`Dashboard::getScopedRecordQuery()`:**
- Added `$user->hasRole('Proponent')` to the privileged-role bypass — Proponents now see all records in the dashboard KPI counts, status chart, trend chart, module stats, and recent activity (consistent with the unscoped TRC schedule table). The `my_records_only` restriction continues to apply only at the module record-list level.

---

## Summary of All Files Changed

| File | Change |
|------|--------|
| `prms/app/Livewire/Builder/Dashboard.php` | Updated — Proponent added to dashboard bypass; TRC schedule query replaced with `record_schedules` lookup |
| `prms/app/Livewire/Builder/DynamicRecordForm.php` | Updated — passes `$record` to `canEditRecord()` for server-side ownership enforcement |
| `prms/app/Livewire/Builder/DynamicRecordIndex.php` | Updated — ownership check in `deleteRecord()`; `$isProponent` flag passed to view |
| `prms/app/Livewire/Builder/DynamicRecordShow.php` | Updated — `$editorTokens` visibility + `Locked` attribute fix; Proponent ownership gate on `$canEdit`; token minting delegated to `TokenMintingService` |
| `prms/app/Livewire/Builder/RecordScheduler.php` | New — Livewire component for TRC/Ad Referendum meeting schedule management |
| `prms/app/Models/RecordSchedule.php` | New — Eloquent model for `record_schedules` table |
| `prms/app/Services/RecordApprovalService.php` | Updated — `canEditRecord()` accepts `?Record` and enforces Proponent ownership |
| `prms/app/Services/TokenMintingService.php` | Updated — per-field delete-then-recreate strategy; preserves concurrent session tokens |
| `prms/database/migrations/2026_06_03_000001_create_record_schedules_table.php` | New — creates `record_schedules` table |
| `prms/hocuspocus/server.js` | Updated — automatic `.env` loader; expanded `onAuthenticate` diagnostics; separate network/parse error handling |
| `prms/resources/js/text-editor.js` | Updated — three-tier `WS_URL` resolution (`window.HOCUSPOCUS_URL` → `VITE_HOCUSPOCUS_URL` → hostname fallback) |
| `prms/resources/views/livewire/builder/dashboard.blade.php` | Updated — TRC schedule table uses `record_schedules` fields; added Stage column and time display |
| `prms/resources/views/livewire/builder/dynamic-record-index.blade.php` | Updated — per-row Edit/Delete visibility gate for Proponent ownership |
| `prms/resources/views/livewire/builder/dynamic-record-show.blade.php` | Updated — `RecordScheduler` component injected after Approval Log |
| `prms/resources/views/livewire/builder/record-scheduler.blade.php` | New — scheduling panel UI: current schedule display, date/time form, schedule history log |
