# Branch Summary: `bugfix/hocuspocus-data-sync-loss`

## Purpose

Fix collaborative text editor data loss and authentication failures affecting the Proponent form and Reviewer show views in the dynamic record workflow.

---

## Bugs Fixed

### Bug 1 — Proponent Save / Submit Wipes Editor Content

**Symptom:** Saving a draft or submitting a proposal caused the text editor content to be cleared in the database.

**Root cause:** `$editorTokens` was declared `protected` in both Livewire components. Livewire only serialises `public` properties into its snapshot. On every network round-trip (save, submit, approve, comment), the snapshot deserialized with an empty `$editorTokens`, causing `render()` to call `EditorTokenService::mint()` again. `mint()` **revokes all existing tokens before creating new ones**, so the token the browser held became invalid mid-session. The Hocuspocus WebSocket disconnected, `isSynced` dropped to `false`, and the commit hook (which had the `isSynced` guard) was blocked from pushing editor content into the Livewire payload — leaving an empty or stale value to be saved.

**Fix:** Changed `protected array $editorTokens` → `public array $editorTokens` in both components. The `render()` guard (`if (empty($this->editorTokens))`) now correctly skips re-minting on every subsequent request.

---

### Bug 2 — Reviewer Edits Not Saved

**Symptom:** Comments and edits made by a Reviewer in the collaborative text editor were not persisted when "Mark Review Done" was clicked.

**Root cause:** `DynamicRecordShow` had no `$data` / `$editorData` property and no hidden `<input wire:model>` in the blade. The editor content existed only in the Hocuspocus CRDT (binary state) but never reached the PHP component — so `markReviewDone()` had nothing to write to `records.data`.

**Fix:**
- Added `public array $editorData = []` to `DynamicRecordShow`, seeded from `$record->data` in `mount()`.
- Added a hidden input (`<input type="text" style="display:none" wire:model="editorData.{slug}">`) in `dynamic-record-show.blade.php` for each text editor field, giving the JS commit hook a target.
- `markReviewDone()` now merges `$editorData` into `$record->data` before recording the review.

---

### Bug 3 — Partial Reviews Not Persisting Editor Content

**Symptom:** When a reviewer marked a field done (but was not the last reviewer), no data was saved, leaving the record in a potentially stale state.

**Root cause:** The `records.data` save inside `markReviewDone()` was placed inside the all-reviewers-done quorum block, so it only ran when the final reviewer acted.

**Fix:** Moved the save unconditionally before the quorum check in both `DynamicRecordForm` and `DynamicRecordShow`.

---

### Bug 4 — Livewire Commit Hook Removed (Regression)

**Symptom:** Editor HTML was not being injected into Livewire requests, making all saves dependent on Alpine's deferred `wire:model` binding — which morphdom could reset between the last `onUpdate` event and the save action.

**Root cause:** Commit `e74edad7` removed the Livewire commit hook entirely, citing it as "buggy." The actual bug was a missing `isSynced` guard (added in `ddc04b07`), not the hook itself.

**Fix:** Reinstated the `Livewire.hook('commit', ...)` in `resources/js/app.js` with:
- `isSynced` guard — prevents a blank, unsynced editor from overwriting saved content
- `isNew` bypass — new records have no server content to protect, so the guard is skipped
- `data-readonly` check — skips readonly editors (reviewers who already marked done)
- Dynamic `getAttribute('wire:model')` — works for both `data.{slug}` (form) and `editorData.{slug}` (show) without hardcoding

---

### Bug 5 — Hocuspocus Authentication Always Failing

**Symptom:** Hocuspocus WebSocket connections failed immediately with `[onAuthenticate] Authentication failed` in the server log. The editor showed "Connecting…" and never synced.

**Root cause:** Three misconfigured defaults in `hocuspocus/server.js`:

| Variable | Wrong default | Correct value |
|---|---|---|
| `APP_URL` | `http://app` (Docker hostname) | `http://localhost:8000` |
| `DB_USER` | `prms` | `root` (Laravel uses `DB_USERNAME`) |
| `TABLE_PREFIX` | `jea_` | `""` (Laravel uses empty prefix) |

The Node.js process does not read Laravel's `.env` automatically, so all three fell back to incorrect Docker-era defaults.

**Fix:**
- Added `dotenv` as a dependency to `hocuspocus/package.json`
- `server.js` now loads `../.env` (the parent Laravel `.env`) at startup via `dotenv.config()`
- DB user resolution: `process.env.DB_USERNAME || process.env.DB_USER || 'root'` (handles both Laravel and Docker conventions)
- Table prefix: uses nullish coalescing (`??`) so an explicit empty string in `.env` is respected

---

## Files Changed

### PHP — Livewire Components

| File | Change |
|---|---|
| `app/Livewire/Builder/DynamicRecordForm.php` | `protected → public` on `$editorTokens`; `markReviewDone()` saves data unconditionally before quorum check |
| `app/Livewire/Builder/DynamicRecordShow.php` | Same token fix; new `public array $editorData`; `mount()` seeds `$editorData`; `markReviewDone()` saves `$editorData` before recording review |

### Blade — Show View

| File | Change |
|---|---|
| `resources/views/livewire/builder/dynamic-record-show.blade.php` | Added `<input type="text" style="display:none" wire:model="editorData.{slug}">` after each text editor mount container |

### JavaScript

| File | Change |
|---|---|
| `resources/js/app.js` | Reinstated `Livewire.hook('commit', ...)` with `isSynced` guard, readonly check, `isNew` bypass, and dynamic `wire:model` resolution |

### Hocuspocus Server

| File | Change |
|---|---|
| `hocuspocus/server.js` | Load parent `.env` via dotenv; fix `APP_URL`, `DB_USERNAME`, `TABLE_PREFIX` defaults |
| `hocuspocus/package.json` | Added `dotenv ^16.0.0` dependency |
| `hocuspocus/package-lock.json` | First-time tracked; generated by `npm install` |

---

## Deployment Checklist

After pulling this branch, the following one-time steps are required:

- [ ] **`npm install`** inside `prms/hocuspocus/` — installs the new `dotenv` dependency
- [ ] **`npm run build`** inside `prms/` — recompiles `app.js` with the reinstated commit hook
- [ ] **Restart the Hocuspocus server** — picks up the new `server.js` with dotenv loading
- [ ] Verify `prms/.env` has `APP_URL=http://localhost:{port}` set to your local dev URL

No database migrations required. No breaking changes to existing data.

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
   → reads editor.getHTML()
   → sets hiddenInput.value
   → injects directly into commit.updates[wire:model]
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
