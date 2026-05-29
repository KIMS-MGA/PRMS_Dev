// @vitest-environment jsdom
import { describe, it, expect } from 'vitest'
import * as Y from 'yjs'
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Collaboration from '@tiptap/extension-collaboration'
import { wordGradeExtensions } from '../extensions/index.js'
import { getProseFragment, getStylesMap, txn, TE_ORIGIN } from './yroots.js'

function mkEditor(ydoc) {
  return new Editor({
    element: document.createElement('div'),
    extensions: [
      StarterKit.configure({ history: false }),
      ...wordGradeExtensions(),
      Collaboration.configure({ document: ydoc }),
    ],
  })
}

// Text covered by a range mark (link), used as a proxy for any range-anchored
// annotation (comments anchor identically via the CRDT).
function linkedText(editor) {
  let t = ''
  editor.state.doc.descendants((n) => {
    if (n.isText && n.marks.some((m) => m.type.name === 'link')) t += n.text
  })
  return t
}

describe('range/comment stability under concurrent edits', () => {
  it('keeps a range mark anchored to the same text after concurrent inserts elsewhere', () => {
    const da = new Y.Doc()
    const db = new Y.Doc()

    // Buffered, manual sync so we can stage *concurrent* (un-synced) edits.
    const bufA = []
    const bufB = []
    da.on('update', (u, origin) => { if (origin !== 'remote') bufA.push(u) })
    db.on('update', (u, origin) => { if (origin !== 'remote') bufB.push(u) })
    const flush = () => {
      bufA.forEach((u) => Y.applyUpdate(db, u, 'remote'))
      bufB.forEach((u) => Y.applyUpdate(da, u, 'remote'))
      bufA.length = 0
      bufB.length = 0
    }

    const ea = mkEditor(da)
    const eb = mkEditor(db)

    ea.commands.setContent('<p>hello world here</p>')
    flush()

    // Anchor a link mark over "world" (text offset 6..11 -> doc pos 7..12).
    ea.chain().setTextSelection({ from: 7, to: 12 }).setLink({ href: 'https://x' }).run()
    flush()
    expect(linkedText(ea)).toBe('world')
    expect(linkedText(eb)).toBe('world')

    // Concurrent edits BEFORE the marked range, staged without syncing between them.
    ea.chain().setTextSelection({ from: 1, to: 1 }).insertContent('AAA').run()
    eb.chain().setTextSelection({ from: 1, to: 1 }).insertContent('BBB').run()
    flush() // CRDT merges both

    // The anchored mark still wraps exactly "world" on both peers.
    expect(linkedText(ea)).toBe('world')
    expect(linkedText(eb)).toBe('world')
    // And both converged to the same document.
    expect(ea.getText()).toBe(eb.getText())

    ea.destroy()
    eb.destroy()
  })

  it('side-map edits during prose edits never disturb the prose fragment', () => {
    const ydoc = new Y.Doc()
    const editor = mkEditor(ydoc)
    editor.commands.setContent('<p>stable content</p>')
    const before = getProseFragment(ydoc).toJSON()

    txn(ydoc, TE_ORIGIN.STYLES, (d) => getStylesMap(d).set('Body', { type: 'paragraph', props: { bold: true } }))

    expect(getProseFragment(ydoc).toJSON()).toBe(before)
    editor.destroy()
  })
})
