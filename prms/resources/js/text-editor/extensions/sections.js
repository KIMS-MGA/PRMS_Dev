// Section breaks and column breaks — additive block nodes that mirror the
// existing PageBreak node. A sectionBreak carries a `sectionId`; the matching
// per-section configuration (size/orientation/margins/columns/headers/footers)
// lives in the te:sections Y.Map so the prose fragment stays lean.

import { Node, mergeAttributes } from '@tiptap/core'

export const SectionBreak = Node.create({
  name: 'sectionBreak',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: false,
  addAttributes() {
    return {
      sectionId: {
        default: null,
        parseHTML: (el) => el.getAttribute('data-section-id'),
        renderHTML: (attrs) => (attrs.sectionId ? { 'data-section-id': attrs.sectionId } : {}),
      },
      breakType: {
        default: 'nextPage', // nextPage | continuous | evenPage | oddPage
        parseHTML: (el) => el.getAttribute('data-break-type') || 'nextPage',
        renderHTML: (attrs) => ({ 'data-break-type': attrs.breakType || 'nextPage' }),
      },
    }
  },
  parseHTML() {
    return [{ tag: 'div[data-section-break]' }]
  },
  renderHTML({ HTMLAttributes }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, { 'data-section-break': 'true', class: 'te-section-break' }),
    ]
  },
  addCommands() {
    return {
      setSectionBreak:
        (attrs = {}) =>
        ({ chain }) =>
          chain().insertContent({ type: 'sectionBreak', attrs }).run(),
    }
  },
})

export const ColumnBreak = Node.create({
  name: 'columnBreak',
  group: 'block',
  atom: true,
  selectable: true,
  parseHTML() {
    return [{ tag: 'div[data-column-break]' }]
  },
  renderHTML() {
    return ['div', { 'data-column-break': 'true', class: 'te-column-break' }]
  },
  addCommands() {
    return {
      setColumnBreak:
        () =>
        ({ chain }) =>
          chain().insertContent({ type: 'columnBreak' }).run(),
    }
  },
})
