import { defineConfig } from 'vitest/config'

// JS unit/integration tests for the Word-grade editor enhancement.
// Pure logic + Yjs-in-node tests use the default 'node' environment; the few
// DOM-touching suites opt in per-file via:  // @vitest-environment jsdom
export default defineConfig({
  test: {
    include: ['resources/js/**/*.test.js'],
    environment: 'node',
  },
})
