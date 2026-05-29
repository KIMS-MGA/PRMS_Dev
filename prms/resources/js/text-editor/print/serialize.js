// Transform the editor's rendered HTML into print-ready HTML for Paged.js.
// Operates on a DOM container so it is testable under jsdom. Responsibilities:
//   - inline footnote/endnote content as Paged.js float:footnote elements;
//   - group content into per-section wrappers at sectionBreak boundaries;
//   - build a Table of Contents from headings where a tocPlaceholder exists.
// Named-style classes and paragraph-format inline styles are already present in
// the editor HTML, so the print output matches the on-screen styling exactly.

const esc = (s) =>
  String(s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]))

/** Replace footnote-ref <sup> markers with Paged.js float:footnote spans. */
export function inlineFootnotes(container, footnotes = {}) {
  container.querySelectorAll('sup[data-footnote-id]').forEach((sup) => {
    const id = sup.getAttribute('data-footnote-id')
    const note = footnotes[id]
    if (!note) return
    const span = container.ownerDocument.createElement('span')
    span.className = 'te-print-footnote'
    span.setAttribute('data-footnote-kind', note.kind || 'footnote')
    span.textContent = note.text || ''
    sup.replaceWith(span)
  })
}

/** Group top-level nodes into section wrappers at each sectionBreak. */
export function groupSections(container) {
  const doc = container.ownerDocument
  const root = doc.createElement('div')
  root.className = 'te-print-body'

  let current = root
  const children = Array.from(container.childNodes)
  for (const node of children) {
    if (node.nodeType === 1 && node.getAttribute && node.getAttribute('data-section-break') === 'true') {
      const id = node.getAttribute('data-section-id')
      const wrap = doc.createElement('div')
      wrap.className = id ? `te-section te-section-${cssSafeLocal(id)}` : 'te-section'
      root.appendChild(wrap)
      current = wrap
      continue // drop the break marker itself
    }
    current.appendChild(node)
  }
  return root
}

// Local cssSafe mirror (avoid importing the extensions module into print code).
function cssSafeLocal(id) {
  return String(id).trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
}

/** Build a Table of Contents from headings, replacing any tocPlaceholder. */
export function buildToc(root) {
  const placeholder = root.querySelector('[data-toc-placeholder]')
  if (!placeholder) return
  const doc = root.ownerDocument
  const toc = doc.createElement('nav')
  toc.className = 'te-toc'
  const headings = root.querySelectorAll('h1, h2, h3, h4, h5, h6')
  let n = 0
  headings.forEach((h) => {
    const level = Number(h.tagName.slice(1))
    if (level > 3) return
    n++
    let id = h.id
    if (!id) {
      id = `te-h-${n}`
      h.id = id
    }
    const a = doc.createElement('a')
    a.href = `#${id}`
    a.className = `te-toc-item te-toc-l${level}`
    a.textContent = h.textContent
    toc.appendChild(a)
  })
  placeholder.replaceWith(toc)
}

/**
 * Produce the print body HTML string.
 * @param {string} bodyHtml  editor.getHTML()
 * @param {object} opts      { footnotes, document } (document = a DOM document, for tests)
 */
export function serializeForPrint(bodyHtml, opts = {}) {
  const d = opts.document || document
  const container = d.createElement('div')
  container.innerHTML = bodyHtml || ''

  inlineFootnotes(container, opts.footnotes || {})
  const root = groupSections(container)
  buildToc(root)

  return root.outerHTML
}
