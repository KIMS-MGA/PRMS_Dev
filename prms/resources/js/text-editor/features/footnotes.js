// Footnote/endnote pane + wiring. Reference markers live in the prose fragment;
// note content lives in te:footnotes. On-screen numbering uses CSS counters.

import { numberFootnotes } from '../extensions/footnote.js'
import { getFootnotesMap, txn, TE_ORIGIN } from '../collab/yroots.js'

const esc = (s) =>
  String(s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]))

/** Inject the CSS that auto-numbers footnote/endnote markers by document order. */
export function injectFootnoteCss(instance) {
  if (instance._footnoteCssEl) return
  const el = document.createElement('style')
  el.className = 'te-footnote-css'
  el.textContent = `
    .te-page .ProseMirror { counter-reset: te-footnote te-endnote; }
    .te-footnote-ref { color:#6366f1; cursor:pointer; font-size:.7em; vertical-align:super; line-height:0; }
    .te-footnote-ref[data-footnote-kind="footnote"]::after { counter-increment: te-footnote; content: counter(te-footnote); }
    .te-footnote-ref[data-footnote-kind="endnote"]::after { counter-increment: te-endnote; content: "[" counter(te-endnote, lower-roman) "]"; }
    .te-footnote-pane { position:absolute; right:16px; bottom:8px; z-index:40; width:360px; max-height:50%; overflow:auto;
      background:#fff; border:1px solid #d1d5db; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.12); padding:10px 12px; font-size:12px; }
    .te-footnote-row { display:flex; gap:6px; align-items:flex-start; margin-bottom:8px; }
    .te-footnote-row .te-fn-num { font-weight:700; color:#6366f1; min-width:22px; }
    .te-footnote-row textarea { flex:1; min-height:38px; border:1px solid #d1d5db; border-radius:4px; padding:4px 6px; font-size:12px; resize:vertical; }
  `
  instance.container.appendChild(el)
  instance._footnoteCssEl = el
}

/** Insert a footnote (or endnote) reference and open the pane focused on it. */
export function insertFootnote(instance, kind = 'footnote') {
  if (instance.readonly) return
  const id = (crypto?.randomUUID && crypto.randomUUID()) || `fn-${Date.now()}-${Math.random().toString(36).slice(2)}`
  if (instance.ydoc) {
    txn(instance.ydoc, TE_ORIGIN.FOOTNOTES, (d) => {
      getFootnotesMap(d).set(id, { kind, text: '' })
    })
  }
  instance.editor.chain().focus().insertFootnoteRef({ footnoteId: id, kind }).run()
  openFootnotePane(instance, id)
}

/** Build/refresh the footnote editing pane. */
export function openFootnotePane(instance, focusId) {
  injectFootnoteCss(instance)
  let pane = instance._footnotePane
  if (!pane) {
    pane = document.createElement('div')
    pane.className = 'te-footnote-pane'
    pane.setAttribute('role', 'complementary')
    pane.setAttribute('aria-label', 'Footnotes')
    const host = instance.container.querySelector('.te-shell') || instance.container
    if (getComputedStyle(host).position === 'static') host.style.position = 'relative'
    host.appendChild(pane)
    instance._footnotePane = pane
  }
  pane.style.display = ''
  renderFootnotePane(instance, focusId)
}

export function renderFootnotePane(instance, focusId) {
  const pane = instance._footnotePane
  if (!pane) return
  const ordered = numberFootnotes(instance.editor.state.doc)
  const map = instance.ydoc ? getFootnotesMap(instance.ydoc) : null

  if (ordered.length === 0) {
    pane.innerHTML =
      '<div style="display:flex;justify-content:space-between;align-items:center"><strong>Footnotes</strong>' +
      '<button type="button" class="te-fn-close te-btn" style="font-size:13px;padding:2px 7px">×</button></div>' +
      '<p style="color:#6b7280;margin:8px 0 0">No footnotes yet.</p>'
    pane.querySelector('.te-fn-close')?.addEventListener('click', () => (pane.style.display = 'none'))
    return
  }

  pane.innerHTML =
    '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><strong>Footnotes &amp; endnotes</strong>' +
    '<button type="button" class="te-fn-close te-btn" style="font-size:13px;padding:2px 7px">×</button></div>' +
    ordered
      .map((f) => {
        const data = map?.get(f.id) || { text: '' }
        const label = f.kind === 'endnote' ? `e${f.n}` : `${f.n}`
        return `<div class="te-footnote-row" data-fn-id="${esc(f.id)}">
          <span class="te-fn-num">${label}</span>
          <textarea aria-label="Footnote ${label}" placeholder="Note text…">${esc(data.text)}</textarea>
          <button type="button" class="te-fn-del te-btn" title="Delete note" style="font-size:11px;padding:2px 6px">✕</button>
        </div>`
      })
      .join('')

  pane.querySelector('.te-fn-close')?.addEventListener('click', () => (pane.style.display = 'none'))

  pane.querySelectorAll('.te-footnote-row').forEach((row) => {
    const id = row.dataset.fnId
    const ta = row.querySelector('textarea')
    ta.addEventListener('input', () => {
      if (!instance.ydoc) return
      txn(instance.ydoc, TE_ORIGIN.FOOTNOTES, (d) => {
        const m = getFootnotesMap(d)
        const prev = m.get(id) || { kind: 'footnote' }
        m.set(id, { ...prev, text: ta.value })
      })
    })
    row.querySelector('.te-fn-del')?.addEventListener('click', () => deleteFootnote(instance, id))
    if (id === focusId) ta.focus()
  })
}

/** Remove a footnote: delete its content entry and its reference node. */
export function deleteFootnote(instance, id) {
  if (instance.ydoc) {
    txn(instance.ydoc, TE_ORIGIN.FOOTNOTES, (d) => getFootnotesMap(d).delete(id))
  }
  const { state, view } = instance.editor
  let tr = state.tr
  let removed = false
  state.doc.descendants((node, pos) => {
    if (!removed && node.type.name === 'footnoteRef' && node.attrs.footnoteId === id) {
      tr = tr.delete(pos, pos + node.nodeSize)
      removed = true
      return false
    }
  })
  if (removed) view.dispatch(tr)
  renderFootnotePane(instance)
}
