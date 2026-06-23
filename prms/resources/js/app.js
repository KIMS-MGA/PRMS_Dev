import './bootstrap';
import { mountEditors } from './text-editor';
import Sortable from 'sortablejs';
import mammoth from 'mammoth/mammoth.browser.js';

// Expose as globals so blade inline scripts can access them
window.Sortable = Sortable;
window.mammoth  = mammoth;

// app.js is a type="module" script (deferred). Livewire.js is a synchronous
// script injected at end of <body> — it runs and fires all init events BEFORE
// this module executes. So we call mountEditors() directly here (no listener
// needed) and register hooks directly (Livewire is already ready).
mountEditors();

// Before every Livewire request, push the current editor HTML directly into
// the commit payload. This prevents morphdom resets from leaving a stale
// server value in the hidden input between the last onUpdate and the save.
//
// The isSynced guard blocks the push for existing records whose editor hasn't
// yet connected to Hocuspocus — without it a blank editor would overwrite
// saved content on the very first network round-trip.
Livewire.hook('commit', ({ component, commit }) => {
    document.querySelectorAll('.text-editor-mount[data-te-mounted]').forEach(container => {
        const instance = container._teInstance
        if (!instance?.editor || !instance.hiddenInput) return
        if (container.dataset.readonly === '1') return
        const isNew = !instance.recordId || instance.recordId === 'new'
        if (!isNew && !instance.isSynced) return
        const html = instance.editor.getHTML()
        instance.hiddenInput.value = html
        const wireModel = instance.hiddenInput.getAttribute('wire:model')
        if (wireModel && commit.updates) commit.updates[wireModel] = html
    })
})

Livewire.hook('morph.updated', () => setTimeout(mountEditors, 0));

document.addEventListener('livewire:navigated', mountEditors);
