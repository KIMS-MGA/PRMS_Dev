import { Server } from '@hocuspocus/server'
import { Database } from '@hocuspocus/extension-database'
import mysql from 'mysql2/promise'
import fetch from 'node-fetch'
import { readFileSync } from 'fs'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

// Load the Laravel .env from the project root (one level up from hocuspocus/).
// This ensures DB_USERNAME, APP_URL, etc. are available even when the process
// is started without explicit env-var injection (e.g. `node server.js` locally).
const __dir = dirname(fileURLToPath(import.meta.url))
const envPath = resolve(__dir, '..', '.env')
try {
  const envContent = readFileSync(envPath, 'utf8')
  for (const line of envContent.split('\n')) {
    const trimmed = line.trim()
    if (!trimmed || trimmed.startsWith('#')) continue
    const eqIdx = trimmed.indexOf('=')
    if (eqIdx < 1) continue
    const key = trimmed.slice(0, eqIdx).trim()
    let val  = trimmed.slice(eqIdx + 1).trim()
    // Strip surrounding quotes (Laravel .env allows "value" or 'value')
    if ((val.startsWith('"') && val.endsWith('"')) ||
        (val.startsWith("'") && val.endsWith("'"))) {
      val = val.slice(1, -1)
    }
    // Only set if not already provided by the shell environment
    if (!(key in process.env)) process.env[key] = val
  }
  console.log('[Hocuspocus] Loaded .env from', envPath)
} catch (e) {
  console.warn('[Hocuspocus] Could not load .env file:', e.message)
}

const db = await mysql.createPool({
  host:     process.env.DB_HOST     ?? '127.0.0.1',
  database: process.env.DB_DATABASE ?? 'prms_dev',
  // Laravel uses DB_USERNAME; support both for compatibility
  user:     process.env.DB_USERNAME ?? process.env.DB_USER ?? 'root',
  password: process.env.DB_PASSWORD ?? '',
  port:     parseInt(process.env.DB_PORT ?? '3306', 10),
  waitForConnections: true,
  connectionLimit: 10,
})

// Must match APP_URL in .env — the Node server calls Laravel's validate-token endpoint
const APP_URL = process.env.APP_URL || 'http://localhost'

// Parse document name: "record-{id}-field-{slug}"
function parseDocName(name) {
  const match = name.match(/^record-(\d+)-field-(.+)$/)
  if (!match) return null
  return { recordId: match[1], fieldSlug: match[2] }
}

const TABLE_PREFIX = process.env.DB_PREFIX ?? ''

const server = Server.configure({
  port: 1234,

  async onAuthenticate({ token, documentName }) {
    if (!token) {
      console.error('[onAuthenticate] No token provided for doc:', documentName)
      throw new Error('No token provided')
    }

    const url = `${APP_URL}/api/text-editor/validate-token`
    console.log(`[onAuthenticate] doc=${documentName} token=${token.slice(0,8)}... url=${url}`)

    let res
    try {
      res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ document: documentName }),
      })
    } catch (networkErr) {
      console.error('[onAuthenticate] Network error reaching Laravel:', networkErr.message)
      throw new Error('Authentication failed')
    }

    const bodyText = await res.text()
    console.log(`[onAuthenticate] HTTP ${res.status} response: ${bodyText.slice(0, 300)}`)

    if (!res.ok) {
      console.error(`[onAuthenticate] Laravel rejected token — HTTP ${res.status}`)
      throw new Error('Authentication failed')
    }

    try {
      const data = JSON.parse(bodyText)
      console.log(`[onAuthenticate] OK user=${data.user?.name}`)
      return { user: data.user }
    } catch (parseErr) {
      console.error('[onAuthenticate] Could not parse JSON response:', bodyText.slice(0, 200))
      throw new Error('Authentication failed')
    }
  },

  extensions: [
    new Database({
      async fetch({ documentName }) {
        const parsed = parseDocName(documentName)
        if (!parsed) return null

        const [rows] = await db.execute(
          `SELECT binary_state FROM ${TABLE_PREFIX}text_editor_documents WHERE record_id = ? AND field_slug = ? LIMIT 1`,
          [parsed.recordId, parsed.fieldSlug]
        )

        if (!rows.length || !rows[0].binary_state) return null

        // Convert base64 back to Uint8Array
        return Buffer.from(rows[0].binary_state, 'base64')
      },

      async store({ documentName, state }) {
        const parsed = parseDocName(documentName)
        if (!parsed) return

        const base64State = Buffer.from(state).toString('base64')

        try {
          await db.execute(
            `INSERT INTO ${TABLE_PREFIX}text_editor_documents (record_id, field_slug, binary_state, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE binary_state = VALUES(binary_state), updated_at = NOW()`,
            [parsed.recordId, parsed.fieldSlug, base64State]
          )
        } catch (err) {
          // FK violation means the record was deleted — skip silently
          if (err.code === 'ER_NO_REFERENCED_ROW_2') return
          console.error('[store] Unexpected DB error:', err.message)
        }
      },
    }),
  ],

  async onChange({ documentName, context, document }) {
    // History logging is handled client-side via Livewire for now
    // Future: send diffs to Laravel API
  },
})

process.on('unhandledRejection', (reason) => {
  console.error('[unhandledRejection]', reason)
})

server.listen()
console.log('Hocuspocus server running on port 1234')
