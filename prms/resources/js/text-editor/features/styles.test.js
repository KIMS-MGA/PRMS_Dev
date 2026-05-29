import { describe, it, expect } from 'vitest'
import { propsToCss, stylesToCss, mergedStyles, DEFAULT_STYLES } from './styles.js'

describe('named styles', () => {
  it('propsToCss maps abstract props to CSS declarations', () => {
    const css = propsToCss({ fontSize: 14, bold: true, color: '#111', spaceBefore: 6, align: 'center' })
    expect(css).toContain('font-size: 14pt')
    expect(css).toContain('font-weight: 700')
    expect(css).toContain('color: #111')
    expect(css).toContain('margin-top: 6pt')
    expect(css).toContain('text-align: center')
  })

  it('stylesToCss emits prefixed classes for paragraph vs character styles', () => {
    const css = stylesToCss({
      'Heading 1': { type: 'paragraph', props: { bold: true } },
      Emphasis: { type: 'character', props: { italic: true } },
    })
    expect(css).toContain('.te-pstyle-heading-1 {')
    expect(css).toContain('.te-cstyle-emphasis {')
    expect(css).toContain('font-weight: 700')
    expect(css).toContain('font-style: italic')
  })

  it('stylesToCss skips entries without props', () => {
    expect(stylesToCss({ Empty: { type: 'paragraph' } })).toBe('')
  })

  it('mergedStyles overlays user styles over the built-ins', () => {
    const merged = mergedStyles({ 'Heading 1': { type: 'paragraph', props: { fontSize: 99 } }, Custom: { type: 'paragraph', props: {} } })
    expect(merged['Heading 1'].props.fontSize).toBe(99) // overridden
    expect(merged.Normal).toEqual(DEFAULT_STYLES.Normal) // built-in retained
    expect(merged.Custom).toBeTruthy() // user-added
  })
})
