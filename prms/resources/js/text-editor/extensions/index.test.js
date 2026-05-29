// @vitest-environment jsdom
import { describe, it, expect } from 'vitest'
import { getSchema } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { DOMParser, DOMSerializer } from '@tiptap/pm/model'
import { wordGradeExtensions, cssSafe } from './index.js'

// StarterKit supplies paragraph/heading/lists. The table/image/textStyle global
// attributes simply no-op when those types aren't in the schema, which is the
// additive guarantee we want to prove.
const schema = getSchema([StarterKit, ...wordGradeExtensions()])

describe('wordGradeExtensions (additive global attrs)', () => {
  it('adds the new attributes to paragraph and heading with safe defaults', () => {
    const attrs = schema.nodes.paragraph.spec.attrs
    for (const k of [
      'spaceBefore', 'spaceAfter', 'firstLineIndent', 'indentLeft',
      'indentRight', 'keepWithNext', 'keepLines', 'pageBreakBefore',
      'widowControl', 'paragraphStyleId',
    ]) {
      expect(attrs, `paragraph missing ${k}`).toHaveProperty(k)
    }
    expect(attrs.spaceBefore.default).toBeNull()
    expect(attrs.keepWithNext.default).toBe(false)
    expect(attrs.paragraphStyleId.default).toBeNull()
  })

  it('parses pre-existing/plain HTML with all new attrs at their defaults (back-compat)', () => {
    const div = document.createElement('div')
    div.innerHTML = '<p>hello</p><h2>title</h2><ul><li>a</li></ul>'
    const doc = DOMParser.fromSchema(schema).parse(div)

    const p = doc.child(0)
    expect(p.type.name).toBe('paragraph')
    expect(p.attrs.spaceBefore).toBeNull()
    expect(p.attrs.keepWithNext).toBe(false)
    expect(p.attrs.paragraphStyleId).toBeNull()
    expect(p.textContent).toBe('hello')

    const h = doc.child(1)
    expect(h.type.name).toBe('heading')
    expect(h.attrs.level).toBe(2)
  })

  it('renders paragraph formatting as inline CSS + flow flags as data-*', () => {
    const node = schema.nodes.paragraph.create(
      { spaceBefore: 12, indentLeft: 36, keepWithNext: true, pageBreakBefore: true },
      schema.text('x'),
    )
    const dom = DOMSerializer.fromSchema(schema).serializeNode(node)
    const html = dom.outerHTML
    expect(html).toContain('margin-top: 12pt')
    expect(html).toContain('margin-left: 36pt')
    expect(html).toContain('data-keep-with-next="true"')
    expect(html).toContain('data-page-break-before="true"')
  })

  it('renders a named paragraph style as data + sanitized class', () => {
    const node = schema.nodes.paragraph.create(
      { paragraphStyleId: 'Heading 1' },
      schema.text('x'),
    )
    const html = DOMSerializer.fromSchema(schema).serializeNode(node).outerHTML
    expect(html).toContain('data-pstyle="Heading 1"')
    expect(html).toContain('te-pstyle-heading-1')
  })

  it('round-trips: nodes with new attrs serialize then re-parse identically', () => {
    const node = schema.nodes.paragraph.create({ spaceAfter: 6, keepLines: true }, schema.text('y'))
    const dom = DOMSerializer.fromSchema(schema).serializeNode(node)
    const wrap = document.createElement('div')
    wrap.appendChild(dom)
    const reparsed = DOMParser.fromSchema(schema).parse(wrap).child(0)
    expect(reparsed.attrs.spaceAfter).toBe(6)
    expect(reparsed.attrs.keepLines).toBe(true)
  })

  it('cssSafe sanitizes style names into class tokens', () => {
    expect(cssSafe('Heading 1')).toBe('heading-1')
    expect(cssSafe('  My Style!! ')).toBe('my-style')
    expect(cssSafe('Body_Text')).toBe('body-text')
  })
})
