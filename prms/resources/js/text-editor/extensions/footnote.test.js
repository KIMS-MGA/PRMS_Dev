// @vitest-environment jsdom
import { describe, it, expect } from 'vitest'
import { getSchema } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { wordGradeExtensions } from './index.js'
import { numberFootnotes } from './footnote.js'

const schema = getSchema([StarterKit, ...wordGradeExtensions()])

// Build a doc: paragraph with [footnote a][endnote x][footnote b].
function docWithNotes() {
  const ref = (id, kind) => schema.nodes.footnoteRef.create({ footnoteId: id, kind })
  const para = schema.nodes.paragraph.create(null, [
    schema.text('A'), ref('a', 'footnote'),
    schema.text('B'), ref('x', 'endnote'),
    schema.text('C'), ref('b', 'footnote'),
  ])
  return schema.nodes.doc.create(null, [para])
}

describe('footnotes', () => {
  it('registers the footnoteRef node', () => {
    expect(schema.nodes.footnoteRef).toBeTruthy()
  })

  it('numbers footnotes and endnotes in independent document-order sequences', () => {
    const result = numberFootnotes(docWithNotes())
    expect(result).toEqual([
      { id: 'a', kind: 'footnote', n: 1 },
      { id: 'x', kind: 'endnote', n: 1 },
      { id: 'b', kind: 'footnote', n: 2 },
    ])
  })

  it('returns empty for a doc with no footnotes', () => {
    const doc = schema.nodes.doc.create(null, [schema.nodes.paragraph.create(null, schema.text('hi'))])
    expect(numberFootnotes(doc)).toEqual([])
  })
})
