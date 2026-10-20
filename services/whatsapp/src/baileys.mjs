import makeWASocket, { DisconnectReason, generateWAMessage, makeCacheableSignalKeyStore, proto } from '@whiskeysockets/baileys';
import pino from 'pino';
import QRCode from 'qrcode';

export class BaileysAdapter {
  constructor(store, { socketFactory = makeWASocket, schedule = setTimeout, cancel = clearTimeout, now = Date.now, logoutTimeoutMs = 10000, renderQr = qr => QRCode.toDataURL(qr, { width: 320 }) } = {}) {
    this.store = store;
    Object.assign(this, { socketFactory, schedule, cancel, now, renderQr, logoutTimeoutMs });
    this.socket = null;
    this.state = 'disconnected';
    this.qr = null;
    this.timer = null;
    this.generation = 0;
    this.retries = 0;
    this.qrCount = 0;
    this.qrExpires = 0;
    this.lastDisconnect = store.connectionDiagnostic();
    this.logger = pino({ level: 'silent' });
  }

  ready() { return this.state === 'connected'; }
  async status() {
    const qr = this.qr;
    const generation = this.generation;
    const rendered = qr && this.qrExpires > this.now() ? await this.renderQr(qr) : null;
    const current = rendered && qr === this.qr && generation === this.generation && this.qrExpires > this.now();
    return {
      state: this.state,
      qr: current ? rendered : null,
      qrValidForMs: current ? this.qrExpires - this.now() : 0,
      lastDisconnect: this.lastDisconnect,
      account: this.ready() ? (this.socket?.user?.id?.split(':')[0] || '').split('@')[0] : null,
    };
  }

  async connect({ resume = false } = {}) {
    if (this.socket || this.timer || this.state === 'disconnecting') return;
    if (!resume) {
      this.retries = 0;
      if (['needs_pairing', 'qr_expired'].includes(this.state)) this.store.clearAuth();
    }
    this.store.setEnabled(true);
    this.state = 'connecting';
    const generation = ++this.generation;
    this.qrCount = 0;
    const auth = this.store.auth();
    auth.save();
    const socket = this.socketFactory({
      logger: this.logger,
      auth: { creds: auth.state.creds, keys: makeCacheableSignalKeyStore(auth.state.keys, this.logger) },
      printQRInTerminal: false, markOnlineOnConnect: false,
      syncFullHistory: false, shouldSyncHistoryMessage: () => false,
      connectTimeoutMs: 60000, defaultQueryTimeoutMs: 60000,
      getMessage: async key => this.store.message(key.id),
    });
    this.socket = socket;
    socket.ev.on('creds.update', update => {
      if (generation !== this.generation) return;
      Object.assign(auth.state.creds, update);
      auth.save();
    });
    socket.ev.on('connection.update', update => {
      if (generation !== this.generation) return;
      if (update.qr) {
        this.qr = update.qr;
        // Baileys 6 uses 60s for the first QR and 20s for subsequent references.
        this.qrExpires = this.now() + (this.qrCount++ === 0 ? 60000 : 20000);
        this.state = 'qr';
      }
      if (update.isNewLogin) {
        auth.save();
        this.state = 'pairing'; this.qr = null; this.qrExpires = 0;
      }
      if (update.connection === 'open') {
        this.state = 'connected'; this.qr = null; this.qrExpires = 0; this.retries = 0;
      }
      if (update.connection === 'close') {
        auth.save();
        this.socket = null; this.qr = null; this.qrExpires = 0;
        ++this.generation;
        const code = update.lastDisconnect?.error?.output?.statusCode;
        const paired = !!auth.state.creds.me?.id;
        this.lastDisconnect = { code: Number.isInteger(code) ? code : null, at: new Date(this.now()).toISOString(), paired };
        this.store.connectionDiagnostic(this.lastDisconnect);
        if ([DisconnectReason.loggedOut, DisconnectReason.badSession, DisconnectReason.connectionReplaced, DisconnectReason.multideviceMismatch, DisconnectReason.forbidden].includes(code)) {
          this.state = 'needs_pairing'; this.store.setEnabled(false);
          return;
        }
        if (code === DisconnectReason.timedOut && !paired) {
          this.state = 'qr_expired'; this.store.setEnabled(false);
          return;
        }
        // WhatsApp requires a new socket immediately after successful QR pairing.
        // This is a protocol step, not a failed connection to back off for a minute.
        const restart = code === DisconnectReason.restartRequired;
        this.state = restart ? 'pairing' : 'reconnecting';
        this.timer = this.schedule(() => {
          this.timer = null;
          this.connect({ resume: true }).catch(() => { this.state = 'unavailable'; });
        }, restart ? 0 : Math.min(60000, 1000 * 2 ** Math.min(this.retries++, 6)));
      }
    });
    socket.ev.on('messages.update', updates => {
      for (const { key, update } of updates) {
        if (!key.fromMe || update.status == null) continue;
        const statuses = proto.WebMessageInfo.Status;
        if (update.status >= statuses.READ) this.store.receipt(key.id, 'read');
        else if (update.status >= statuses.DELIVERY_ACK) this.store.receipt(key.id, 'delivered');
        else if (update.status >= statuses.SERVER_ACK) this.store.receipt(key.id, 'sent');
      }
    });
    socket.ev.on('message-receipt.update', receipts => {
      for (const { key, receipt } of receipts) {
        if (key.fromMe && receipt.readTimestamp) this.store.receipt(key.id, 'read');
        else if (key.fromMe && receipt.receiptTimestamp) this.store.receipt(key.id, 'delivered');
      }
    });
  }

  async disconnect() {
    const wasConnected = this.ready();
    this.store.setEnabled(false);
    this.cancel(this.timer); this.timer = null;
    ++this.generation;
    const socket = this.socket;
    this.socket = null; this.qr = null; this.qrExpires = 0; this.state = 'disconnecting';
    if (socket) {
      if (wasConnected) {
        let timeout;
        try {
          await Promise.race([
            socket.logout(),
            new Promise((_, reject) => { timeout = setTimeout(() => reject(new Error('Logout timeout')), this.logoutTimeoutMs); }),
          ]);
        } catch {
          socket.end(new Error('Disconnected by administrator'));
        } finally {
          clearTimeout(timeout);
        }
      } else {
        socket.end(new Error('Pairing cancelled by administrator'));
      }
    }
    this.store.clearAuth();
    this.state = 'disconnected';
  }

  async resolve(number) {
    const result = await this.socket.onWhatsApp(number + '@s.whatsapp.net');
    return result?.find(value => value.exists)?.jid;
  }
  async prepare(jid, text) {
    return generateWAMessage(jid, { text }, { userJid: this.socket.user.id, logger: this.logger, linkPreview: null });
  }
  async relay(jid, message) {
    return this.socket.relayMessage(jid, message.message, { messageId: message.key.id });
  }

  stop() {
    ++this.generation;
    this.cancel(this.timer); this.timer = null;
    this.socket?.end(new Error('Service stopping'));
  }
}
