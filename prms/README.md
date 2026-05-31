# PRMS — Policy Record Management System

A dynamic, low-code record management and workflow approval platform built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire). PRMS lets administrators define custom data modules (forms/tables) and route submitted records through multi-stage approval workflows — without writing code for each new form type.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture Overview](#architecture-overview)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
  - [Docker (Recommended)](#docker-recommended)
  - [Local Development](#local-development)
- [Environment Variables](#environment-variables)
- [Key Modules](#key-modules)
- [Workflow Engine](#workflow-engine)
- [Collaborative Text Editor](#collaborative-text-editor)
- [Authentication & Authorization](#authentication--authorization)
- [Scheduled Tasks](#scheduled-tasks)
- [API & Webhooks](#api--webhooks)

---

## Features

| Feature | Description |
|---|---|
| **Dynamic Module Builder** | Create custom forms and data tables at runtime without writing code |
| **Multi-Stage Approval Workflows** | Linear and branching approval pipelines with per-stage role assignment |
| **Collaborative Text Editor** | Real-time multi-user rich text editing powered by TipTap + Yjs |
| **Approval Queue** | Centralized inbox for reviewers to act on pending records |
| **Audit Log** | Full history of every action taken on every record |
| **Webhook Manager** | Send outgoing HTTP payloads to external systems on record events |
| **API Manager** | Token-based external API access to module records |
| **Notification Center** | In-app and email notifications at each workflow stage transition |
| **Role-Based Access Control** | Fine-grained permissions per module (create, edit, approve, review) |
| **Two-Factor Authentication** | TOTP-based 2FA via Google Authenticator |
| **Google OAuth Login** | Sign in with Google via Laravel Socialite |
| **CSV Export** | Export any module's records to CSV |
| **Login Slide Manager** | Dynamically manage the images shown on the login screen |
| **Auto-Advance Deadlines** | Automatically advance stalled records after a working-day deadline |

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | Laravel 13, PHP 8.3 |
| **Frontend** | Livewire 3, Livewire Volt, Tailwind CSS 4, Alpine.js |
| **Database** | MySQL 8.4 |
| **Real-time Collaboration** | TipTap 2, Yjs, Hocuspocus (Node.js WebSocket server) |
| **Authentication** | Laravel Sanctum, Spatie Laravel Permission, Laravel Socialite, Google2FA |
| **Mail** | Resend (`resend/resend-laravel`) |
| **Document I/O** | `docx`, `mammoth` (Word document generation and parsing) |
| **Build Tool** | Vite 8 |
| **Infrastructure** | Docker, Nginx, Laravel Queue Worker, Laravel Scheduler |

---

## Architecture Overview

```
Browser
  │
  ├── HTTP ──► Nginx (port 8080) ──► Laravel App (PHP-FPM)
  │                                        │
  │                                        ├── MySQL 8.4
  │                                        ├── Queue Worker (background jobs)
  │                                        └── Scheduler (cron every 60s)
  │
  └── WebSocket ──► Hocuspocus (port 1234) ──► MySQL 8.4
                    (collaborative editing)
```

The application uses a **dynamic record architecture**: instead of a separate database table for every form type, a generic `Module` + `Record` model pair stores all data. Each `Module` defines its schema via `ModuleField` records, and each `Record` stores its field values as JSON. This allows administrators to create new "application modules" entirely through the UI.

---

## Project Structure

```
prms/
├── app/
│   ├── Console/Commands/
│   │   ├── AdvanceDeadlineStages.php   # Auto-advance records past stage deadlines
│   │   └── SendDateFieldReminders.php  # Email reminders for date-type fields
│   ├── Events/
│   │   └── RecordSaved.php             # Fired after a record is saved
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/DynamicApiController.php      # REST API for external consumers
│   │   │   ├── Auth/SocialController.php         # Google OAuth callback
│   │   │   ├── DynamicRecordController.php       # CSV export
│   │   │   └── TextEditorController.php          # Collaborative editor endpoints
│   │   └── Middleware/
│   │       ├── EnsureUserIsActive.php            # Block deactivated accounts
│   │       └── RequireTwoFactor.php              # Enforce 2FA completion
│   ├── Livewire/
│   │   ├── Admin/
│   │   │   ├── LoginSlideManager.php   # Manage login screen slides
│   │   │   ├── RoleManagement.php      # Create/assign roles and permissions
│   │   │   └── UserManagement.php      # Activate/deactivate users, assign roles
│   │   └── Builder/
│   │       ├── ApiManager.php          # Manage API tokens
│   │       ├── ApprovalQueue.php       # Reviewer inbox for pending records
│   │       ├── AuditLog.php            # View record change history
│   │       ├── Dashboard.php           # Stats and summary cards
│   │       ├── DynamicRecordForm.php   # Create/edit records for any module
│   │       ├── DynamicRecordIndex.php  # List/search/filter records
│   │       ├── DynamicRecordShow.php   # View a record with approval actions
│   │       ├── ModuleForm.php          # Build or edit a module's fields
│   │       ├── ModuleIndex.php         # List all modules
│   │       ├── NotificationCenter.php  # In-app notification list
│   │       ├── WebhookManager.php      # Configure outgoing webhooks
│   │       ├── WorkflowManager.php     # Define workflow stages per module
│   │       └── WorkflowStageManager.php# Configure individual stage settings
│   ├── Models/
│   │   ├── Module.php                  # Dynamic form definition
│   │   ├── ModuleField.php             # Field schema for a Module
│   │   ├── Record.php                  # A single data entry for a Module
│   │   ├── RecordApproval.php          # Approval/rejection action log
│   │   ├── RecordComment.php           # Inline comments on a record
│   │   ├── RecordHistory.php           # Full audit trail per record
│   │   ├── User.php                    # Extended with roles, 2FA, theme
│   │   ├── Webhook.php / WebhookLog.php
│   │   ├── Workflow.php
│   │   ├── WorkflowAction.php
│   │   ├── WorkflowStage.php           # A single approval stage
│   │   └── WorkflowStageTemplate.php
│   └── Services/
│       ├── RecordApprovalService.php   # Submit, approve, return, branch logic
│       ├── RecordCommentService.php    # Comment CRUD and permissions
│       ├── RecordSaveService.php       # Field validation and record persistence
│       └── TokenMintingService.php     # API token creation
├── database/
│   ├── migrations/                     # 30+ migrations covering all entities
│   └── seeders/
│       ├── SuperAdminSeeder.php        # Seeds the initial super admin user
│       └── PolicyProposalsSeeder.php   # Sample module seeder
├── docs/                               # Architecture and developer guides
├── hocuspocus/                         # Node.js WebSocket server for collaborative editing
│   ├── server.js
│   ├── package.json
│   └── Dockerfile
├── docker/                             # Nginx and PHP runtime config
├── docker-compose.yml                  # Full local environment definition
├── Dockerfile                          # Laravel app container
├── routes/
│   ├── web.php                         # All web routes (auth, builder, dynamic app)
│   ├── api.php                         # API routes (token-protected)
│   └── auth.php                        # Breeze auth routes
└── resources/
    ├── js/
    │   ├── app.js                      # Vite entry point
    │   └── text-editor.js              # TipTap + Yjs collaborative editor init
    └── views/                          # Blade layouts and profile page
```

---

## Getting Started

### Docker (Recommended)

**Prerequisites:** Docker Desktop

```bash
# 1. Clone the repository
git clone https://github.com/KIMS-MGA/PRMS_Dev.git
cd PRMS_Dev/prms

# 2. Copy environment file
cp .env.example .env

# 3. Build and start all services
docker compose up -d --build

# 4. Install dependencies and run migrations (inside the app container)
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run build
```

The application will be available at **http://localhost:8080**.
The Hocuspocus WebSocket server runs on **ws://localhost:1234**.

### Local Development

**Prerequisites:** PHP 8.3, Composer, Node.js 20+, MySQL 8

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure DB_* variables in .env, then:
php artisan migrate --seed
npm install
npm run dev

# In a separate terminal, run the queue worker:
php artisan queue:listen --tries=1 --timeout=0
```

---

## Environment Variables

Key variables to configure in `.env`:

| Variable | Description |
|---|---|
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection |
| `MAIL_MAILER` | Set to `resend` for production email |
| `RESEND_API_KEY` | API key from [resend.com](https://resend.com) |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Google OAuth credentials |
| `SANCTUM_STATEFUL_DOMAINS` | Domains allowed for cookie-based auth |
| `QUEUE_CONNECTION` | Set to `database` for persistent job queues |

---

## Key Modules

### Module Builder (`/builder/modules`)
Administrators create **Modules** — each representing a custom data form. Fields can be typed as text, number, date, file upload, textarea, or rich text (collaborative editor). The field order and visibility in list views are configurable per field.

### Dynamic Records (`/app/{moduleSlug}`)
End users interact with records through auto-generated CRUD interfaces. The same Livewire components (`DynamicRecordIndex`, `DynamicRecordForm`, `DynamicRecordShow`) render differently depending on the module's field schema.

### Dashboard (`/dashboard`)
Displays per-module record count statistics filtered by the authenticated user's accessible modules.

---

## Workflow Engine

Each module can have a multi-stage approval workflow configured at `/builder/workflows/{module}`.

**Record lifecycle:**
```
Draft → Submitted → [Stage 1] → [Stage 2] → ... → Completed
                        ↓ (returned)
                     Returned → (resubmit)
```

**Stage capabilities:**
- Assign an **approver role** per stage
- Configure **branching** (forward to one of several downstream stages)
- Set a **working-day deadline** for auto-advance if no action is taken
- Allow **editing** of the record at a specific stage
- Configure **who gets notified** on stage entry (submitter, role, specific user, or external email)
- Mark a stage as **final approval**

Actions: **Approve**, **Return for Revision**, **Forward to Branch**.

---

## Collaborative Text Editor

Records with rich-text fields open a TipTap editor backed by **Yjs CRDT** for conflict-free real-time collaboration. A dedicated **Hocuspocus** Node.js server manages WebSocket connections and persists document state to MySQL.

Features:
- Live multi-cursor presence
- Inline reviewer comments
- "Mark Review Done" per reviewer — auto-advances the record when all assigned reviewers complete their review
- Export to `.docx`

---

## Authentication & Authorization

| Mechanism | Implementation |
|---|---|
| **Standard login** | Laravel Breeze (email + password) |
| **Google OAuth** | Laravel Socialite → `SocialController` |
| **Two-Factor Auth** | `pragmarx/google2fa` + `bacon/bacon-qr-code` (TOTP) |
| **Roles & Permissions** | `spatie/laravel-permission` |
| **API tokens** | Laravel Sanctum |
| **Account activation** | `EnsureUserIsActive` middleware — inactive users are rejected |

**Built-in roles:** `super admin` (full access). All other roles are created by admins and assigned module-level permissions such as `create-{module}`, `edit-{module}`, `approve-{module}`, and `review-{module}`.

---

## Scheduled Tasks

Two Artisan commands run on the scheduler:

| Command | Schedule | Purpose |
|---|---|---|
| `prms:advance-deadline-stages` | Daily | Auto-advance (or auto-approve) records whose current stage has exceeded its working-day deadline |
| `prms:send-date-field-reminders` | Daily | Send email reminders to relevant users when a date-type field on a record is approaching |

---

## API & Webhooks

### REST API (`/api/...`)
External systems can read and create records via token-authenticated API endpoints managed by `DynamicApiController`. Tokens are issued through the **API Manager** UI (`/builder/api-manager`).

### Webhooks (`/builder/webhooks`)
Configure outgoing HTTP POST webhooks that fire when records are created, updated, or transition through workflow stages. Each webhook call is logged in `webhook_logs` for debugging.

---

## Branch: `general-refinement`

This branch contains a major refactor of the `DynamicRecordForm` Livewire component. Its previously monolithic business logic has been extracted into dedicated service classes:

- `RecordSaveService` — field validation and record persistence
- `RecordApprovalService` — submit, approve, return, branch, and review-done logic
- `RecordCommentService` — inline comment management
- `TokenMintingService` — API token issuance

Additionally, this branch introduces per-module record count statistics restricted to the authenticated account on the Dashboard.
