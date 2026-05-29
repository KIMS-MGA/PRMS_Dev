// Word-grade editor extensions — ADDITIVE ONLY.
//
// Everything exported here is appended to the editor's `extensions` array
// (after TextAlign, before the Collaboration push). These are all
// "global-attribute" extensions: they add new attributes (with safe defaults)
// to node/mark types that ALREADY exist in the schema. Per plan §0 this is the
// lowest-regression-risk category — older persisted docs decode fine because
// missing attrs fall back to their defaults, and no existing node/mark type is
// renamed, removed, or restructured.
//
// New stand-alone nodes/marks (Link, bookmark, sectionBreak, footnoteRef,
// suggestion marks, …) are introduced in their own modules in later build steps
// and composed in here so text-editor.js keeps a single `...wordGradeExtensions()`
// spread.

import { Extension } from '@tiptap/core'
import Link from '@tiptap/extension-link'
import { Bookmark, CrossReference, ClearFormatting } from './nodes.js'
import { WordShortcuts } from './shortcuts.js'
import { SectionBreak, ColumnBreak } from './sections.js'
import { FootnoteRef } from './footnote.js'
import { SuggestionInsert, SuggestionDelete, TrackChanges } from './track-changes.js'

// ---------------------------------------------------------------------------
// Attribute factories (each hardcodes its own attr key, the TipTap convention)
// ---------------------------------------------------------------------------

/** numeric value rendered as an inline CSS length in points, e.g. margin-top: 12pt */
const cssPt = (attrKey, cssProp) => ({
  default: null,
  parseHTML: (el) => {
    const raw = el.style?.getPropertyValue?.(cssProp)
    return raw ? parseFloat(raw) : null
  },
  renderHTML: (attrs) =>
    attrs[attrKey] != null && attrs[attrKey] !== ''
      ? { style: `${cssProp}: ${attrs[attrKey]}pt` }
      : {},
})

/** raw CSS string value (e.g. a color) rendered as an inline style */
const cssRaw = (attrKey, cssProp) => ({
  default: null,
  parseHTML: (el) => el.style?.getPropertyValue?.(cssProp) || null,
  renderHTML: (attrs) =>
    attrs[attrKey] ? { style: `${cssProp}: ${attrs[attrKey]}` } : {},
})

/** boolean rendered as a data-* flag (print-only attrs; no on-screen effect) */
const dataFlag = (attrKey, dataName) => ({
  default: false,
  parseHTML: (el) => el.getAttribute(`data-${dataName}`) === 'true',
  renderHTML: (attrs) => (attrs[attrKey] ? { [`data-${dataName}`]: 'true' } : {}),
})

/** sanitize a user style name into a CSS-class-safe token */
export const cssSafe = (id) =>
  String(id)
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')

/** named-style attribute -> data-<kind>style + class te-<kind>style-<safe> */
const styleRef = (attrKey, dataName, classPrefix) => ({
  default: null,
  parseHTML: (el) => el.getAttribute(`data-${dataName}`) || null,
  renderHTML: (attrs) =>
    attrs[attrKey]
      ? { [`data-${dataName}`]: attrs[attrKey], class: `${classPrefix}-${cssSafe(attrs[attrKey])}` }
      : {},
})

// ---------------------------------------------------------------------------
// Extensions
// ---------------------------------------------------------------------------

// Word paragraph formatting + flow control on paragraph & heading nodes.
// Visual attrs (spacing/indents) render as inline CSS; flow-control flags
// (consumed only by the print pipeline) render as data-* and are inert on screen.
export const ParagraphFormat = Extension.create({
  name: 'paragraphFormat',
  addOptions() {
    return { types: ['paragraph', 'heading'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          spaceBefore: cssPt('spaceBefore', 'margin-top'),
          spaceAfter: cssPt('spaceAfter', 'margin-bottom'),
          firstLineIndent: cssPt('firstLineIndent', 'text-indent'),
          indentLeft: cssPt('indentLeft', 'margin-left'),
          indentRight: cssPt('indentRight', 'margin-right'),
          keepWithNext: dataFlag('keepWithNext', 'keep-with-next'),
          keepLines: dataFlag('keepLines', 'keep-lines'),
          pageBreakBefore: dataFlag('pageBreakBefore', 'page-break-before'),
          widowControl: dataFlag('widowControl', 'widow-control'),
        },
      },
    ]
  },
  addCommands() {
    return {
      setParagraphFormat:
        (attrs) =>
        ({ commands, state }) => {
          const types = this.options.types.filter((t) => state.schema.nodes[t])
          return types.some((t) => commands.updateAttributes(t, attrs))
        },
    }
  },
})

// Named paragraph style reference (the style definitions live in te:styles;
// the StylesManager in a later step injects the matching CSS classes).
export const NamedParagraphStyle = Extension.create({
  name: 'namedParagraphStyle',
  addOptions() {
    return { types: ['paragraph', 'heading'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          paragraphStyleId: styleRef('paragraphStyleId', 'pstyle', 'te-pstyle'),
        },
      },
    ]
  },
  addCommands() {
    return {
      setParagraphStyle:
        (id) =>
        ({ chain, state }) => {
          let c = chain()
          for (const t of this.options.types) {
            if (state.schema.nodes[t]) c = c.updateAttributes(t, { paragraphStyleId: id })
          }
          return c.run()
        },
    }
  },
})

// Named character style reference, carried on the existing textStyle mark.
export const NamedCharStyle = Extension.create({
  name: 'namedCharStyle',
  addOptions() {
    return { types: ['textStyle'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          charStyleId: styleRef('charStyleId', 'cstyle', 'te-cstyle'),
        },
      },
    ]
  },
})

// Multilevel / numbered-list format. Adds list-style-type to the list nodes
// (decimal, lower-alpha, upper-alpha, lower-roman, upper-roman, disc, circle,
// square). Existing toggle commands and the `start` attr are untouched.
export const ListFormat = Extension.create({
  name: 'listFormat',
  addOptions() {
    return { types: ['bulletList', 'orderedList'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          listStyleType: cssRaw('listStyleType', 'list-style-type'),
        },
      },
    ]
  },
})

// Table cell formatting on the existing tableCell / tableHeader nodes.
export const TableCellFormat = Extension.create({
  name: 'tableCellFormat',
  addOptions() {
    return { types: ['tableCell', 'tableHeader'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          cellBackground: cssRaw('cellBackground', 'background-color'),
          cellBorderColor: cssRaw('cellBorderColor', 'border-color'),
          cellVerticalAlign: cssRaw('cellVerticalAlign', 'vertical-align'),
          cellAlign: cssRaw('cellAlign', 'text-align'),
        },
      },
    ]
  },
})

// Repeat-header-row flag on the table node (consumed by the print pipeline;
// `thead { display: table-header-group }` makes the browser repeat it per page).
export const TableFormat = Extension.create({
  name: 'tableFormat',
  addOptions() {
    return { types: ['table'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          repeatHeader: dataFlag('repeatHeader', 'repeat-header'),
        },
      },
    ]
  },
})

// Word-style image text-wrap mode on the existing image node. The on-screen
// NodeView (ResizableImage) handles `align`; this only persists the richer wrap
// intent as a data-* attr for the print pipeline. Values: inline, square, tight,
// top-bottom, behind, in-front.
export const ImageWrap = Extension.create({
  name: 'imageWrap',
  addOptions() {
    return { types: ['image'] }
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          wrapMode: {
            default: null,
            parseHTML: (el) => el.getAttribute('data-wrap') || null,
            renderHTML: (attrs) => (attrs.wrapMode ? { 'data-wrap': attrs.wrapMode } : {}),
          },
        },
      },
    ]
  },
})

/**
 * Returns the additive Word-grade extension list to spread into the editor's
 * `extensions` array. `opts` is reserved for later steps (e.g. passing the ydoc
 * to feature extensions); accepted now to keep the call site stable.
 */
export function wordGradeExtensions(opts = {}) {
  return [
    // Global-attribute extensions (additive attrs on existing types)
    ParagraphFormat,
    NamedParagraphStyle,
    NamedCharStyle,
    ListFormat,
    TableCellFormat,
    TableFormat,
    ImageWrap,
    // New marks/nodes/commands (additive schema members)
    Link.configure({
      openOnClick: false,
      autolink: false,
      HTMLAttributes: { class: 'te-link', rel: 'noopener nofollow', target: '_blank' },
    }),
    Bookmark,
    CrossReference,
    ClearFormatting,
    WordShortcuts,
    SectionBreak,
    ColumnBreak,
    FootnoteRef,
    SuggestionInsert,
    SuggestionDelete,
    TrackChanges.configure({
      isActive: opts.isSuggesting || (() => false),
      getUser: opts.getUser || (() => ({ name: '', color: '' })),
    }),
  ]
}
