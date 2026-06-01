# Branch: `general-refinement` — Changes vs `main`

**Branch:** `general-refinement`
**Author:** KIMS-MGA
**Total Changes:** 9 files changed, 1,065 insertions(+), 400 deletions(-)

---

## Commits Overview

| # | Hash | Date | Message |
|---|------|------|---------|
| 1 | `95c9042` | May 28, 2026 | Refactoring of the DynamicRecordForm Livewire component into Service classes |
| 2 | `d830d069` | May 28, 2026 | Restrict stats count based on the account |
| 3 | `6cabead` | Jun 1, 2026 | docs: replace default Laravel README with PRMS project documentation |
| 4 | `0cb9941` | Jun 1, 2026 | dEncapsulating all notification processing logic into dedicated NotificationController and referencing its method in routes/web.php. Clean up routes/web.php removed three inline closures and mapped to NotificationController. |

---

## Commit 1 — `95c9042` | May 28, 2026

### Refactoring: `DynamicRecordForm` → Service Classes

**Files changed:**
- `app/Livewire/Builder/DynamicRecordForm.php` — 359 lines removed, heavily slimmed down
- `app/Services/RecordApprovalService.php` — new (398 lines)
- `app/Services/RecordCommentService.php` — new (45 lines)
- `app/Services/RecordSaveService.php` — new (101 lines)
- `app/Services/TokenMintingService.php` — new (44 lines)
- `public/storage/.gitignore` — new (2 lines)

**What changed:**

The `DynamicRecordForm` Livewire component was refactored to remove its heavy business logic responsibilities. All logic was extracted into 4 clean, domain-specific Service classes under the `App\Services` namespace:

| New Service | Responsibility |
|---|---|
| `TokenMintingService` | Secure generation, prefixing, and cleanup of editor tokens |
| `RecordCommentService` | Creating and deleting comments linked to records |
| `RecordSaveService` | Record updates/creation, file uploads, and attachment versioning |
| `RecordApprovalService` | Workflow actions: approve, submit, return for revision, branch forward, mark review done — plus authorization guards and DB/mail notifications |

**DynamicRecordForm.php changes:**
- Removed heavy dependency imports
- Delegated logic inside `mount()`, `persistRecord()`, `submitForApproval()`, `approve()`, `forwardToBranch()`, `returnForRevision()`, `markReviewDone()`, `addComment()`, `deleteComment()` to injected Services via Laravel's `app(...)` container resolver
- Removed helper authorization methods (`canEditRecord()`, `canAct()`, `canReview()`) from the component — routed through `RecordApprovalService`

---

## Commit 2 — `d830d069` | May 28, 2026

### Feature: Restrict Dashboard Stats by Account

**Files changed:**
- `app/Livewire/Builder/Dashboard.php` — 53 lines added, 7 lines removed
- `Refactor DynamicRecordForm` — new documentation file (38 lines)

**What changed:**

A new private method `getScopedRecordQuery()` was added to `Dashboard.php`. This method applies row-level visibility rules to all dashboard queries based on the authenticated user's role and module settings.

**Scoping logic:**

| Role | Visibility |
|---|---|
| `super admin` | All records (no filter) |
| `Reviewer` | All records (no filter) |
| `TRC Secretariat` | All records (no filter) |
| All other users | Records from unrestricted modules (`my_records_only = false`) **+** records they personally created in restricted modules (`my_records_only = true`) |

**Queries now scoped:**

1. `totalRecords` — total count excluding Draft and Archived
2. `statusCounts` — per-status breakdown
3. 30-day trend chart — records created over last 30 days
4. Per-module stats table — total, pending, and other aggregate counts
5. TRC Schedule list — records with `date_scheduled` set
6. Recent activity — last 10 history entries, now filtered to scoped record IDs via `whereIn('record_id', $scopedRecordIds)`

---

## Commit 3 — `6cabead` | Jun 1, 2026

### Docs: README Overhaul

**Files changed:**
- `README.md` — 330 lines added

**What changed:**

The default Laravel boilerplate `README.md` was replaced with full PRMS project documentation covering the system's purpose, structure, and usage.

---

## Summary of All Files Changed

| File | Change |
|---|---|
| `app/Livewire/Builder/DynamicRecordForm.php` | Refactored — business logic delegated to services |
| `app/Livewire/Builder/Dashboard.php` | Updated — account-scoped stat queries |
| `app/Services/RecordApprovalService.php` | New — approval workflow logic |
| `app/Services/RecordCommentService.php` | New — comment creation/deletion |
| `app/Services/RecordSaveService.php` | New — record save and file upload logic |
| `app/Services/TokenMintingService.php` | New — editor token management |
| `public/storage/.gitignore` | New — gitignore for storage symlink |
| `README.md` | Updated — replaced with PRMS documentation |
| `Refactor DynamicRecordForm` | New — refactoring walkthrough notes |
