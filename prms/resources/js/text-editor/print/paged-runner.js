// Lazy-loaded print/preview/PDF runner. Pulls in Paged.js only when the user
// opens Print Layout or exports a PDF. preview === print === PDF because all
// three consume the SAME serialized body + the SAME generated stylesheet.

import { Previewer } from 'pagedjs'
import { buildPageCss } from './stylesheet.js'
import { serializeForPrint } from './serialize.js'
import { readPageSetup, getSectionsMap, getStylesMap, getFootnotesMap } from '../collab/yroots.js'

function gatherState(instance) {
  const ydoc = instance.ydoc
  const page = ydoc ? readPageSetup(ydoc) : {}
  // page UI state may be ahead of the persisted map on brand-new docs:
  page.size = instance.pageSize || page.size
  page.orientation = instance.orientation || page.orientation
  page.columns = instance.columns ?? page.columns
  page.margins = instance.margins || page.margins
  const sections = ydoc ? getSectionsMap(ydoc).toJSON() : {}
  const styles = ydoc ? getStylesMap(ydoc).toJSON() : {}
  const footnotes = ydoc ? getFootnotesMap(ydoc).toJSON() : {}
  return { page, sections, styles, footnotes }
}

function ctxLiterals(instance) {
  const now = new Date()
  return {
    title: instance.documentTitle || document.title || '',
    date: now.toLocaleDateString(),
    time: now.toLocaleTimeString(),
  }
}

/** Build the print body HTML + CSS for the current document state. */
export function buildPrintArtifacts(instance) {
  const { page, sections, styles, footnotes } = gatherState(instance)
  const css = buildPageCss(page, sections, styles, ctxLiterals(instance))
  const body = serializeForPrint(instance.editor.getHTML(), { footnotes, document })
  return { css, body, margins: page.margins || { top: 50, right: 50, bottom: 50, left: 50 } }
}

/** Open a paginated print-preview overlay; returns a handle with print(). */
export async function openPrintPreview(instance) {
  const { css, body } = buildPrintArtifacts(instance)

  const overlay = document.createElement('div')
  overlay.className = 'te-print-overlay'
  overlay.style.cssText =
    'position:fixed;inset:0;z-index:100000;background:#525659;overflow:auto;padding:24px 0'
  overlay.innerHTML = `
    <div class="te-print-toolbar" style="position:sticky;top:0;display:flex;gap:8px;justify-content:center;padding:8px;background:#3a3d40;margin:-24px 0 24px">
      <button type="button" class="te-print-do te-btn" style="background:#fff">Print / Save as PDF</button>
      <button type="button" class="te-print-close te-btn" style="background:#fff">Close</button>
    </div>
    <div class="te-print-render" style="display:flex;flex-direction:column;align-items:center;gap:16px"></div>
  `
  document.body.appendChild(overlay)

  const target = overlay.querySelector('.te-print-render')
  const previewer = new Previewer()
  await previewer.preview(body, [{ _: css }], target)

  // Pagedjs accepts CSS as text via a Blob URL; some builds want raw strings.
  // Fallback: inject a <style> if the above did not apply.
  if (!target.querySelector('.pagedjs_page')) {
    const style = document.createElement('style')
    style.textContent = css
    target.before(style)
    await previewer.preview(body, [], target)
  }

  const close = () => overlay.remove()
  overlay.querySelector('.te-print-close').addEventListener('click', close)
  overlay.querySelector('.te-print-do').addEventListener('click', () => printArtifacts(css, body))
  overlay.addEventListener('keydown', (e) => { if (e.key === 'Escape') close() })

  return { close, element: overlay }
}

/** Print (and thus Save-as-PDF) the same body+css via an isolated iframe. */
export function printArtifacts(css, body, margins = { top: 50, right: 50, bottom: 50, left: 50 }) {
  const m = margins
  // Setting @page margin to 0 removes the browser's native print headers/footers
  // (title, URL, date, time, page number). Content spacing is handled via body padding.
  const suppressCss = `@page { margin: 0 !important; } body { margin: 0; padding: ${m.top}px ${m.right}px ${m.bottom}px ${m.left}px; box-sizing: border-box; }`
  const iframe = document.createElement('iframe')
  iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0'
  document.body.appendChild(iframe)
  const doc = iframe.contentDocument
  doc.open()
  doc.write(`<!doctype html><html><head><meta charset="utf-8"><style>${suppressCss}${css}</style></head><body>${body}</body></html>`)
  doc.close()

  const run = () => {
    iframe.contentWindow.focus()
    iframe.contentWindow.print()
    setTimeout(() => iframe.remove(), 1000)
  }
  if (doc.readyState === 'complete') setTimeout(run, 50)
  else iframe.onload = () => setTimeout(run, 50)
}

/** Export to PDF == open the native print dialog on the print artifacts. */
export async function exportPdf(instance) {
  const { css, body, margins } = buildPrintArtifacts(instance)
  printArtifacts(css, body, margins)
}
