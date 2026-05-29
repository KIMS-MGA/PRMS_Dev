# Collaborative Editor — Y Schema & Editor Schema (final)

This is the one-page reference proving the Word-grade enhancement is **additive**: the
existing collaborative document (the `"default"` `Y.XmlFragment`) is never renamed or
restructured, and older clients keep working. New items are marked **NEW**.

## Yjs root keys (on the same `Y.Doc`)

| Root key | Type | Status | Holds |
|---|---|---|---|
| `default` | `Y.XmlFragment` | existing | The ProseMirror document (all prose, marks, nodes). **Untouched.** |
| `te:page` | `Y.Map` | **NEW** | Default page setup: size, orientation, margins, columns, numbering, header/footer config |
| `te:sections` | `Y.Map` | **NEW** | Per-section overrides keyed by `sectionId` |
| `te:styles` | `Y.Map` | **NEW** | User-defined named paragraph/character styles |
| `te:print` | `Y.Map` | **NEW** | Print settings (hyphenation, widow/orphan, TOC) — reserved |
| `te:footnotes` | `Y.Map` | **NEW** | Footnote/endnote content keyed by `footnoteId` |
| `te:commentsIndex` | `Y.Map` | **NEW** | Comment-thread index — reserved (bodies live in Laravel) |

Accessors + the `txn(ydoc, origin, fn)` transaction wrapper live in
`resources/js/text-editor/collab/yroots.js`. Every side-map write is wrapped in a
`ydoc.transact(fn, origin)` with an origin tag from `TE_ORIGIN`. Awareness is used only for
cursors/selection/presence — never persisted data.

**Back-compat is enforced by tests** (`yroots.test.js`): writing any side map leaves the
`default` fragment byte-identical, and a pre-change snapshot decodes unchanged under the new
schema.

## Editor schema additions (all additive)

Registered via `wordGradeExtensions()` in `resources/js/text-editor/extensions/index.js`,
spread into the editor's `extensions` array **before** the Collaboration plugin.

### New marks
| Mark | Status | Notes |
|---|---|---|
| `link` | **NEW** | `@tiptap/extension-link` |
| `suggestionInsert` / `suggestionDelete` | **NEW** | Track-changes; render `<ins>`/`<del>` with `data-tc-*` |
| (char style) | **NEW** | `charStyleId` global attr on existing `textStyle` |

### New nodes
| Node | Status | Notes |
|---|---|---|
| `bookmark` | **NEW** | Inline anchor |
| `crossReference` | **NEW** | Inline link to a bookmark |
| `sectionBreak` | **NEW** | Block atom carrying `sectionId` |
| `columnBreak` | **NEW** | Block atom |
| `footnoteRef` | **NEW** | Inline atom carrying `footnoteId` + `kind` |

Headings now allow **levels 1–6** (the StarterKit default; H4–H6 toolbar buttons added).

### New global attributes (added to existing node types, default-safe)
- **paragraph/heading:** `spaceBefore`, `spaceAfter`, `firstLineIndent`, `indentLeft`,
  `indentRight`, `keepWithNext`, `keepLines`, `pageBreakBefore`, `widowControl`,
  `paragraphStyleId`
- **bulletList/orderedList:** `listStyleType`
- **tableCell/tableHeader:** `cellBackground`, `cellBorderColor`, `cellVerticalAlign`, `cellAlign`
- **table:** `repeatHeader`
- **image:** `wrapMode`

Visual attributes render as inline CSS; print-only flow flags render as `data-*` (inert on
screen, consumed by the print pipeline). All have safe defaults so existing content parses
unchanged — proven by `extensions/index.test.js`.

## Server (Laravel)
Only one additive change: `jea_text_editor_comments.parent_id` (nullable self-FK) for threaded
replies — migration `2026_05_30_000001_add_parent_id_to_text_editor_comments.php`, endpoint
`POST /api/text-editor/{record}/{fieldSlug}/comments/{commentId}/reply`. The Hocuspocus server
(`hocuspocus/server.js`) is unchanged — it round-trips the whole Yjs blob regardless of new roots.
