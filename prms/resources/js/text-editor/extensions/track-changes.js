// Track changes / suggestion mode — additive marks + a per-user plugin.
//
// When suggestion mode is ON for the local user:
//  - typed text is intercepted (handleTextInput) and inserted carrying a
//    `suggestionInsert` mark instead of plain text;
//  - Backspace/Delete don't remove text — they apply a `suggestionDelete` mark.
// Remote collaborators' edits arrive via the y-prosemirror sync plugin, which
// goes through neither path, so they are never mis-attributed.
//
// Accept/reject are ordinary editor transactions (so they sync like any edit):
//  - accept: drop insert marks (keep text), delete deleted ranges;
//  - reject: delete inserted ranges, drop delete marks (restore text).

import { Mark, Extension, mergeAttributes } from '@tiptap/core'
import { Plugin } from '@tiptap/pm/state'

const cid = () => 'tc-' + Math.random().toString(36).slice(2, 10)

const userAttrs = () => ({
  user: {
    default: null,
    parseHTML: (el) => el.getAttribute('data-tc-user'),
    renderHTML: (a) => (a.user ? { 'data-tc-user': a.user } : {}),
  },
  changeId: {
    default: null,
    parseHTML: (el) => el.getAttribute('data-tc-id'),
    renderHTML: (a) => (a.changeId ? { 'data-tc-id': a.changeId } : {}),
  },
  at: {
    default: null,
    parseHTML: (el) => el.getAttribute('data-tc-at'),
    renderHTML: (a) => (a.at ? { 'data-tc-at': a.at } : {}),
  },
})

export const SuggestionInsert = Mark.create({
  name: 'suggestionInsert',
  inclusive: true,
  excludes: '',
  addAttributes() {
    return userAttrs()
  },
  parseHTML() {
    return [{ tag: 'ins', priority: 60 }]
  },
  renderHTML({ HTMLAttributes }) {
    return ['ins', mergeAttributes(HTMLAttributes, { class: 'te-tc-insert' }), 0]
  },
})

export const SuggestionDelete = Mark.create({
  name: 'suggestionDelete',
  inclusive: false,
  excludes: '',
  addAttributes() {
    return userAttrs()
  },
  parseHTML() {
    // Only claim <del> that we rendered (carries data-tc-id / our class); a plain
    // <del> from existing content stays a StarterKit Strike mark, unchanged.
    return [
      { tag: 'del[data-tc-id]', priority: 60 },
      { tag: 'del.te-tc-delete', priority: 60 },
    ]
  },
  renderHTML({ HTMLAttributes }) {
    return ['del', mergeAttributes(HTMLAttributes, { class: 'te-tc-delete' }), 0]
  },
})

/** Collect [from,to] ranges of text nodes carrying `markName`. */
export function collectMarkRanges(doc, markName) {
  const ranges = []
  doc.descendants((node, pos) => {
    if (node.isText && node.marks.some((m) => m.type.name === markName)) {
      ranges.push({ from: pos, to: pos + node.nodeSize })
    }
  })
  return ranges
}

/** Build a transaction that accepts/rejects all suggestions. Pure given state. */
export function resolveSuggestions(state, mode) {
  const { doc, schema } = state
  const insMark = schema.marks.suggestionInsert
  const delMark = schema.marks.suggestionDelete
  if (!insMark || !delMark) return null
  const insRanges = collectMarkRanges(doc, 'suggestionInsert')
  const delRanges = collectMarkRanges(doc, 'suggestionDelete')
  let tr = state.tr

  const unmark = (ranges, mark) => ranges.forEach((r) => { tr = tr.removeMark(r.from, r.to, mark) })
  const remove = (ranges) =>
    ranges
      .slice()
      .sort((a, b) => b.from - a.from) // delete high→low so earlier positions stay valid
      .forEach((r) => { tr = tr.delete(tr.mapping.map(r.from), tr.mapping.map(r.to)) })

  if (mode === 'accept') {
    unmark(insRanges, insMark) // keep inserted text
    remove(delRanges) // drop deleted text
  } else {
    unmark(delRanges, delMark) // restore text marked for deletion
    remove(insRanges) // drop inserted text
  }
  return tr
}

export const TrackChanges = Extension.create({
  name: 'trackChanges',
  addOptions() {
    return {
      isActive: () => false,
      getUser: () => ({ name: '', color: '' }),
    }
  },
  addProseMirrorPlugins() {
    const opts = this.options
    return [
      new Plugin({
        props: {
          handleTextInput(view, from, to, text) {
            if (!opts.isActive()) return false
            const { schema } = view.state
            const user = opts.getUser() || {}
            const mark = schema.marks.suggestionInsert.create({
              user: user.name || null,
              changeId: cid(),
              at: Date.now(),
            })
            const tr = view.state.tr.insertText(text, from, to)
            tr.addMark(from, from + text.length, mark)
            view.dispatch(tr.scrollIntoView())
            return true
          },
        },
      }),
    ]
  },
  addKeyboardShortcuts() {
    const opts = this.options
    const editor = this.editor
    const markDelete = (dir) => {
      if (!opts.isActive()) return false
      const { state } = editor
      const { selection, schema, doc } = state
      const user = opts.getUser() || {}
      const mark = schema.marks.suggestionDelete.create({
        user: user.name || null,
        changeId: cid(),
        at: Date.now(),
      })
      let from
      let to
      if (!selection.empty) {
        from = selection.from
        to = selection.to
      } else if (dir === 'back') {
        to = selection.from
        from = Math.max(0, selection.from - 1)
      } else {
        from = selection.from
        to = Math.min(doc.content.size, selection.from + 1)
      }
      if (from === to) return true // nothing to mark, but swallow the key
      let tr = state.tr.addMark(from, to, mark)
      tr = tr.setSelection(state.selection.constructor.near(tr.doc.resolve(dir === 'back' ? from : to)))
      editor.view.dispatch(tr)
      return true
    }
    return {
      Backspace: () => markDelete('back'),
      Delete: () => markDelete('fwd'),
    }
  },
  addCommands() {
    return {
      acceptAllSuggestions:
        () =>
        ({ state, dispatch }) => {
          const tr = resolveSuggestions(state, 'accept')
          if (tr && dispatch) dispatch(tr)
          return !!tr
        },
      rejectAllSuggestions:
        () =>
        ({ state, dispatch }) => {
          const tr = resolveSuggestions(state, 'reject')
          if (tr && dispatch) dispatch(tr)
          return !!tr
        },
    }
  },
})
