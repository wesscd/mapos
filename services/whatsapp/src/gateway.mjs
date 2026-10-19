import { createHash, timingSafeEqual } from 'node:crypto';

export class Gateway {
  constructor({ store, adapter, token, interval = 10000 }) {
    if (!token || token.length < 32) throw new Error('A private token of at least 32 characters is required.');
    Object.assign(this, { store, adapter, token, interval });
    this.busy = false;
    this.lastSend = 0;
  }

  authorized(header) {
    const supplied = Buffer.from(header || '');
    const expected = Buffer.from(`Bearer ${this.token}`);
    return supplied.length === expected.length && timingSafeEqual(supplied, expected);
  }

  async route(method, path, data = {}) {
    if (method === 'GET' && path === '/status') return [200, await this.adapter.status()];
    if (method === 'POST' && path === '/connect') {
      await this.adapter.connect();
      return [200, await this.adapter.status()];
    }
    if (method === 'POST' && path === '/disconnect') {
      if (this.busy) return [409, { error: 'Aguarde o envio em andamento.' }];
      await this.adapter.disconnect();
      return [200, { state: 'disconnected' }];
    }
    if (method === 'GET' && /^\/messages\/[a-f0-9]{48}$/.test(path)) {
      const row = this.store.get(path.split('/')[2]);
      return row ? [200, { status: row.status }] : [404, { error: 'Mensagem não localizada.' }];
    }
    if (method !== 'POST' || path !== '/messages') return [404, { error: 'Recurso não encontrado.' }];
    if (!/^[a-f0-9]{48}$/.test(data.key || '') || !/^[1-9][0-9]{7,14}$/.test(data.to || '')
      || typeof data.text !== 'string' || !data.text.trim() || data.text.length > 6000) {
      return [422, { error: 'Mensagem inválida.' }];
    }
    const fingerprint = createHash('sha256').update(JSON.stringify([data.to, data.text])).digest('hex');
    const previous = this.store.get(data.key);
    if (previous) {
      if (previous.fingerprint !== fingerprint) return [409, { error: 'Chave já utilizada para outra mensagem.' }];
      return [200, { status: previous.status }];
    }
    if (this.busy || Date.now() - this.lastSend < this.interval) return [429, { error: 'Aguarde o intervalo de envio.' }];
    if (!this.adapter.ready()) return [503, { error: 'WhatsApp desconectado.' }];
    this.busy = true;
    try {
      const jid = await this.adapter.resolve(data.to);
      if (!jid) return [422, { error: 'Número não disponível no WhatsApp.' }];
      // Persist the message ID before handing it to the external network.
      const message = await this.adapter.prepare(jid, data.text);
      this.store.begin(data.key, fingerprint, message.key.id, message.message);
      this.lastSend = Date.now();
      try {
        await this.adapter.relay(jid, message);
        this.store.receipt(message.key.id, 'sent');
      } catch {
        // A receipt can race the network error, preserve the stronger evidence.
        if (this.store.get(data.key).status === 'sending') this.store.update(data.key, 'uncertain');
      }
      return [200, { status: this.store.get(data.key).status }];
    } catch {
      // Lookup/prepare failed, relay was never started: a retry is safe.
      return [503, { error: 'Não foi possível preparar o envio.' }];
    } finally {
      this.busy = false;
    }
  }
}
