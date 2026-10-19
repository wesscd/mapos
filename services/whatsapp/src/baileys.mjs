import makeWASocket, { DisconnectReason, generateWAMessage, makeCacheableSignalKeyStore, proto } from '@whiskeysockets/baileys';
import pino from 'pino';
import QRCode from 'qrcode';

export class BaileysAdapter {
  constructor(store) {
    this.store = store;
    this.socket = null;
    this.state = 'disconnected';
    this.qr = null;
    this.timer = null;
    this.generation = 0;
    this.retries = 0;
    this.logger = pino({ level: 'silent' });
  }

  ready() { return this.state === 'connected'; }
  async status() {
    return {
      state: this.state,
      qr: this.qr && this.qrExpires > Date.now() ? await QRCode.toDataURL(this.qr, { width: 280 }) : null,
      account: this.ready() ? (this.socket?.user?.id?.split(':')[0] || '').split('@')[0] : null,
    };
  }

  async connect() {
    if (this.socket || this.timer) return;
    if (this.state === 'needs_pairing') this.store.clearAuth();
    this.store.setEnabled(true);
    this.state = 'connecting';
    const generation = ++this.generation;
    const auth = this.store.auth();
    auth.save();
    const socket = makeWASocket({
      logger: this.logger,
      auth: { creds: auth.state.creds, keys: makeCacheableSignalKeyStore(auth.state.keys, this.logger) },
      printQRInTerminal: false, markOnlineOnConnect: false,
      syncFullHistory: false, shouldSyncHistoryMessage: () => false,
      connectTimeoutMs: 20000, defaultQueryTimeoutMs: 20000,
      getMessage: async key => this.store.message(key.id),
    });
    this.socket = socket;
    socket.ev.on('creds.update', () => { if (generation === this.generation) auth.save(); });
    socket.ev.on('connection.update', update => {
      if (generation !== this.generation) return;
      if (update.qr) {
        this.qr = update.qr;
        this.qrExpires = Date.now() + 45000;
        this.state = 'qr';
      }
      if (update.connection === 'open') {
        this.state = 'connected'; this.qr = null; this.retries = 0;
      }
      if (update.connection === 'close') {
        this.socket = null; this.qr = null;
        const code = update.lastDisconnect?.error?.output?.statusCode;
        if ([DisconnectReason.loggedOut, DisconnectReason.badSession, DisconnectReason.connectionReplaced, DisconnectReason.multideviceMismatch, DisconnectReason.forbidden].includes(code)) {
          this.state = 'needs_pairing'; this.store.setEnabled(false);
          return;
        }
        this.state = 'reconnecting';
        this.timer = setTimeout(() => {
          this.timer = null;
          this.connect().catch(() => { this.state = 'unavailable'; });
        }, Math.min(60000, 1000 * 2 ** Math.min(this.retries++, 6)));
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
    this.store.setEnabled(false);
    clearTimeout(this.timer); this.timer = null;
    ++this.generation;
    const socket = this.socket;
    this.socket = null; this.qr = null; this.state = 'disconnected';
    if (socket) {
      try { await socket.logout(); } catch { socket.end(new Error('Disconnected by administrator')); }
    }
    this.store.clearAuth();
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
    clearTimeout(this.timer);
    this.socket?.end(new Error('Service stopping'));
  }
}
