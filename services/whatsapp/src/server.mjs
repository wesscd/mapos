import { createServer } from 'node:http';
import { readFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { Store } from './store.mjs';
import { Gateway } from './gateway.mjs';
import { BaileysAdapter } from './baileys.mjs';

process.umask(0o077);
const directory = process.env.DATA_DIR || '/data';
mkdirSync(directory, { recursive: true, mode: 0o700 });
const store = new Store(join(directory, 'whatsapp.sqlite'));
const adapter = new BaileysAdapter(store);
const gateway = new Gateway({ store, adapter, token: readFileSync(process.env.TOKEN_FILE || '/run/secrets/whatsapp_token', 'utf8').trim() });
const server = createServer(async (req, res) => {
  res.setHeader('Cache-Control', 'no-store');
  res.setHeader('Content-Type', 'application/json');
  if (req.method === 'GET' && req.url === '/health') {
    res.end('{"ok":true}'); return;
  }
  if (!gateway.authorized(req.headers.authorization)) {
    res.writeHead(401); res.end('{"error":"Unauthorized"}'); return;
  }
  let size = 0;
  let chunks = '';
  try {
    for await (const chunk of req) {
      size += chunk.length;
      if (size > 32000) { res.writeHead(413); res.end('{}'); return; }
      chunks += chunk.toString();
    }
    const data = chunks ? JSON.parse(chunks) : {};
    const [code, body] = await gateway.route(req.method, req.url, data);
    res.writeHead(code); res.end(JSON.stringify(body));
  } catch {
    res.writeHead(500); res.end('{"error":"Serviço indisponível."}');
  }
});
server.requestTimeout = 30000;
server.headersTimeout = 10000;
server.listen(3000, '0.0.0.0');
if (store.enabled()) adapter.connect().catch(() => { adapter.state = 'unavailable'; });
for (const signal of ['SIGTERM', 'SIGINT']) {
  process.on(signal, () => {
    adapter.stop();
    server.close(() => { store.close(); process.exit(0); });
    setTimeout(() => process.exit(0), 5000).unref();
  });
}
