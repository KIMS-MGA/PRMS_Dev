// Find & replace panel — lazily imported (it pulls in prosemirror-search).
// Wires a small panel into the editor and drives prosemirror-search via the
// TipTap editor's registerPlugin API. Supports match-case, whole-word, regex.

import {
  search,
  SearchQuery,
  setSearchState,
  findNext,
  findPrev,
  replaceNext,
  replaceAll,
} from 'prosemirror-search'
import { countMatches } from './counts.js'

const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]))

function buildPanel(replace) {
  const el = document.createElement('div')
  el.className = 'te-find-panel'
  el.setAttribute('role', 'search')
  el.style.cssText =
    'position:absolute;top:8px;right:16px;z-index:40;display:flex;flex-wrap:wrap;align-items:center;gap:6px;' +
    'background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);padding:8px 10px;font-size:12px'
  el.innerHTML = `
    <input class="te-find-input te-select" type="text" placeholder="Find" aria-label="Find" style="width:160px">
    <input class="te-replace-input te-select" type="text" placeholder="Replace with" aria-label="Replace with" style="width:160px;${replace ? '' : 'display:none'}">
    <span class="te-find-count" aria-live="polite" style="min-width:60px;color:#6b7280"></span>
    <label style="display:inline-flex;align-items:center;gap:3px" title="Match case"><input type="checkbox" class="te-find-case">Aa</label>
    <label style="display:inline-flex;align-items:center;gap:3px" title="Whole word"><input type="checkbox" class="te-find-word">W</label>
    <label style="display:inline-flex;align-items:center;gap:3px" title="Regular expression"><input type="checkbox" class="te-find-regex">.*</label>
    <button type="button" class="te-btn te-find-prev" title="Previous (Shift+Enter)" style="font-size:11px;padding:3px 7px">↑</button>
    <button type="button" class="te-btn te-find-next" title="Next (Enter)" style="font-size:11px;padding:3px 7px">↓</button>
    <button type="button" class="te-btn te-find-replace" title="Replace" style="font-size:11px;padding:3px 7px;${replace ? '' : 'display:none'}">Replace</button>
    <button type="button" class="te-btn te-find-replace-all" title="Replace all" style="font-size:11px;padding:3px 7px;${replace ? '' : 'display:none'}">All</button>
    <button type="button" class="te-btn te-find-close" title="Close (Esc)" style="font-size:13px;padding:3px 7px">×</button>
  `
  return el
}

export function openFindReplace(instance, { replace = false } = {}) {
  const editor = instance.editor
  if (!editor) return

  // Register the search plugin once per editor.
  if (!instance._searchPluginRegistered) {
    editor.registerPlugin(search())
    instance._searchPluginRegistered = true
  }

  // Reuse an existing panel; just toggle replace visibility.
  let panel = instance._findPanel
  if (!panel) {
    panel = buildPanel(replace)
    // Anchor relative to the editor shell.
    const host = instance.container.querySelector('.te-shell') || instance.container
    if (getComputedStyle(host).position === 'static') host.style.position = 'relative'
    host.appendChild(panel)
    instance._findPanel = panel
    wire(instance, panel)
  } else {
    const rep = panel.querySelector('.te-replace-input')
    const rbtn = panel.querySelector('.te-find-replace')
    const rabtn = panel.querySelector('.te-find-replace-all')
    ;[rep, rbtn, rabtn].forEach((n) => { if (n) n.style.display = replace ? '' : 'none' })
    panel.style.display = ''
  }

  const input = panel.querySelector('.te-find-input')
  input.focus()
  input.select()
}

function currentQuery(panel) {
  return {
    search: panel.querySelector('.te-find-input').value,
    replace: panel.querySelector('.te-replace-input').value,
    caseSensitive: panel.querySelector('.te-find-case').checked,
    wholeWord: panel.querySelector('.te-find-word').checked,
    regexp: panel.querySelector('.te-find-regex').checked,
  }
}

function wire(instance, panel) {
  const editor = instance.editor
  const view = editor.view
  const input = panel.querySelector('.te-find-input')
  const countEl = panel.querySelector('.te-find-count')

  const applyQuery = () => {
    const q = currentQuery(panel)
    const query = new SearchQuery({
      search: q.search,
      caseSensitive: q.caseSensitive,
      regexp: q.regexp,
      wholeWord: q.wholeWord,
      replace: q.replace,
    })
    view.dispatch(setSearchState(view.state.tr, query))
    const n = countMatches(editor.getText(), q)
    countEl.textContent = q.search ? `${n} match${n !== 1 ? 'es' : ''}` : ''
  }

  const close = () => {
    panel.style.display = 'none'
    // Clear the search highlight.
    view.dispatch(setSearchState(view.state.tr, new SearchQuery({ search: '' })))
    editor.commands.focus()
  }

  panel.querySelectorAll('input').forEach((i) =>
    i.addEventListener('input', applyQuery),
  )
  panel.querySelectorAll('input[type=checkbox]').forEach((i) =>
    i.addEventListener('change', applyQuery),
  )

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault()
      applyQuery()
      ;(e.shiftKey ? findPrev : findNext)(view.state, view.dispatch)
    } else if (e.key === 'Escape') {
      e.preventDefault()
      close()
    }
  })

  panel.querySelector('.te-find-next').addEventListener('click', () => { applyQuery(); findNext(view.state, view.dispatch); view.focus() })
  panel.querySelector('.te-find-prev').addEventListener('click', () => { applyQuery(); findPrev(view.state, view.dispatch); view.focus() })
  panel.querySelector('.te-find-replace').addEventListener('click', () => { applyQuery(); replaceNext(view.state, view.dispatch); applyQuery() })
  panel.querySelector('.te-find-replace-all').addEventListener('click', () => { applyQuery(); replaceAll(view.state, view.dispatch); applyQuery() })
  panel.querySelector('.te-find-close').addEventListener('click', close)
}
