// Additive nodes/marks + commands for the Word-grade enhancement: bookmarks,
// cross-references, and a clear-formatting command. All are new schema members
// (no existing type touched) and round-trip through getHTML/setContent via their
// parseHTML/renderHTML so the existing Source view keeps working.

import { Node, Extension, mergeAttributes } from '@tiptap/core'
import { cssSafe } from './index.js'

// An invisible named anchor. Cross-references and the TOC link to it.
export const Bookmark = Node.create({
  name: 'bookmark',
  group: 'inline',
  inline: true,
  atom: true,
  selectable: true,
  addAttributes() {
    return {
      name: {
        default: null,
        parseHTML: (el) => el.getAttribute('data-bookmark'),
        renderHTML: (attrs) =>
          attrs.name
            ? { 'data-bookmark': attrs.name, id: `bm-${cssSafe(attrs.name)}` }
            : {},
      },
    }
  },
  parseHTML() {
    // Higher priority than the Link mark's default `a[href]` rule so a stored
    // bookmark/cross-reference anchor is restored as its node, not linked text.
    return [{ tag: 'a[data-bookmark]', priority: 100 }]
  },
  renderHTML({ HTMLAttributes }) {
    return ['a', mergeAttributes(HTMLAttributes, { class: 'te-bookmark' })]
  },
  addCommands() {
    return {
      insertBookmark:
        (name) =>
        ({ commands }) =>
          commands.insertContent({ type: 'bookmark', attrs: { name } }),
    }
  },
})

// A clickable reference to a bookmark; renders its label and links to #bm-<name>.
export const CrossReference = Node.create({
  name: 'crossReference',
  group: 'inline',
  inline: true,
  atom: true,
  selectable: true,
  addAttributes() {
    return {
      target: {
        default: null,
        parseHTML: (el) => el.getAttribute('data-xref'),
        renderHTML: (attrs) => (attrs.target ? { 'data-xref': attrs.target } : {}),
      },
      label: {
        default: '',
        parseHTML: (el) => el.getAttribute('data-xref-label') || el.textContent || '',
        renderHTML: (attrs) => (attrs.label ? { 'data-xref-label': attrs.label } : {}),
      },
      kind: {
        default: 'bookmark',
        parseHTML: (el) => el.getAttribute('data-xref-kind') || 'bookmark',
        renderHTML: (attrs) => ({ 'data-xref-kind': attrs.kind || 'bookmark' }),
      },
    }
  },
  parseHTML() {
    return [{ tag: 'a[data-xref]', priority: 100 }]
  },
  renderHTML({ node, HTMLAttributes }) {
    const href = node.attrs.target ? `#bm-${cssSafe(node.attrs.target)}` : null
    const text = node.attrs.label || node.attrs.target || 'reference'
    return ['a', mergeAttributes(HTMLAttributes, { class: 'te-xref', href }), text]
  },
  addCommands() {
    return {
      insertCrossReference:
        (attrs) =>
        ({ commands }) =>
          commands.insertContent({ type: 'crossReference', attrs }),
    }
  },
})

// Word-style "Clear Formatting": strip inline marks and reset the visual
// paragraph-format attributes back to their defaults.
export const ClearFormatting = Extension.create({
  name: 'clearFormatting',
  addCommands() {
    const reset = {
      spaceBefore: null,
      spaceAfter: null,
      firstLineIndent: null,
      indentLeft: null,
      indentRight: null,
      lineSpacing: null,
      paragraphStyleId: null,
      indent: 0,
    }
    return {
      clearFormatting:
        () =>
        ({ chain, state }) => {
          let c = chain().unsetAllMarks()
          if (state.schema.nodes.paragraph) c = c.updateAttributes('paragraph', reset)
          if (state.schema.nodes.heading) c = c.updateAttributes('heading', reset)
          return c.run()
        },
    }
  },
})
