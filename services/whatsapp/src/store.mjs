import { DatabaseSync } from 'node:sqlite';
import { BufferJSON, initAuthCreds, proto } from '@whiskeysockets/baileys';

export class Store {
  constructor(path) {
    this.db = new DatabaseSync(path);
    this.db.exec(`PRAGMA journal_mode=WAL; PRAGMA synchronous=FULL;
      CREATE TABLE IF NOT EXISTS auth (type TEXT, id TEXT, value TEXT, PRIMARY KEY(type,id));
      CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT);
      CREATE TABLE IF NOT EXISTS messages (key TEXT PRIMARY KEY, fingerprint TEXT NOT NULL,
        status TEXT NOT NULL, message_id TEXT UNIQUE, body TEXT, updated_at INTEGER NOT NULL);`);
    // A crash while sending is ambiguous, never an invitation to send again.
    this.db.prepare("UPDATE messages SET status='uncertain' WHERE status='sending'").run();
  }

  read(type, id) {
    const row = this.db.prepare('SELECT value FROM auth WHERE type=? AND id=?').get(type, id);
    return row ? JSON.parse(row.value, BufferJSON.reviver) : null;
  }

  write(type, id, value) {
    if (value == null) this.db.prepare('DELETE FROM auth WHERE type=? AND id=?').run(type, id);
    else this.db.prepare('INSERT OR REPLACE INTO auth VALUES (?,?,?)').run(type, id, JSON.stringify(value, BufferJSON.replacer));
  }

  auth() {
    const creds = this.read('creds', 'main') || initAuthCreds();
    const keys = {
      get: async (type, ids) => Object.fromEntries(ids.map(id => {
        let value = this.read(type, id);
        if (type === 'app-state-sync-key' && value) value = proto.Message.AppStateSyncKeyData.fromObject(value);
        return [id, value];
      })),
      set: async data => {
        this.db.exec('BEGIN IMMEDIATE');
        try {
          for (const [type, values] of Object.entries(data)) {
            for (const [id, value] of Object.entries(values)) this.write(type, id, value);
          }
          this.db.exec('COMMIT');
        } catch (error) {
          this.db.exec('ROLLBACK');
          throw error;
        }
      }
    };
    return { state: { creds, keys }, save: () => this.write('creds', 'main', creds) };
  }

  enabled() {
    return this.db.prepare("SELECT value FROM settings WHERE key='enabled'").get()?.value === '1';
  }

  setEnabled(enabled) {
    this.db.prepare("INSERT OR REPLACE INTO settings VALUES ('enabled',?)").run(enabled ? '1' : '0');
  }

  clearAuth() { this.db.exec('DELETE FROM auth'); }
  get(key) { return this.db.prepare('SELECT key, fingerprint, status, message_id FROM messages WHERE key=?').get(key); }

  begin(key, fingerprint, id, body) {
    this.db.prepare("INSERT INTO messages VALUES (?,?,'sending',?,?,?)").run(key, fingerprint, id, JSON.stringify(body, BufferJSON.replacer), Date.now());
  }

  update(key, status) {
    this.db.prepare('UPDATE messages SET status=?, updated_at=? WHERE key=?').run(status, Date.now(), key);
  }

  receipt(id, status) {
    // Never downgrade a read/delivery confirmation on out-of-order events.
    const rank = { sending: 0, uncertain: 0, sent: 1, delivered: 2, read: 3 };
    const row = this.db.prepare('SELECT key,status FROM messages WHERE message_id=?').get(id);
    if (row && rank[status] > rank[row.status]) this.update(row.key, status);
  }

  message(id) {
    const row = this.db.prepare('SELECT body FROM messages WHERE message_id=?').get(id);
    return row ? JSON.parse(row.body, BufferJSON.reviver) : undefined;
  }

  close() { this.db.close(); }
}
