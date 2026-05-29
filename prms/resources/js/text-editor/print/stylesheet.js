// Pure print-stylesheet builder. Turns the page/sections/styles state into a CSS
// string consumed by Paged.js (and the native print path — same CSS). No DOM, so
// fully unit-testable. Reuses the named-style CSS generator from features/styles.

import { stylesToCss, mergedStyles } from '../features/styles.js'

// Physical page sizes in inches.
const SIZE_IN = {
  a4: { w: 8.27, h: 11.69 },
  letter: { w: 8.5, h: 11 },
  legal: { w: 8.5, h: 14 },
}

const NUM_FORMAT = {
  decimal: 'decimal',
  'lower-roman': 'lower-roman',
  'upper-roman': 'upper-roman',
  'lower-alpha': 'lower-alpha',
  'upper-alpha': 'upper-alpha',
}

const pxToIn = (px) => +(Number(px || 0) / 96).toFixed(3)
const esc = (s) => String(s).replace(/["\\]/g, '\\$&')

/** Page size as a CSS `size` value, honoring orientation + custom dimensions. */
export function sizeValue(setup) {
  if (setup.size === 'custom' && setup.width && setup.height) {
    const w = pxToIn(setup.width)
    const h = pxToIn(setup.height)
    return setup.orientation === 'landscape' ? `${h}in ${w}in` : `${w}in ${h}in`
  }
  const s = SIZE_IN[setup.size] || SIZE_IN.a4
  return setup.orientation === 'landscape' ? `${s.h}in ${s.w}in` : `${s.w}in ${s.h}in`
}

function marginValue(m = {}) {
  const t = pxToIn(m.top ?? 96)
  const r = pxToIn(m.right ?? 96)
  const b = pxToIn(m.bottom ?? 96)
  const l = pxToIn(m.left ?? 96)
  return `${t}in ${r}in ${b}in ${l}in`
}

/**
 * Build a CSS `content` expression for a header/footer template.
 * Tokens: {page} {pages} -> counters; {title} {date} {time} {section} -> literals
 * from ctx. Literal text becomes quoted strings.
 */
export function contentExpr(tpl, fmt = 'decimal', ctx = {}) {
  if (!tpl) return null
  const counterFmt = NUM_FORMAT[fmt] || 'decimal'
  const parts = []
  const re = /\{(page|pages|title|date|time|section)\}/g
  let last = 0
  let m
  const pushLiteral = (text) => {
    if (text) parts.push(`"${esc(text)}"`)
  }
  while ((m = re.exec(tpl))) {
    pushLiteral(tpl.slice(last, m.index))
    const tok = m[1]
    if (tok === 'page') parts.push(`counter(page, ${counterFmt})`)
    else if (tok === 'pages') parts.push(`counter(pages, ${counterFmt})`)
    else pushLiteral(ctx[tok] != null ? String(ctx[tok]) : '')
    last = re.lastIndex
  }
  pushLiteral(tpl.slice(last))
  return parts.length ? parts.join(' ') : '""'
}

// Map header/footer slot keys to Paged.js margin boxes.
const SLOTS = {
  left: '@top-left',
  center: '@top-center',
  right: '@top-right',
}
const FOOTER_SLOTS = {
  left: '@bottom-left',
  center: '@bottom-center',
  right: '@bottom-right',
}

function marginBoxes(header, footer, fmt, ctx) {
  const lines = []
  const emit = (slots, cfg) => {
    if (!cfg) return
    for (const [key, box] of Object.entries(slots)) {
      const expr = contentExpr(cfg[key], fmt, ctx)
      if (expr && expr !== '""') lines.push(`  ${box} { content: ${expr}; font-size: 9pt; color: #444; }`)
    }
  }
  emit(SLOTS, header)
  emit(FOOTER_SLOTS, footer)
  return lines.join('\n')
}

function pageRule(name, setup, ctx) {
  const sel = name ? `@page ${name}` : '@page'
  const fmt = setup.numbering?.format || 'decimal'
  // Default footer shows the page number centered when nothing is configured.
  const footer = setup.footer || { center: '{page}' }
  const header = setup.header || null
  const boxes = marginBoxes(header, footer, fmt, ctx)
  return `${sel} {
  size: ${sizeValue(setup)};
  margin: ${marginValue(setup.margins)};
${boxes}
}`
}

/**
 * Build the full print stylesheet.
 * @param {object} page    default page setup (te:page)
 * @param {object} sections per-section overrides keyed by sectionId (te:sections)
 * @param {object} styles  user named styles (te:styles)
 * @param {object} ctx     runtime literals for headers/footers {title,date,time}
 */
export function buildPageCss(page = {}, sections = {}, styles = {}, ctx = {}) {
  const out = []

  // Default page + per-section named pages.
  out.push(pageRule(null, page, ctx))
  const startFmt = page.numbering?.format || 'decimal'
  out.push(`.te-print-body { counter-reset: page ${(page.numbering?.start || 1) - 1}; }`)

  for (const [id, sec] of Object.entries(sections || {})) {
    const merged = { ...page, ...sec }
    out.push(pageRule(`te-section-${id}`, merged, ctx))
    let rule = `.te-section-${id} { page: te-section-${id}; break-before: page; }`
    if (merged.numbering?.restart) {
      rule += `\n.te-section-${id} { counter-reset: page ${(merged.numbering?.start || 1) - 1}; }`
    }
    if (merged.columns > 1) {
      rule += `\n.te-section-${id} { column-count: ${merged.columns}; column-gap: 24px; }`
    }
    out.push(rule)
  }

  // Body-level flow + multi-column for the default section.
  out.push('.te-print-body { orphans: 2; widows: 2; }')
  if (page.columns > 1) {
    out.push(`.te-print-body { column-count: ${page.columns}; column-gap: 24px; }`)
  }
  if (page.hyphenation) out.push('.te-print-body { hyphens: auto; }')

  // Word paragraph flow-control attributes -> CSS fragmentation.
  out.push('[data-keep-with-next="true"] { break-after: avoid; }')
  out.push('[data-keep-lines="true"] { break-inside: avoid; }')
  out.push('[data-page-break-before="true"] { break-before: page; }')
  out.push('[data-page-break="true"], [data-section-break="true"] { break-before: page; }')
  out.push('[data-column-break="true"] { break-before: column; }')

  // Repeat table header rows across page breaks.
  out.push('.te-print-body table thead { display: table-header-group; }')
  out.push('.te-print-body table[data-repeat-header="true"] thead { display: table-header-group; }')

  // Paged.js footnotes pinned to the page bottom.
  out.push('.te-print-footnote { float: footnote; }')
  out.push('@page { @footnote { border-top: 0.5pt solid #999; } }')

  // Named styles (shared with the on-screen renderer).
  out.push(stylesToCss(mergedStyles(styles)))

  return out.join('\n')
}
