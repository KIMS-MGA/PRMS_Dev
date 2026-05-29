// Pure, testable helpers for the live word/char/paragraph/page counts and for
// find & replace match counting. No DOM or editor dependency here so they can be
// unit-tested directly in node.

export function computeTextCounts(text) {
  const trimmed = (text || '').trim()
  return {
    words: trimmed ? trimmed.split(/\s+/).length : 0,
    chars: (text || '').length,
  }
}

/** Count top-level text blocks (paragraphs/headings/etc.) in a ProseMirror doc. */
export function countParagraphs(doc) {
  let n = 0
  doc?.forEach?.((node) => {
    if (node.isTextblock) n++
  })
  return n
}

const PAGE_HEIGHTS = { a4: 1123, letter: 1056, legal: 1344 }

/** Estimate page count from rendered content height; null when page sizing is off. */
export function estimatePages(scrollHeight, pageSize) {
  if (!pageSize || pageSize === 'auto') return null
  const pageH = PAGE_HEIGHTS[pageSize] || PAGE_HEIGHTS.a4
  if (!scrollHeight) return 1
  return Math.max(1, Math.ceil(scrollHeight / pageH))
}

export function formatCounts({ words, chars, paras, pages }) {
  const parts = [
    `${words} word${words !== 1 ? 's' : ''}`,
    `${chars} char${chars !== 1 ? 's' : ''}`,
    `${paras} ¶`,
  ]
  if (pages) parts.push(`${pages} page${pages !== 1 ? 's' : ''}`)
  return parts.join(' · ')
}

/** Build a global RegExp from a find query (used for live match counts). */
export function buildMatchRegex({ search, caseSensitive, regexp, wholeWord } = {}) {
  if (!search) return null
  let pattern = regexp ? search : search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  if (wholeWord) pattern = `\\b(?:${pattern})\\b`
  try {
    return new RegExp(pattern, caseSensitive ? 'g' : 'gi')
  } catch {
    return null
  }
}

export function countMatches(text, query) {
  const re = buildMatchRegex(query)
  if (!re) return 0
  const m = (text || '').match(re)
  return m ? m.length : 0
}
