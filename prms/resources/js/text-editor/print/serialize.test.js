// @vitest-environment jsdom
import { describe, it, expect } from 'vitest'
import { serializeForPrint, inlineFootnotes, groupSections, buildToc } from './serialize.js'

describe('print serialize', () => {
  it('inlines footnote content as float:footnote spans', () => {
    const html = '<p>Hello<sup data-footnote-id="a" data-footnote-kind="footnote"></sup> world</p>'
    const out = serializeForPrint(html, { footnotes: { a: { kind: 'footnote', text: 'A note' } }, document })
    expect(out).toContain('class="te-print-footnote"')
    expect(out).toContain('A note')
    expect(out).not.toContain('data-footnote-id')
  })

  it('wraps the whole body in te-print-body', () => {
    const out = serializeForPrint('<p>x</p>', { document })
    expect(out).toContain('class="te-print-body"')
    expect(out).toContain('<p>x</p>')
  })

  it('groups content into per-section wrappers at section breaks', () => {
    const html = '<p>one</p><div data-section-break="true" data-section-id="sec-1"></div><p>two</p>'
    const container = document.createElement('div')
    container.innerHTML = html
    const root = groupSections(container)
    expect(root.querySelector('.te-section-sec-1')).toBeTruthy()
    expect(root.querySelector('.te-section-sec-1').textContent).toContain('two')
    // the break marker itself is dropped
    expect(root.querySelector('[data-section-break]')).toBeNull()
  })

  it('builds a TOC from headings where a placeholder exists', () => {
    const container = document.createElement('div')
    container.innerHTML = '<div data-toc-placeholder="true"></div><h1>Intro</h1><h2>Background</h2><h4>skip</h4>'
    buildToc(container)
    const toc = container.querySelector('.te-toc')
    expect(toc).toBeTruthy()
    const items = toc.querySelectorAll('.te-toc-item')
    expect(items.length).toBe(2) // h4 excluded (depth > 3)
    expect(items[0].textContent).toBe('Intro')
    expect(items[0].getAttribute('href')).toMatch(/^#/)
  })

  it('leaves content untouched when there is no toc placeholder', () => {
    const container = document.createElement('div')
    container.innerHTML = '<h1>Title</h1>'
    buildToc(container)
    expect(container.querySelector('.te-toc')).toBeNull()
  })
})
