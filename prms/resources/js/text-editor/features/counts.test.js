import { describe, it, expect } from 'vitest'
import {
  computeTextCounts,
  estimatePages,
  formatCounts,
  buildMatchRegex,
  countMatches,
} from './counts.js'

describe('counts', () => {
  it('computeTextCounts counts words and characters', () => {
    expect(computeTextCounts('hello world')).toEqual({ words: 2, chars: 11 })
    expect(computeTextCounts('   ')).toEqual({ words: 0, chars: 3 })
    expect(computeTextCounts('')).toEqual({ words: 0, chars: 0 })
    expect(computeTextCounts('one  two   three')).toEqual({ words: 3, chars: 16 })
  })

  it('estimatePages divides content height by the page height', () => {
    expect(estimatePages(0, 'a4')).toBe(1)
    expect(estimatePages(1123, 'a4')).toBe(1)
    expect(estimatePages(1124, 'a4')).toBe(2)
    expect(estimatePages(2112, 'letter')).toBe(2) // 1056 * 2
    expect(estimatePages(500, 'auto')).toBeNull()
    expect(estimatePages(500, null)).toBeNull()
  })

  it('formatCounts renders a compact summary, pages only when known', () => {
    expect(formatCounts({ words: 1, chars: 5, paras: 1 })).toBe('1 word · 5 chars · 1 ¶')
    expect(formatCounts({ words: 3, chars: 9, paras: 2, pages: 2 })).toBe(
      '3 words · 9 chars · 2 ¶ · 2 pages',
    )
  })

  it('buildMatchRegex escapes literals, honors flags, and handles whole-word', () => {
    expect('a.b.c'.match(buildMatchRegex({ search: 'a.b' })).length).toBe(1) // literal dot
    expect('axb'.match(buildMatchRegex({ search: 'a.b', regexp: true })).length).toBe(1)
    expect(buildMatchRegex({ search: 'A' }).flags).toContain('i')
    expect(buildMatchRegex({ search: 'A', caseSensitive: true }).flags).not.toContain('i')
    expect('cat catalog'.match(buildMatchRegex({ search: 'cat', wholeWord: true })).length).toBe(1)
    expect(buildMatchRegex({ search: '' })).toBeNull()
    expect(buildMatchRegex({ search: '(' , regexp: true })).toBeNull() // invalid regex -> null
  })

  it('countMatches counts occurrences', () => {
    expect(countMatches('the the THE', { search: 'the' })).toBe(3)
    expect(countMatches('the the THE', { search: 'the', caseSensitive: true })).toBe(2)
    expect(countMatches('nothing here', { search: 'xyz' })).toBe(0)
  })
})
