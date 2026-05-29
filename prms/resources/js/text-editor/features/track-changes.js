// DOM-side wiring for track changes: the CSS that visually distinguishes
// suggested insertions (underlined/green) from deletions (struck/red), plus a
// screen-reader announcement helper used when toggling the mode.

export function injectTrackChangesCss(instance) {
  if (instance._tcCssEl) return
  const el = document.createElement('style')
  el.className = 'te-track-changes-css'
  el.textContent = `
    ins.te-tc-insert { text-decoration: underline; color: #15803d; background: rgba(34,197,94,.10); text-decoration-skip-ink: none; }
    del.te-tc-delete { text-decoration: line-through; color: #b91c1c; background: rgba(239,68,68,.10); }
    .te-shell.te-suggesting .te-editor-body { box-shadow: inset 0 0 0 2px rgba(99,102,241,.25); }
  `
  instance.container.appendChild(el)
  instance._tcCssEl = el
}
