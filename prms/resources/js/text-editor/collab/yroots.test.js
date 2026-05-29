import { describe, it, expect } from 'vitest'
import * as Y from 'yjs'
import {
  TE_ROOTS,
  TE_ORIGIN,
  PROSE_FRAGMENT_KEY,
  getProseFragment,
  getSectionsMap,
  getStylesMap,
  getPageMap,
  txn,
  readPageSetup,
  DEFAULT_PAGE_SETUP,
  hasWordGradeState,
} from './yroots.js'

// Build a doc whose "default" fragment holds some prose-like content, mimicking
// what y-prosemirror would persist (XmlElement blocks containing XmlText).
function seededDoc() {
  const doc = new Y.Doc()
  const frag = getProseFragment(doc)
  const p1 = new Y.XmlElement('paragraph')
  p1.insert(0, [new Y.XmlText('Hello world')])
  const h = new Y.XmlElement('heading')
  h.setAttribute('level', '1')
  h.insert(0, [new Y.XmlText('Title')])
  frag.insert(0, [h, p1])
  return doc
}

describe('yroots', () => {
  it('side-map writes leave the prose fragment byte-identical', () => {
    const doc = seededDoc()
    const frag = getProseFragment(doc)
    const before = frag.toJSON()

    txn(doc, TE_ORIGIN.SECTIONS, (d) => {
      getSectionsMap(d).set('sec-1', { size: 'letter', orientation: 'landscape' })
    })
    txn(doc, TE_ORIGIN.STYLES, (d) => {
      getStylesMap(d).set('Heading 1', { type: 'paragraph', props: { bold: true } })
    })
    txn(doc, TE_ORIGIN.PAGE, (d) => {
      getPageMap(d).set('size', 'legal')
    })

    expect(frag.toJSON()).toBe(before)
  })

  it('only declares the prose fragment plus te:* namespaced roots', () => {
    const doc = seededDoc()
    txn(doc, TE_ORIGIN.SECTIONS, (d) => getSectionsMap(d).set('s', {}))
    txn(doc, TE_ORIGIN.STYLES, (d) => getStylesMap(d).set('x', {}))

    const roots = Array.from(doc.share.keys()).sort()
    for (const key of roots) {
      const ok = key === PROSE_FRAGMENT_KEY || key.startsWith('te:')
      expect(ok, `unexpected root key: ${key}`).toBe(true)
    }
    expect(roots).toContain(PROSE_FRAGMENT_KEY)
    expect(roots).toContain(TE_ROOTS.SECTIONS)
  })

  it('txn tags updates with the given origin', () => {
    const doc = seededDoc()
    const origins = []
    doc.on('afterTransaction', (txnObj) => origins.push(txnObj.origin))
    txn(doc, TE_ORIGIN.STYLES, (d) => getStylesMap(d).set('Body', {}))
    expect(origins).toContain(TE_ORIGIN.STYLES)
  })

  it('readPageSetup falls back to defaults for unwritten keys', () => {
    const doc = seededDoc()
    txn(doc, TE_ORIGIN.PAGE, (d) => getPageMap(d).set('orientation', 'landscape'))
    const setup = readPageSetup(doc)
    expect(setup.orientation).toBe('landscape')        // overridden
    expect(setup.size).toBe(DEFAULT_PAGE_SETUP.size)   // default
    expect(setup.margins).toEqual(DEFAULT_PAGE_SETUP.margins)
  })

  it('back-compat: a pre-change snapshot (fragment only) decodes unchanged under the new schema', () => {
    // Old client: doc with ONLY the prose fragment, no te:* roots.
    const oldDoc = seededDoc()
    const snapshot = Y.encodeStateAsUpdate(oldDoc)
    const oldJson = getProseFragment(oldDoc).toJSON()

    // New client opens the old snapshot, then touches new side maps.
    const newDoc = new Y.Doc()
    Y.applyUpdate(newDoc, snapshot)
    expect(getProseFragment(newDoc).toJSON()).toBe(oldJson)
    expect(hasWordGradeState(newDoc)).toBe(false)

    txn(newDoc, TE_ORIGIN.PAGE, (d) => getPageMap(d).set('size', 'letter'))
    expect(getProseFragment(newDoc).toJSON()).toBe(oldJson) // still unchanged
    expect(hasWordGradeState(newDoc)).toBe(true)

    // ...and an old client can still apply the new client's update without error.
    const forward = Y.encodeStateAsUpdate(newDoc)
    const oldDoc2 = new Y.Doc()
    expect(() => Y.applyUpdate(oldDoc2, forward)).not.toThrow()
    expect(getProseFragment(oldDoc2).toJSON()).toBe(oldJson)
  })
})
