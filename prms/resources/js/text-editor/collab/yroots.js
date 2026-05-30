// Namespaced Yjs root accessors for the Word-grade editor enhancement.
//
// INTEGRITY RULE (see plan §0/§2): the collaborative *document* lives in the
// Y.XmlFragment under the implicit key "default" (TipTap's Collaboration default).
// That fragment is NEVER touched here. Every new document-level concern (page
// setup, sections, named styles, print settings, footnote content, comment-thread
// index) lives under its own namespaced root key on the SAME Y.Doc. Adding new
// root types to an existing doc is additive and back-compatible — older clients
// simply never read them, and the persisted binary blob round-trips unchanged
// through Hocuspocus.
//
// All writes go through txn() so they carry a clear origin tag (keeps awareness
// and the side-map UndoManager well-behaved, and never tangles with the
// editor's own content transactions).

import * as Y from 'yjs'

// Root keys. Prefixed `te:` so they can never collide with the prose fragment
// ("default") or any future TipTap field name.
export const TE_ROOTS = Object.freeze({
  PAGE: 'te:page',
  SECTIONS: 'te:sections',
  STYLES: 'te:styles',
  PRINT: 'te:print',
  FOOTNOTES: 'te:footnotes',
  COMMENTS_INDEX: 'te:commentsIndex',
})

// Transaction origin tags — one per concern, so undo/awareness can be scoped.
export const TE_ORIGIN = Object.freeze({
  PAGE: 'te:page',
  SECTIONS: 'te:sections',
  STYLES: 'te:styles',
  PRINT: 'te:print',
  FOOTNOTES: 'te:footnotes',
  COMMENTS: 'te:commentsIndex',
})

// The prose fragment key we must never restructure. Exposed read-only so other
// modules can reference it without hard-coding the literal.
export const PROSE_FRAGMENT_KEY = 'default'

/** Default page setup written lazily the first time page UI is opened. */
export const DEFAULT_PAGE_SETUP = Object.freeze({
  size: 'a4',            // a4 | letter | legal | custom
  width: null,           // px, only when size === 'custom'
  height: null,          // px, only when size === 'custom'
  orientation: 'portrait',
  margins: { top: 50, right: 50, bottom: 50, left: 50 }, // px
  gutter: 0,
  numbering: { start: 1, format: 'decimal', restart: false },
  header: { firstDifferent: false, oddEvenDifferent: false },
  footer: { firstDifferent: false, oddEvenDifferent: false },
})

export const getPageMap = (ydoc) => ydoc.getMap(TE_ROOTS.PAGE)
export const getSectionsMap = (ydoc) => ydoc.getMap(TE_ROOTS.SECTIONS)
export const getStylesMap = (ydoc) => ydoc.getMap(TE_ROOTS.STYLES)
export const getPrintMap = (ydoc) => ydoc.getMap(TE_ROOTS.PRINT)
export const getFootnotesMap = (ydoc) => ydoc.getMap(TE_ROOTS.FOOTNOTES)
export const getCommentsIndexMap = (ydoc) => ydoc.getMap(TE_ROOTS.COMMENTS_INDEX)

/** The prose fragment — read-only convenience; callers must NOT restructure it. */
export const getProseFragment = (ydoc) => ydoc.getXmlFragment(PROSE_FRAGMENT_KEY)

/**
 * Run a mutation against the side maps inside a single Yjs transaction tagged
 * with `origin`. Mirrors the project's existing pattern of wrapping content
 * writes in ydoc.transact(...).
 *
 * @param {Y.Doc} ydoc
 * @param {string} origin  one of TE_ORIGIN.*
 * @param {(ydoc: Y.Doc) => void} fn
 */
export function txn(ydoc, origin, fn) {
  return ydoc.transact(() => fn(ydoc), origin)
}

/**
 * Read the effective page setup map as a plain object, falling back to
 * DEFAULT_PAGE_SETUP for keys that have never been written.
 */
export function readPageSetup(ydoc) {
  const map = getPageMap(ydoc)
  const out = { ...DEFAULT_PAGE_SETUP }
  for (const k of map.keys()) out[k] = map.get(k)
  return out
}

/** True if a doc has any persisted page/section/style customization yet. */
export function hasWordGradeState(ydoc) {
  return (
    getPageMap(ydoc).size > 0 ||
    getSectionsMap(ydoc).size > 0 ||
    getStylesMap(ydoc).size > 0
  )
}
