# Print / PDF pipeline & extending the editor

## How edit ↔ preview ↔ print ↔ PDF is wired

The editing surface is the existing TipTap "Print Layout" flow (continuous, with the on-screen
page simulation). True pagination, native print, and PDF all come from **one** lazy-loaded
Paged.js pipeline, so **preview === print === PDF** by construction.

```
toolbar "Print"  → TextEditorInstance.openPrintLayout()
toolbar "PDF"    → TextEditorInstance.exportToPdf()
                        │  (dynamic import — code-split)
                        ▼
        resources/js/text-editor/print/paged-runner.js
          ├─ gatherState(instance)        reads te:page / te:sections / te:styles / te:footnotes
          ├─ buildPageCss(...)  ◀── print/stylesheet.js  (pure)
          │     @page + named @page per section, running headers/footers,
          │     page-number counters, named-style classes, widow/orphan,
          │     columns, hyphenation, repeating table headers, footnotes
          ├─ serializeForPrint(editor.getHTML(), ...) ◀── print/serialize.js (pure/DOM)
          │     inline footnotes (float:footnote), group sections, build TOC
          └─ Paged.js Previewer.preview(body, [css], target)
                ├─ openPrintPreview() → paginated overlay + "Print / Save as PDF"
                └─ printArtifacts(css, body) → hidden iframe → window.print()
```

Because the print body is just `editor.getHTML()` (which already carries named-style classes
and paragraph-format inline styles) plus the generated `@page`/style CSS, the printed output
matches the on-screen styling. Text stays selectable, links stay live.

### Lazy loading / bundle
`paged-runner.js` (Paged.js, ~102 KB gzip), `features/find-replace.js`, and the DOCX export
(`docx` + `file-saver`) are all loaded via dynamic `import()` and never enter the main bundle.
`vite.config.js` additionally splits `tiptap`, `yjs`, and `hocuspocus` into cacheable vendor
chunks.

### Known limitations
- PDF heading **outline/bookmarks** depend on the browser's print engine (best-effort).
- Paged.js **footnote** support is partial (separator/multi-column fidelity); endnotes are the
  reliable fallback.
- "Word-matching" means layout-equivalent, not pixel-identical (different line-breaking engine).

## How to add a new paragraph style

1. Add it to `DEFAULT_STYLES` in `resources/js/text-editor/features/styles.js`, e.g.:
   ```js
   'Legal Heading': { type: 'paragraph', props: { fontSize: 13, bold: true, spaceBefore: 8 } },
   ```
   `props` keys are mapped to CSS by `propsToCss` (fontFamily, fontSize(pt), bold, italic,
   underline, color, background, lineSpacing, spaceBefore/After, indentLeft/Right,
   firstLineIndent, align).
2. That's it — the style appears in the toolbar **Styles** dropdown, renders on screen
   (`injectStylesSheet`) and in print (`buildPageCss` reuses `stylesToCss`). User-created
   styles (saved to `te:styles` via `saveStyle`) merge over the defaults automatically.

## How to add a new page-setup option

1. Add the field to `DEFAULT_PAGE_SETUP` in `collab/yroots.js` (so it has a default).
2. Add a control to the page-setup dropdown markup in `text-editor.js` (`buildShell`) and grab
   a ref in the element-refs block.
3. Wire its change handler in `setupToolbar` to update the instance + call
   `persistPageSetup()` (writes `te:page`) and the relevant `apply*()` for on-screen effect.
4. Consume it in `print/stylesheet.js` `buildPageCss` (and `pageRule` for `@page`) so it also
   affects print/PDF. Add a case to `print/stylesheet.test.js`.

## Tests
JS: `npx vitest run` (pagination math, section-aware headers/footers, repeating table headers,
footnote numbering, comment-range stability, Y-schema-unchanged, back-compat, track-changes
accept/reject). PHP: `php84 artisan test --filter=TextEditorCommentThread`.
