import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // Split heavy editor vendor libs into their own cacheable chunks so
                // the main app bundle stays lean. (Paged.js / docx / find-replace are
                // already split automatically via dynamic import().)
                manualChunks(id) {
                    if (!id.includes('node_modules')) return
                    if (id.includes('yjs') || id.includes('y-prosemirror') || id.includes('y-protocols')) return 'yjs'
                    if (id.includes('@tiptap') || id.includes('prosemirror')) return 'tiptap'
                    if (id.includes('@hocuspocus')) return 'hocuspocus'
                },
            },
        },
    },
});
