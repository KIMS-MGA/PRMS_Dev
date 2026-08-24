# PRMS — Full Codebase Analysis

## What Is This System?

PRMS is a **dynamic, low-code records and workflow management platform** built on Laravel 13. Think of it like a custom-built "Google Forms meets Jira approvals" — an administrator can create any type of form (a "Module"), define its fields, configure a multi-stage approval pipeline, and then regular users submit records that travel through that pipeline. No code changes are needed to add a new form type; everything is database-driven.

The name clue: `PolicyProposalsSeeder.php` — this system was likely built for a government or institutional environment managing policy submissions, technical review committees, and approval chains.

---

## Step 1 — The Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | 8.3 |
| Framework | Laravel | 13 |
| Reactive UI | Livewire 3 + Volt | ^3.6 / ^1.7 |
| CSS | Tailwind CSS | 4 |
| Auth | Laravel Sanctum (API) + Breeze (session) | ^4.0 |
| Social Login | Laravel Socialite (Google OAuth) | ^5.25 |
| 2FA | pragmarx/google2fa | ^9.0 |
| Roles/Permissions | Spatie Laravel Permission | ^7.2 |
| Email | Resend | ^1.3 |
| Rich Text Editor | Tiptap (ProseMirror) | ^2.x |
| Collaborative Editing | Hocuspocus + Yjs | ^2.13 / ^13.6 |
| Build Tool | Vite | ^8.0 |
| Database (prod) | MySQL | 8.4 |
| Database (dev/test) | SQLite | — |
| Testing | Pest PHP | ^4.4 |
| Containerization | Docker Compose | 6 containers |

---

## Step 2 — Folder Map & Learning Order

```
prms/
├── app/
│   ├── Console/Commands/       ← Scheduled artisan tasks (2 files)
│   ├── Events/                 ← RecordSaved event
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           ← SocialController (Google login)
│   │   │   ├── Api/            ← DynamicApiController (REST API)
│   │   │   ├── DynamicRecordController.php  ← CSV export
│   │   │   └── TextEditorController.php     ← Collaborative editor API
│   │   └── Middleware/         ← EnsureUserIsActive, RequireTwoFactor
│   ├── Listeners/              ← ProcessWorkflows (automation engine)
│   ├── Livewire/
│   │   ├── Admin/              ← UserManagement, RoleManagement, LoginSlideManager
│   │   └── Builder/            ← THE CORE: all dynamic form/record/workflow UI
│   ├── Mail/                   ← StageNotificationMail
│   ├── Models/                 ← All Eloquent models (10 files)
│   ├── Notifications/          ← DynamicNotification (DB + email)
│   └── Providers/              ← AppServiceProvider (Gate + Event wiring)
├── database/
│   ├── migrations/             ← 50+ migrations, well-dated
│   └── seeders/                ← SuperAdmin + PolicyProposals example data
├── hocuspocus/                 ← Standalone Node.js collaborative WebSocket server
├── resources/
│   ├── js/                     ← Vite entry + Tiptap editor JS
│   └── views/
│       ├── livewire/builder/   ← Blade templates for all Livewire components
│       ├── layouts/            ← app.blade.php, guest.blade.php
│       └── components/         ← Reusable Blade components
├── routes/
│   ├── web.php                 ← All web routes
│   ├── api.php                 ← REST API + text editor endpoints
│   └── auth.php                ← Breeze-generated auth routes
└── docker-compose.yml          ← 6-container orchestration
```

**Suggested learning order for a new developer:**
1. `routes/web.php` — see all the URLs
2. `app/Models/Module.php` + `ModuleField.php` — the schema for "what forms look like"
3. `app/Models/Record.php` — the data store for all submitted records
4. `app/Models/WorkflowStage.php` — the approval pipeline structure
5. `app/Livewire/Builder/ModuleForm.php` — how admins build modules
6. `app/Livewire/Builder/DynamicRecordForm.php` — the most complex file; handles create/edit/approve/return
7. `app/Listeners/ProcessWorkflows.php` — the automation engine
8. `hocuspocus/server.js` — the real-time collaboration server

---

## Step 3 — Request Lifecycle

### Typical web request (a user submits a record):

```
Browser POST (Livewire AJAX)
  └─▶ Nginx (port 8080)
        └─▶ PHP-FPM (app container)
              └─▶ Laravel Router (routes/web.php)
                    └─▶ Middleware stack:
                          ├─ EnsureUserIsActive   ← bans deactivated accounts on every request
                          ├─ RequireTwoFactor      ← enforces TOTP completion
                          ├─ auth (Sanctum/session)
                          └─ verified (email verification)
                    └─▶ Livewire component: DynamicRecordForm
                          └─▶ mount() — loads Module + fields from DB
                          └─▶ save() / persistRecord()
                                ├─ Validates fields (dynamic rules built from ModuleField records)
                                ├─ Handles file uploads → Storage::disk('public')
                                └─▶ Record::updateOrCreate()
                                      └─▶ RecordHistory::create()  ← audit log
                                      └─▶ RecordSaved::dispatch()
                                            └─▶ ProcessWorkflows listener
                                                  ├─ Fires webhooks (outbound HTTP)
                                                  └─ Evaluates workflow rules → notify/assign/set_field/email
```

---

## Step 4 — Core Architecture: The Dynamic Module System

This is the most important thing to understand. **There are no "form classes" in PHP for each business form type.** Instead:

### How a Module becomes a working app:

1. Admin creates a `Module` record (name: "Policy Proposals", slug: `policy_proposals`)
2. Admin adds `ModuleField` records (name: "Title", type: `text`; name: "Document", type: `attachment`; etc.)
3. System auto-generates 7 Spatie permissions: `view-policy_proposals`, `create-policy_proposals`, `edit-policy_proposals`, `delete-policy_proposals`, `change-status-policy_proposals`, `review-policy_proposals`, `approve-policy_proposals`
4. Admin assigns those permissions to roles
5. Users now visit `/app/policy_proposals` and get a fully working list/form/show UI

**The `data` column is the key insight.** Every record stores its field values as JSON in `records.data`:
```json
{
  "title": "Amendment to Procurement Policy",
  "department": "Finance",
  "document": "attachments/abc123.pdf",
  "effective_date": "2026-06-01"
}
```

This means the system can handle any form shape without code changes — but it also means there's no database-level type enforcement on individual fields.

---

## Step 5 — Database Schema

### Core Tables

| Table | Purpose |
|---|---|
| `users` | Authentication + profile (supports Google ID, 2FA, theme, active flag) |
| `modules` | Module definitions (name, slug, buttons, default status, source_module_id for mirroring) |
| `module_fields` | Field definitions per module (name, type, required, options, col_span, versioning, visibility_conditions) |
| `records` | All submitted data for all modules (module_id + data JSON + status + workflow state) |
| `workflow_stages` | Approval pipeline stages per module (order, approver role, branching, auto-advance, stage-specific fields) |
| `record_approvals` | Log of every approval action (approve/return/forward/auto_advanced) |
| `record_histories` | Detailed audit log of all record changes |
| `record_comments` | Discussion thread on each record |
| `workflows` | Automation rules (trigger + conditions + actions) |
| `workflow_actions` | Individual actions for a workflow (notify/assign/set_field/send_email) |
| `workflow_stage_templates` | Saved workflow stage blueprints (reusable across modules) |
| `webhooks` / `webhook_logs` | Outbound HTTP hooks on record events |
| `text_editor_documents` | Binary CRDT state for collaborative rich text fields (stored base64) |
| `text_editor_histories` | Insert/delete log for rich text fields |
| `text_editor_comments` | Inline annotations/comments on text editor fields |
| `text_editor_reviews` | Tracks who has marked "Review Done" on a rich text field |
| `login_slides` | Configurable slides displayed on the login page |
| `personal_access_tokens` | Sanctum API tokens (including per-field editor tokens) |
| `notifications` | Laravel database notification queue |
| `roles`, `permissions`, `model_has_roles`, etc. | Spatie permission tables |

### Key Relationships

```
Module ──< ModuleField
Module ──< Record
Module ──< WorkflowStage ──< RecordApproval
Record ──< RecordHistory
Record ──< RecordComment
Record ──< RecordApproval
Record ──< text_editor_documents (via recordId in doc name)
Module ──< Workflow ──< WorkflowAction
```

**Source module mirroring:** A Module can have `source_module_id` set. This creates a "mirrored view" that shows records from the source module (excluding Drafts) but with its own extra fields. This is a clever but potentially confusing pattern — the mirrored module shares records with the source but adds a reviewer perspective with additional fields.

---

## Step 6 — Authentication & Authorization

### Login flows
1. **Email/password** — standard Laravel Breeze with session
2. **Google OAuth** — via Socialite; auto-creates user if email doesn't exist; auto-deactivation check
3. **TOTP 2FA** — opt-in via `two_factor_secret`; enforced by `RequireTwoFactor` middleware using a session flag

### Authorization layers

| Layer | Mechanism |
|---|---|
| Super Admin bypass | `Gate::before()` in AppServiceProvider — super admin can do everything |
| Module-level permissions | Spatie permissions auto-generated per module (e.g., `view-slug`, `approve-slug`) |
| Workflow stage access | Role-based: `approver_role_id` on `WorkflowStage` checked at approval time |
| API access | Sanctum tokens with abilities (e.g., `module_slug:read`, `editor:write`) |
| Account active check | `EnsureUserIsActive` middleware — logs out deactivated users on every request |

**Key security pattern:** The `DynamicApiController` double-checks both the token ability AND the live Spatie permission, so revoking a role takes immediate effect even for valid tokens.

---

## Step 7 — Workflow Engine

The workflow system has two distinct layers:

### Layer 1: Stage-based Approval Pipeline
Configured in `WorkflowStageManager`. Each module has ordered `WorkflowStage` records:
- A record moves through stages via approve / return / forward-to-branch actions
- Each stage can have: an approver role, a reviewer role, auto-advance deadline (working days), branch paths (conditional routing), stage-specific extra fields, and notification rules
- "Branch" stages allow routing to different paths (e.g., "Ad Referendum" vs. "TRC Review")

### Layer 2: Event-driven Automation (Workflows)
Configured in `WorkflowManager`. Fires on `RecordSaved` events:
- Evaluates conditions against record data (`field = value`, `field contains X`, `field is empty`, etc.)
- Actions: `notify_user`, `notify_role`, `assign_to`, `set_field`, `send_email`
- Also fires webhooks to external systems

### Scheduled tasks (cron, run every minute in Docker)
- `prms:advance-deadline-stages` — auto-advances (or auto-approves) records that have exceeded their stage deadline in working days
- `prms:send-date-field-reminders` — sends email/notification reminders N days before a date field value

---

## Step 8 — The Collaborative Text Editor

This is the most architecturally complex subsystem. It involves **three separate processes**:

```
Browser (Tiptap + Yjs)
   ↕ WebSocket (port 1234)
Hocuspocus Node.js server
   ├── Validates auth: POST to Laravel API /api/text-editor/validate-token
   ├── Reads/writes CRDT state: MySQL text_editor_documents table directly
   └── Document name format: "record-{id}-field-{slug}"

Browser (Livewire)
   ↕ HTTP (Sanctum tokens)
Laravel API /api/text-editor/...
   ├── getHistory / storeHistory  ← change audit log
   ├── getComments / storeComment / resolveComment ← inline annotations
   ├── getReviewStatus ← who has reviewed
   └── storeImage ← image uploads embedded in editor
```

**Token lifecycle:** On every form `mount()`, the component mints short-lived Sanctum tokens (8hr) per text editor field. These are scoped to `editor:read` / `editor:write` abilities. The Hocuspocus server uses these to authenticate WebSocket connections back to Laravel.

---

## Step 9 — Third-Party Packages

| Package | Purpose | Coupling |
|---|---|---|
| `spatie/laravel-permission` | Role/permission management; dynamically auto-generates permissions per module | **Very deep** — removing would require rewriting all authorization |
| `livewire/livewire` | Reactive server-side components; all UI is Livewire | **Very deep** — entire UI layer |
| `laravel/sanctum` | API token auth; also used for text editor tokens | **Deep** — API and editor both depend on it |
| `laravel/socialite` | Google OAuth | **Moderate** — only SocialController |
| `pragmarx/google2fa` | TOTP 2FA | **Moderate** — login flow + profile settings |
| `resend/resend-laravel` | Transactional email via Resend API | **Light** — swap by changing MAIL_MAILER env |
| `bacon/bacon-qr-code` | QR code generation for 2FA setup | **Light** — one use in 2FA setup view |
| `@tiptap/*` | Rich text editor with collaboration, tables, images | **Moderate** — only for `text_editor` field type |
| `@hocuspocus/provider` | WebSocket CRDT client | **Moderate** — only for `text_editor` field type |
| `yjs` | CRDT state management for collaborative editing | **Moderate** — works with Hocuspocus |
| `docx` + `mammoth` | DOCX import to extract HTML for text editor templates | **Light** — only in ModuleForm template import |
| `sortablejs` | Drag-to-reorder fields in ModuleForm | **Light** |

---

## Step 10 — Code Quality Assessment

### Critical

1. **Fat Livewire components** — `DynamicRecordForm.php` is 622 lines and handles form saving, file uploads, approval routing, branch forwarding, revision returns, comments, reviews, token minting, and notification dispatch. This should be extracted into Service classes (e.g., `RecordApprovalService`, `RecordSaveService`). As-is, it is very hard to unit test.

2. **SQL injection risk (medium-severity)** — In `DynamicRecordIndex::buildFilteredQuery()` and `DynamicApiController::index()`, field slugs are interpolated into raw SQL:
   ```php
   "LOWER(json_extract(data, '$.{$field->slug}')) LIKE ?"
   ```
   The Livewire version whitelists against DB-loaded slugs (`$allowedSlugs = $this->module->fields->pluck('slug')->flip()`), which is correct. The API version (`DynamicApiController`) iterates `$module->fields` directly which is also safe since those come from DB, not user input. But if `$field->slug` were ever user-controllable this would be critical. The pattern is fragile — a future developer may not know the whitelist is load-bearing.

3. **Token accumulation in `DynamicRecordForm::mount()`** — The code tries to revoke previous tokens but uses a loose fallback that could miss tokens. More importantly, `editorTokens` is a `protected array` that isn't in the Livewire snapshot, requiring re-minting in `render()` on every hydration — meaning tokens multiply if the page is hydrated frequently.

### Medium

4. **No service layer** — Business logic (saving records, approval transitions, notifications) lives entirely in Livewire components. This makes automation (e.g., CLI-triggered approvals) impossible without going through the UI component, and makes testing require Livewire's test harness.

5. **`module_fields()->delete()` on every save** — `ModuleForm::save()` deletes all fields and re-creates them on each edit. This means if a field is renamed, old records referencing the old slug in their JSON `data` become orphaned silently. There's no migration path for renaming fields.

6. **Hocuspocus uses MySQL with `DB_PREFIX`** — The Node.js server connects directly to MySQL and has a hardcoded `TABLE_PREFIX` env var (`jea_`). This prefix must match Laravel's `DB_PREFIX` config. If they drift, documents silently never persist.

7. **`SendDateFieldReminders` runs on ALL records regardless of stage** — The command queries records where a date field matches the target date but doesn't filter by `current_stage_id`. A record at any stage (even Completed) will trigger reminders if its data contains a matching date.

### Minor

8. **`DynamicRecordIndex` re-loads module on every `render()`** — Livewire drops eager-loaded relations on re-hydration, so the component reloads the module with fields on every render call. This creates N+2 queries per render (module + source module fields + records query).

9. **No caching on permission lookups** — Every form render calls `auth()->user()->can("create-{$slug}")` etc., which hits Spatie's permission tables. Under load, this could be significant. Spatie supports permission caching but it isn't configured here.

10. **`google_id` not used as unique identifier** — `SocialController::callback()` uses `firstOrCreate` on `email`, not on `google_id`. If a user changes their Google email and re-authenticates, a new account is created.

---

## Step 11 — Infrastructure & DevOps

The Docker Compose stack runs **6 containers**:

| Container | Role |
|---|---|
| `prms_app` | PHP-FPM (Laravel application) |
| `prms_nginx` | Web server, port 8080 on host |
| `prms_mysql` | MySQL 8.4, port 3307 on host |
| `prms_queue` | Laravel queue worker (`queue:work`) |
| `prms_scheduler` | Cron via shell loop (`schedule:run` every 60s) |
| `prms_hocuspocus` | Node.js WebSocket server for collaborative editing, port 1234 |

**Dev vs. prod:** The `composer.json` `dev` script uses `php84 artisan` (non-standard binary name — likely a local alias) and `concurrently` to run the queue worker + Vite together. The Docker setup is the proper production configuration.

**Missing:** There's no Redis configured. The queue driver defaults to whatever is in `.env` — if it's `sync` in production, jobs run inline and failures can't be retried.

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         PRMS System                             │
│                                                                 │
│  Browser                                                        │
│  ├── Livewire (AJAX over HTTP)  ──▶  Nginx ──▶  PHP-FPM        │
│  │        Blade + Tailwind UI             └──▶  Laravel 13      │
│  │                                              ├─ Routes       │
│  │                                              ├─ Livewire     │
│  │                                              ├─ Models       │
│  │                                              └─ MySQL 8.4    │
│  │                                                              │
│  ├── REST API (Sanctum token)   ──▶  Nginx ──▶  PHP-FPM        │
│  │                                              └─ DynamicApiController
│  │                                                              │
│  └── WebSocket (Tiptap/Yjs)     ──▶  Hocuspocus (Node.js)      │
│            real-time collab             ├─ Auth: ──▶ Laravel API│
│                                         └─ Storage: ──▶ MySQL   │
│                                                                  │
│  Scheduler (cron) ──▶  PHP-FPM ──▶  AdvanceDeadlineStages       │
│                                  └──▶  SendDateFieldReminders    │
│                                                                  │
│  Queue Worker ──▶  PHP-FPM ──▶  DynamicNotification (mail+DB)  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Quick Reference: What Happens When...

| Scenario | Key files |
|---|---|
| Admin creates a new form type | `ModuleForm.php` → `Module::updateOrCreate()` + `Permission::firstOrCreate()` for 7 permissions |
| User submits a record | `DynamicRecordForm::persistRecord()` → `RecordSaved::dispatch()` → `ProcessWorkflows::handle()` |
| Approver advances a stage | `DynamicRecordForm::approve()` → `WorkflowStage` lookup → `Record::update()` + `notifyStageUsers()` |
| Record deadline expires | `AdvanceDeadlineStages::handle()` (scheduled every minute) |
| Outbound webhook fires | `ProcessWorkflows::fireWebhooks()` → `Http::post()` → `WebhookLog::create()` |
| User edits a rich text field collaboratively | Tiptap WebSocket → Hocuspocus → MySQL CRDT state; separately Livewire API calls for history/comments |
| Super admin does anything | `Gate::before()` in `AppServiceProvider` returns `true` immediately — skips all permission checks |

---

## Recommended Refactoring Priorities

1. **Extract `RecordApprovalService`** — Pull the approve/return/forward/submitForApproval logic out of `DynamicRecordForm` into a dedicated service. This enables queue-based processing, CLI tools, and proper unit tests.

2. **Protect the field-slug SQL interpolation** — Add an explicit assertion/exception if `$field->slug` doesn't match `^[a-z0-9_]+$` anywhere it gets interpolated into raw SQL. The current whitelist is correct but invisible to future devs.

3. **Add a field rename migration path** — Currently renaming a field in `ModuleForm` silently orphans all existing record data. A migration tool that updates JSON keys in `records.data` would prevent data loss.

4. **Configure Redis and explicit queue connection** — Add Redis to docker-compose and set `QUEUE_CONNECTION=redis` explicitly, so failed jobs are retryable and not running synchronously.

5. **Scope `SendDateFieldReminders` to active records** — Add `.whereIn('status', ['Submitted', 'Under Review'])` to avoid reminders on Completed or archived records.

---

*This is a well-structured, genuinely useful application with a clean event-driven design and thoughtful use of Laravel's ecosystem. The main risks are the fat Livewire component pattern and the silent field-renaming data loss issue. The collaborative editing architecture is notably sophisticated for a Laravel project.*
