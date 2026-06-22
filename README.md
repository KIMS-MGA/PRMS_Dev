# Summary of Implemented Changes — Refactor Phase 2: Extract Core Services

**Date:** 2026-06-22
**Branch:** `refactor/phase-2-core-services`
**Base Branch:** `refactor/phase-1-foundation`
**Methodology:** Subagent-Driven Development with TDD (tests written before implementation)

---

## Overview

Phase 2 is **additive only** — no existing files were modified. Five new service classes and five corresponding Pest unit test files were created under `app/Services/` and `tests/Unit/Services/` respectively. The services extract duplicated business logic that was previously scattered across Livewire components, console commands, and event listeners.

---

## Commits (6 total)

| Commit | Message |
|--------|---------|
| `3d78e9f4` | refactor: extract NotificationService (Phase 2, step 2.1) |
| `ae885357` | refactor: extract ApprovalService (Phase 2, step 2.2) |
| `1a0c6e33` | refactor: extract FileVersioningService (Phase 2, step 2.3) |
| `36b6a360` | refactor: extract RecordPersistenceService (Phase 2, step 2.4) |
| `38ff1a73` | refactor: extract EditorTokenService (Phase 2, step 2.5) |
| `30b31add` | fix: address final review findings (Phase 2 services) |

---

## Files Created

### Services (5 new files)

| File | LOC | Purpose |
|------|-----|---------|
| `app/Services/NotificationService.php` | ~80 | Consolidates the 4-type recipient dispatch (`submitter` / `role` / `specific_user` / `specific_email`) previously duplicated in 4 files |
| `app/Services/ApprovalService.php` | ~200 | Consolidates the approval state machine (`submit`, `approve`, `returnForRevision`, `forwardToBranch`, `autoAdvance`) previously duplicated across 3 files |
| `app/Services/FileVersioningService.php` | ~60 | Extracts file versioning and legacy string-to-array migration logic from `DynamicRecordForm::persistRecord()` |
| `app/Services/RecordPersistenceService.php` | ~90 | Extracts the 90-LOC `persistRecord()` god method: file handling, record upsert, history creation, event dispatch |
| `app/Services/EditorTokenService.php` | ~60 | Extracts Sanctum token mint/revoke logic for `text_editor` fields, fixing the token accumulation edge case |

### Tests (5 new files)

| File | Tests | Assertions |
|------|-------|------------|
| `tests/Unit/Services/NotificationServiceTest.php` | 8 | 21 |
| `tests/Unit/Services/ApprovalServiceTest.php` | 16 | 50 |
| `tests/Unit/Services/FileVersioningServiceTest.php` | 11 | 37 |
| `tests/Unit/Services/RecordPersistenceServiceTest.php` | 13 | 30 |
| `tests/Unit/Services/EditorTokenServiceTest.php` | 11 | 25 (+ DB assertions) |
| **Total** | **59** | **170** |

---

## Service Details

### NotificationService
**Extracts from:** `DynamicRecordForm`, `DynamicRecordShow`, `ProcessWorkflows`, `SendDateFieldReminders`

**Public interface:**
```php
public function notifyRecipients(
    array $recipients,
    Record $record,
    string $message,
    ?string $subject = null
): void;
```

**Behavior:** Resolves each recipient by type, sends in-app `DynamicNotification` for `submitter`, `role`, and `specific_user` types; sends `StageNotificationMail` for `specific_email`. Each recipient is wrapped in try/catch — failures are logged and do not interrupt the batch.

---

### ApprovalService
**Extracts from:** `DynamicRecordForm`, `DynamicRecordShow`, `AdvanceDeadlineStages`

**Public interface:**
```php
public function __construct(private readonly NotificationService $notifications) {}

public function submit(Record $record, User $user): void;
public function approve(Record $record, User $user, string $comment = ''): void;
public function returnForRevision(Record $record, User $user, string $comment): void;
public function forwardToBranch(Record $record, User $user, int $branchIndex, string $comment = ''): void;
public function autoAdvance(Record $record): void;
```

**Uses Phase 1 enums:** `ApprovalAction` and `RecordStatus` throughout.

**Bugs fixed during review:**
- `forwardToBranch()` now throws `\RuntimeException` when branch target stage is deleted (previously silently left the record in a limbo state with `current_stage_id = null` and status `'Under Review'`)
- `autoAdvance()` now guards against being called on already-Completed/Returned records (previously could double-complete and double-notify)
- `returnForRevision()` now clears `stage_entered_at = null` for consistency with the rest of the state machine

---

### FileVersioningService
**Extracts from:** `DynamicRecordForm::persistRecord()` (lines 151–169)

**Public interface:**
```php
public function store(UploadedFile $file, string $disk = 'public', string $directory = 'attachments'): array;
public function prepend(UploadedFile $file, mixed $existingValue, string $disk = 'public', string $directory = 'attachments'): array;
public function migrateLegacyValue(mixed $value): array;
```

**Version entry shape:**
```php
['path', 'original_name', 'uploaded_at', 'uploaded_by', 'uploaded_by_name']
```

---

### RecordPersistenceService
**Extracts from:** `DynamicRecordForm::persistRecord()` (lines 120–209)

**Public interface:**
```php
public function __construct(private readonly FileVersioningService $fileVersioning) {}

public function save(Module $module, array $formData, ?Record $existing = null): Record;
```

**Behavior:** Resolves target module ID, processes all attachment fields (versioned and non-versioned), creates/updates the record, writes a history entry, and dispatches `RecordSaved` event.

**Bug fixed during review:** UploadedFile detection changed from duck-typing (`method_exists($value, 'store')`) to `instanceof \Illuminate\Http\UploadedFile` for precision and static analysis compatibility.

---

### EditorTokenService
**Extracts from:** `DynamicRecordForm::mount()` / `render()`, `DynamicRecordShow::mount()` / `render()`

**Public interface:**
```php
public function mint(User $user, string $prefix, array $fieldSlugs): array;
public function revoke(User $user, string $prefix): void;
public function isValid(string $token): bool;
```

**Bug fixed during review:** The legacy token cleanup `whereNotLike` pattern was `editor-%-%-%` (3 hyphens required) but new-format tokens are named `editor-{id}-{slug}` (only 2 hyphens) — causing live editing sessions to be broken after 8 hours. Fixed to `whereNotLike('editor-%-%')`.

**Additional hardening:** LIKE query patterns now escape SQL wildcards (`%`, `_`) in the prefix string to prevent unintended token matches.

---

## What Did NOT Change

The following existing files were not modified in Phase 2 (wiring services into existing code is Phase 3):

- `app/Livewire/Builder/DynamicRecordForm.php`
- `app/Livewire/Builder/DynamicRecordShow.php`
- `app/Listeners/ProcessWorkflows.php`
- `app/Console/Commands/AdvanceDeadlineStages.php`
- `app/Console/Commands/SendDateFieldReminders.php`
- Any other existing file in the project

---

## Risks and Follow-Up Notes

| Item | Notes |
|------|-------|
| Phase 3 wiring | The services are fully functional but not yet called by any existing code. Phase 3 must replace the inline logic in `DynamicRecordForm`, `DynamicRecordShow`, `ProcessWorkflows`, and the two console commands with calls to these services. Until then, the old logic remains active. |
| `RecordPersistenceService` in console context | `auth()->id()` returns null in scheduler/queue contexts — if this service is called from a console command, `created_by`/`updated_by`/`RecordHistory.user_id` will be null. A nullable int on those columns is required, or an optional `?User $actor` parameter should be added in a follow-up. |
| `FileVersioningService::store()` in console context | Uses `auth()` facade — same limitation as above. |
| ApprovalService legacy fallback | `autoAdvance()` uses the legacy `approver_role_id` path only (not `notify_on_enter_json`), matching the original console command behavior. If stages are configured with `notify_on_enter_json`, those recipients will not be notified during auto-advance. |
| `DynamicRecordShow::markReviewDone()` | Uses a different reviewer-count method than `DynamicRecordForm::markReviewDone()` — one uses permission-based count (form), the other uses role-based count (show). This divergence was not addressed in Phase 2 and is a follow-up item for the TextEditorReviewService planned in a later phase. |

---

*Generated: 2026-06-22*
