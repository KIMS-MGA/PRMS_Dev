// @vitest-environment jsdom
import { describe, it, expect } from 'vitest'
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { wordGradeExtensions } from './index.js'
import { collectMarkRanges, resolveSuggestions } from './track-changes.js'

function makeEditor(content) {
  return new Editor({
    element: document.createElement('div'),
    extensions: [StarterKit, ...wordGradeExtensions()],
    content,
  })
}

describe('track changes accept/reject', () => {
  it('collectMarkRanges finds suggestion mark ranges', () => {
    const editor = makeEditor('<p>keep <ins data-tc-id="1">added</ins> <del data-tc-id="2">gone</del></p>')
    expect(collectMarkRanges(editor.state.doc, 'suggestionInsert').length).toBe(1)
    expect(collectMarkRanges(editor.state.doc, 'suggestionDelete').length).toBe(1)
    editor.destroy()
  })

  it('accept keeps inserted text and removes deleted text', () => {
    const editor = makeEditor('<p>keep <ins data-tc-id="1">added</ins><del data-tc-id="2">gone</del></p>')
    const tr = resolveSuggestions(editor.state, 'accept')
    editor.view.dispatch(tr)
    const text = editor.getText()
    expect(text).toContain('added')
    expect(text).not.toContain('gone')
    // no suggestion marks remain
    expect(editor.getHTML()).not.toContain('data-tc-id')
    editor.destroy()
  })

  it('reject removes inserted text and restores deleted text', () => {
    const editor = makeEditor('<p>keep <ins data-tc-id="1">added</ins><del data-tc-id="2">gone</del></p>')
    const tr = resolveSuggestions(editor.state, 'reject')
    editor.view.dispatch(tr)
    const text = editor.getText()
    expect(text).not.toContain('added')
    expect(text).toContain('gone')
    expect(editor.getHTML()).not.toContain('data-tc-id')
    editor.destroy()
  })

  it('acceptAllSuggestions command works through the editor chain', () => {
    const editor = makeEditor('<p>x<del data-tc-id="9">y</del>z</p>')
    editor.commands.acceptAllSuggestions()
    expect(editor.getText()).toBe('xz')
    editor.destroy()
  })
})
