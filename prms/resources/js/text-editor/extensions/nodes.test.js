// @vitest-environment jsdom
import { describe, it, expect } from 'vitest'
import { getSchema } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { DOMParser, DOMSerializer } from '@tiptap/pm/model'
import { wordGradeExtensions } from './index.js'

const schema = getSchema([StarterKit, ...wordGradeExtensions()])

describe('additive nodes/marks (link, bookmark, cross-reference, headings)', () => {
  it('registers the link mark and bookmark/crossReference nodes', () => {
    expect(schema.marks.link).toBeTruthy()
    expect(schema.nodes.bookmark).toBeTruthy()
    expect(schema.nodes.crossReference).toBeTruthy()
  })

  it('supports headings H1-H6 in the schema', () => {
    const h6 = schema.nodes.heading.create({ level: 6 }, schema.text('deep'))
    expect(h6.attrs.level).toBe(6)
  })

  it('parses an existing anchor as a link mark (back-compat with stored HTML)', () => {
    const div = document.createElement('div')
    div.innerHTML = '<p><a href="https://example.com">site</a></p>'
    const doc = DOMParser.fromSchema(schema).parse(div)
    const textNode = doc.child(0).child(0)
    const linkMark = textNode.marks.find((m) => m.type.name === 'link')
    expect(linkMark).toBeTruthy()
    expect(linkMark.attrs.href).toBe('https://example.com')
  })

  it('renders a bookmark as an anchor with id derived from its name', () => {
    const node = schema.nodes.bookmark.create({ name: 'Section A' })
    const html = DOMSerializer.fromSchema(schema).serializeNode(node).outerHTML
    expect(html).toContain('data-bookmark="Section A"')
    expect(html).toContain('id="bm-section-a"')
  })

  it('renders a cross-reference linking to the bookmark id with its label', () => {
    const node = schema.nodes.crossReference.create({ target: 'Section A', label: 'see §A' })
    const dom = DOMSerializer.fromSchema(schema).serializeNode(node)
    expect(dom.getAttribute('href')).toBe('#bm-section-a')
    expect(dom.getAttribute('data-xref')).toBe('Section A')
    expect(dom.textContent).toBe('see §A')
  })

  it('cross-reference round-trips through serialize -> parse', () => {
    const node = schema.nodes.crossReference.create({ target: 'Fig1', label: 'Figure 1' })
    const wrap = document.createElement('div')
    const p = document.createElement('p')
    p.appendChild(DOMSerializer.fromSchema(schema).serializeNode(node))
    wrap.appendChild(p)
    const reparsed = DOMParser.fromSchema(schema).parse(wrap).child(0).child(0)
    expect(reparsed.type.name).toBe('crossReference')
    expect(reparsed.attrs.target).toBe('Fig1')
    expect(reparsed.attrs.label).toBe('Figure 1')
  })
})
