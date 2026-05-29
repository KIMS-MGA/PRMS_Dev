// Named paragraph/character styles. Definitions live in the te:styles Y.Map and
// merge over a built-in default set. propsToCss/stylesToCss are pure so they can
// drive BOTH the on-screen stylesheet and the print stylesheet, and be unit-tested.

import { cssSafe } from '../extensions/index.js'
import { getStylesMap, TE_ORIGIN, txn } from '../collab/yroots.js'

// Built-in Word-like styles. `props` are abstract; propsToCss maps them to CSS.
export const DEFAULT_STYLES = Object.freeze({
  Normal: { type: 'paragraph', props: { fontFamily: 'Times New Roman', fontSize: 12 } },
  Title: { type: 'paragraph', props: { fontSize: 26, bold: true, spaceAfter: 8 } },
  Subtitle: { type: 'paragraph', props: { fontSize: 16, color: '#6b7280', spaceAfter: 6 } },
  'Heading 1': { type: 'paragraph', props: { fontSize: 20, bold: true, color: '#1f2937', spaceBefore: 12, spaceAfter: 4 } },
  'Heading 2': { type: 'paragraph', props: { fontSize: 16, bold: true, color: '#374151', spaceBefore: 10, spaceAfter: 4 } },
  'Heading 3': { type: 'paragraph', props: { fontSize: 14, bold: true, color: '#374151', spaceBefore: 8, spaceAfter: 3 } },
  Quote: { type: 'paragraph', props: { italic: true, color: '#4b5563', indentLeft: 24, indentRight: 24 } },
  Caption: { type: 'paragraph', props: { fontSize: 9, italic: true, color: '#6b7280' } },
  Emphasis: { type: 'character', props: { italic: true } },
  Strong: { type: 'character', props: { bold: true } },
})

/** Map an abstract style props object to a CSS declaration string. */
export function propsToCss(props = {}) {
  const d = []
  if (props.fontFamily) d.push(`font-family: ${props.fontFamily}`)
  if (props.fontSize != null) d.push(`font-size: ${props.fontSize}pt`)
  if (props.bold) d.push('font-weight: 700')
  if (props.italic) d.push('font-style: italic')
  if (props.underline) d.push('text-decoration: underline')
  if (props.color) d.push(`color: ${props.color}`)
  if (props.background) d.push(`background-color: ${props.background}`)
  if (props.lineSpacing) d.push(`line-height: ${props.lineSpacing}`)
  if (props.spaceBefore != null) d.push(`margin-top: ${props.spaceBefore}pt`)
  if (props.spaceAfter != null) d.push(`margin-bottom: ${props.spaceAfter}pt`)
  if (props.indentLeft != null) d.push(`margin-left: ${props.indentLeft}pt`)
  if (props.indentRight != null) d.push(`margin-right: ${props.indentRight}pt`)
  if (props.firstLineIndent != null) d.push(`text-indent: ${props.firstLineIndent}pt`)
  if (props.align) d.push(`text-align: ${props.align}`)
  return d.join('; ')
}

/** Build a stylesheet string for all named styles. */
export function stylesToCss(styles = {}) {
  let css = ''
  for (const [id, def] of Object.entries(styles)) {
    if (!def || !def.props) continue
    const prefix = def.type === 'character' ? 'te-cstyle' : 'te-pstyle'
    const decls = propsToCss(def.props)
    if (decls) css += `.${prefix}-${cssSafe(id)} { ${decls}; }\n`
  }
  return css
}

/** Effective styles = built-ins overridden/extended by the user's te:styles map. */
export function mergedStyles(userStyles = {}) {
  return { ...DEFAULT_STYLES, ...userStyles }
}

// ---------------------------------------------------------------------------
// Instance wiring (DOM side; not unit-tested)
// ---------------------------------------------------------------------------

/** Inject/refresh the on-screen stylesheet for named styles. */
export function injectStylesSheet(instance) {
  const ydoc = instance.ydoc
  const user = ydoc ? getStylesMap(ydoc).toJSON() : {}
  const css = stylesToCss(mergedStyles(user))
  let el = instance._stylesSheetEl
  if (!el) {
    el = document.createElement('style')
    el.className = 'te-named-styles'
    instance.container.appendChild(el)
    instance._stylesSheetEl = el
  }
  el.textContent = css
}

/** Populate the toolbar style <select> with the effective style names. */
export function populateStyleSelect(instance) {
  const sel = instance.container.querySelector('.te-style-select')
  if (!sel) return
  const ydoc = instance.ydoc
  const user = ydoc ? getStylesMap(ydoc).toJSON() : {}
  const all = mergedStyles(user)
  sel.innerHTML =
    '<option value="">Styles</option>' +
    Object.entries(all)
      .filter(([, def]) => def.type !== 'character')
      .map(([id]) => `<option value="${id}">${id}</option>`)
      .join('')
}

/** Create or update a user style definition in te:styles. */
export function saveStyle(instance, id, def) {
  if (!instance.ydoc) return
  txn(instance.ydoc, TE_ORIGIN.STYLES, (d) => getStylesMap(d).set(id, def))
}

/** Wire the StylesManager: inject sheet, fill select, react to remote changes. */
export function setupStyles(instance) {
  injectStylesSheet(instance)
  populateStyleSelect(instance)

  if (instance.ydoc) {
    const map = getStylesMap(instance.ydoc)
    instance._stylesObserver = () => {
      injectStylesSheet(instance)
      populateStyleSelect(instance)
    }
    map.observe(instance._stylesObserver)
  }

  const sel = instance.container.querySelector('.te-style-select')
  sel?.addEventListener('change', (e) => {
    const id = e.target.value
    if (id) instance.editor.chain().focus().setParagraphStyle(id).run()
    e.target.value = ''
  })
}
