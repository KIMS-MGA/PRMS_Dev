// Footnote/endnote reference — an additive inline atom node in the prose
// fragment. The reference carries a footnoteId + kind; the actual note CONTENT
// lives in the te:footnotes Y.Map (keyed by id). On-screen numbering is done
// purely with CSS counters (see features/footnotes.js injectFootnoteCss), so the
// numbers always reflect document order without extra bookkeeping.

import { Node, mergeAttributes } from '@tiptap/core'

export const FootnoteRef = Node.create({
  name: 'footnoteRef',
  group: 'inline',
  inline: true,
  atom: true,
  selectable: true,
  addAttributes() {
    return {
      footnoteId: {
        default: null,
        parseHTML: (el) => el.getAttribute('data-footnote-id'),
        renderHTML: (attrs) =>
          attrs.footnoteId ? { 'data-footnote-id': attrs.footnoteId } : {},
      },
      kind: {
        default: 'footnote', // footnote | endnote
        parseHTML: (el) => el.getAttribute('data-footnote-kind') || 'footnote',
        renderHTML: (attrs) => ({ 'data-footnote-kind': attrs.kind || 'footnote' }),
      },
    }
  },
  parseHTML() {
    return [{ tag: 'sup[data-footnote-id]' }]
  },
  renderHTML({ HTMLAttributes }) {
    return ['sup', mergeAttributes(HTMLAttributes, { class: 'te-footnote-ref' })]
  },
  addCommands() {
    return {
      insertFootnoteRef:
        (attrs = {}) =>
        ({ chain }) =>
          chain().insertContent({ type: 'footnoteRef', attrs }).run(),
    }
  },
})

/**
 * Walk a ProseMirror doc and number footnote references in document order.
 * Footnotes and endnotes are numbered in independent sequences. Pure/testable.
 * @returns {Array<{id:string, kind:string, n:number}>}
 */
export function numberFootnotes(doc) {
  const found = []
  doc?.descendants?.((node) => {
    if (node.type?.name === 'footnoteRef') {
      found.push({ id: node.attrs.footnoteId, kind: node.attrs.kind || 'footnote' })
    }
  })
  const counters = {}
  return found.map((f) => {
    counters[f.kind] = (counters[f.kind] || 0) + 1
    return { ...f, n: counters[f.kind] }
  })
}
