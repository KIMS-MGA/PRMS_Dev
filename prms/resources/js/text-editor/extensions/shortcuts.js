// Word-style keyboard shortcuts that map cleanly to editor commands. Find/replace
// (Ctrl+F/H) and link (Ctrl+K) are handled at the instance level (they need UI),
// so they are NOT here. Each handler returns true to prevent the browser default
// (e.g. Ctrl+R reload, Ctrl+L address bar) while the editor is focused.

import { Extension } from '@tiptap/core'

const run = (fn) => () => {
  fn()
  return true
}

export const WordShortcuts = Extension.create({
  name: 'wordShortcuts',
  addKeyboardShortcuts() {
    const e = this.editor
    return {
      'Mod-l': run(() => e.commands.setTextAlign('left')),
      'Mod-e': run(() => e.commands.setTextAlign('center')),
      'Mod-r': run(() => e.commands.setTextAlign('right')),
      'Mod-j': run(() => e.commands.setTextAlign('justify')),
      'Mod-1': run(() => e.commands.setLineSpacing('1')),
      'Mod-2': run(() => e.commands.setLineSpacing('2')),
      'Mod-5': run(() => e.commands.setLineSpacing('1.5')),
      'Mod-Shift-l': run(() => e.commands.toggleBulletList()),
    }
  },
})
