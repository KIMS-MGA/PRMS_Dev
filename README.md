# Branch: `general-refinement` — Changes vs `main`

**Branch:** `general-refinement`
**Author:** KIMS-MGA
**Total Changes:** 14 files changed, 1,293 insertions(+), 425 deletions(-)

---

## Commits Overview

| # | Hash | Date | Message |
|---|------|------|---------|
| 1 | `95c9042` | May 28, 2026 | Refactoring of the DynamicRecordForm Livewire component into Service classes |
| 2 | `d830d069` | May 28, 2026 | Restrict stats count based on the account |
| 3 | `6cabead` | Jun 1, 2026 | docs: replace default Laravel README with PRMS project documentation |
| 4 | `9a8a69c8` | Jun 1, 2026 | Create README.md |
| 5 | `0cb99419` | Jun 1, 2026 | Encapsulating notification processing logic into dedicated NotificationController |
| 6 | `b3894b04` | Jun 1, 2026 | Merge branch 'general-refinement' (upstream updates) |
| 7 | `1fe0e1c0` | Jun 1, 2026 | refactor: remove unused project configuration files |
| 8 | `58b0ab0e` | Jun 1, 2026 | Update README.md |
| 9 | `b4bc49ad` | Jun 1, 2026 | Prevent the workflow from advancing prematurely when multiple reviewers are assigned. |
| 10 | `3f34a4b0` | Jun 1, 2026 | Merge branch 'general-refinement' (upstream updates) |

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

## Commit 4 — `9a8a69c8` | Jun 1, 2026

### Docs: Create README.md

**Files changed:**
- `README.md` — new (106 lines)

**What changed:**

Initial creation of the project's custom `README.md` containing dynamic refinement notes and setup updates.

---

## Commit 5 — `0cb99419` | Jun 1, 2026

### Refactor: Encapsulate Notification Processing Logic

**Files changed:**
- `app/Http/Controllers/NotificationController.php` — new (50 lines)
- `routes/web.php` — 25 lines removed, 21 lines added, inline closures removed

**What changed:**

Extracted notification processing logic out of `routes/web.php` into a dedicated `NotificationController` controller. This allows the application to utilize route caching to maximize page performance (since route caching does not support inline closures). 

Specifically:
- Created `NotificationController@markRead` to mark a specific notification as read.
- Created `NotificationController@markAllRead` to mark all user notifications as read.
- Created `NotificationController@open` to mark a notification as read and securely redirect the user to the corresponding record and module page.
- Cleared three inline closures in the routes file and mapped routes directly to the controller methods.

---

## Commit 6 — `b3894b04` | Jun 1, 2026

### Merge: Synchronize branch 'general-refinement'

**What changed:**

Merge commit synchronizing local branch with remote repository changes.

---

## Commit 7 — `1fe0e1c0` | Jun 1, 2026

### Refactor: Remove Unused Configuration Files

**Files changed:**
- `Refactor DynamicRecordForm` — deleted (38 lines)

**What changed:**

Removed the redundant `Refactor DynamicRecordForm` plain-text notes file from the repository root to clean up unused documentation.

---

## Commit 8 — `58b0ab0e` | Jun 1, 2026

### Docs: Minor README Update

**Files changed:**
- `README.md` — 1 line added

**What changed:**

Added minor details to the project documentation `README.md` file.

---

## Commit 9 — `b4bc49ad` | Jun 1, 2026

### Feature: Prevent Premature Workflow Advancement for Multiple Reviewers

**Files changed:**
- `app/Livewire/Builder/DynamicRecordForm.php` — 16 lines added, 1 line removed
- `app/Livewire/Builder/DynamicRecordIndex.php` — 11 lines added, 1 line removed
- `app/Livewire/Builder/DynamicRecordShow.php` — 46 lines added, 3 lines removed
- `app/Services/RecordApprovalService.php` — 48 lines added, 4 lines removed
- `phpunit.xml` — 2 lines added

**What changed:**

Implemented logic to prevent the workflow from advancing prematurely when multiple reviewers are assigned. In standard review stages, if there are multiple users designated under the stage's `approver_role_id`, the system now counts these users and ensures that all of them must submit their feedback (e.g. approve or forward to branch) before the workflow proceeds to the next stage.

Specifically:
- **`RecordApprovalService`**: Modified `approve()` and `forwardToBranch()` methods to take an `$autoAdvance` flag, perform the reviewer completeness calculation, and return a boolean indicating whether the record actually advanced.
- **`DynamicRecordShow` / `DynamicRecordForm`**: Updated to handle the service return value and show a successful flash notification (`Review submitted / forwarded. Waiting for other reviewers.`) in the UI when further reviews are still pending, rather than prematurely clearing the queue or advancing stages.
- **`phpunit.xml`**: Configured `VIEW_COMPILED_PATH` (`storage/framework/testing/views`) to prevent Windows file locking issues ("Access is denied") during local test suites.

---

## Commit 10 — `3f34a4b0` | Jun 1, 2026

### Merge: Synchronize branch 'general-refinement'

**What changed:**

Merge commit synchronizing local branch with remote repository changes.

---

## Summary of All Files Changed

| File | Change |
|---|---|
| `README.md` | New — root project documentation file |
| `prms/README.md` | New — custom PRMS project documentation file |
| `prms/app/Http/Controllers/NotificationController.php` | New — encapsulating route notification handlers |
| `prms/app/Livewire/Builder/Dashboard.php` | Updated — account-scoped stat queries |
| `prms/app/Livewire/Builder/DynamicRecordForm.php` | Refactored — business logic delegated to services; handles reviewer completeness feedback |
| `prms/app/Livewire/Builder/DynamicRecordIndex.php` | Updated — custom policies proposals role filtering and secure visibility checks |
| `prms/app/Livewire/Builder/DynamicRecordShow.php` | Updated — added reviewer count verification to prevent premature workflow advancement |
| `prms/app/Services/RecordApprovalService.php` | New — approval workflow logic; implements reviewer completeness checking |
| `prms/app/Services/RecordCommentService.php` | New — comment creation/deletion |
| `prms/app/Services/RecordSaveService.php` | New — record save and file upload logic |
| `prms/app/Services/TokenMintingService.php` | New — editor token management |
| `prms/phpunit.xml` | Updated — added unique testing compiled views path for concurrent local test runs |
| `prms/public/storage/.gitignore` | New — gitignore for storage symlink |
| `prms/routes/web.php` | Updated — removed three inline closures and mapped to NotificationController to maximize route caching performance |
