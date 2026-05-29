import { describe, it, expect } from 'vitest'
import { buildPageCss, sizeValue, contentExpr } from './stylesheet.js'

describe('print stylesheet', () => {
  it('sizeValue honors size + orientation + custom dims', () => {
    expect(sizeValue({ size: 'a4', orientation: 'portrait' })).toBe('8.27in 11.69in')
    expect(sizeValue({ size: 'a4', orientation: 'landscape' })).toBe('11.69in 8.27in')
    expect(sizeValue({ size: 'letter', orientation: 'portrait' })).toBe('8.5in 11in')
    expect(sizeValue({ size: 'custom', width: 960, height: 1280 })).toBe('10in 13.333in')
  })

  it('contentExpr maps {page}/{pages} to counters and literals to strings', () => {
    expect(contentExpr('{page}')).toBe('counter(page, decimal)')
    expect(contentExpr('Page {page} of {pages}')).toBe(
      '"Page " counter(page, decimal) " of " counter(pages, decimal)',
    )
    expect(contentExpr('{page}', 'lower-roman')).toBe('counter(page, lower-roman)')
    expect(contentExpr('{title}', 'decimal', { title: 'Report' })).toBe('"Report"')
  })

  it('emits @page with size + margins and a default page-number footer', () => {
    const css = buildPageCss({ size: 'letter', orientation: 'portrait', margins: { top: 96, right: 96, bottom: 96, left: 96 } })
    expect(css).toContain('@page {')
    expect(css).toContain('size: 8.5in 11in')
    expect(css).toContain('margin: 1in 1in 1in 1in')
    expect(css).toContain('@bottom-center { content: counter(page, decimal)')
  })

  it('emits named @page rules + section binding for each section', () => {
    const css = buildPageCss(
      { size: 'a4' },
      { 'sec-1': { size: 'legal', orientation: 'landscape', numbering: { restart: true, start: 1 } } },
    )
    expect(css).toContain('@page te-section-sec-1 {')
    expect(css).toContain('.te-section-sec-1 { page: te-section-sec-1; break-before: page; }')
    expect(css).toContain('size: 14in 8.5in') // legal landscape
    expect(css).toContain('counter-reset: page 0')
  })

  it('emits flow-control, repeating header, and footnote rules', () => {
    const css = buildPageCss({})
    expect(css).toContain('[data-keep-with-next="true"] { break-after: avoid; }')
    expect(css).toContain('[data-page-break-before="true"] { break-before: page; }')
    expect(css).toContain('thead { display: table-header-group; }')
    expect(css).toContain('.te-print-footnote { float: footnote; }')
  })

  it('includes named-style classes', () => {
    const css = buildPageCss({}, {}, { 'My Style': { type: 'paragraph', props: { bold: true } } })
    expect(css).toContain('.te-pstyle-my-style {')
  })

  it('applies multi-column + hyphenation to the body', () => {
    const css = buildPageCss({ columns: 2, hyphenation: true })
    expect(css).toContain('.te-print-body { column-count: 2; column-gap: 24px; }')
    expect(css).toContain('.te-print-body { hyphens: auto; }')
  })
})
