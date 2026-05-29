// @vitest-environment jsdom
import { describe, it, expect } from 'vitest'
import { getSchema } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { DOMParser, DOMSerializer } from '@tiptap/pm/model'
import { wordGradeExtensions } from './index.js'

const schema = getSchema([StarterKit, ...wordGradeExtensions()])

describe('section / column break nodes', () => {
  it('registers sectionBreak and columnBreak block nodes', () => {
    expect(schema.nodes.sectionBreak).toBeTruthy()
    expect(schema.nodes.columnBreak).toBeTruthy()
  })

  it('section break carries sectionId + breakType and round-trips', () => {
    const node = schema.nodes.sectionBreak.create({ sectionId: 'sec-1', breakType: 'oddPage' })
    const html = DOMSerializer.fromSchema(schema).serializeNode(node).outerHTML
    expect(html).toContain('data-section-break="true"')
    expect(html).toContain('data-section-id="sec-1"')
    expect(html).toContain('data-break-type="oddPage"')

    const wrap = document.createElement('div')
    wrap.appendChild(DOMSerializer.fromSchema(schema).serializeNode(node))
    const reparsed = DOMParser.fromSchema(schema).parse(wrap).child(0)
    expect(reparsed.type.name).toBe('sectionBreak')
    expect(reparsed.attrs.sectionId).toBe('sec-1')
    expect(reparsed.attrs.breakType).toBe('oddPage')
  })

  it('breakType defaults to nextPage', () => {
    const node = schema.nodes.sectionBreak.create({ sectionId: 'x' })
    expect(node.attrs.breakType).toBe('nextPage')
  })
})
