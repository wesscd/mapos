import { test } from 'node:test';
import assert from 'node:assert/strict';
import { EventEmitter } from 'node:events';
import { Store } from '../src/store.mjs';
import { BaileysAdapter } from '../src/baileys.mjs';
import { DisconnectReason } from '@whiskeysockets/baileys';

function setup(t, extra = {}) {
  const store = new Store(':memory:');
  const sockets = [];
  const timers = [];
  const cancelled = [];
  let time = 100000;
  const adapter = new BaileysAdapter(store, {
    socketFactory: config => {
      const socket = { config, ev: new EventEmitter(), end: () => {}, logout: async () => {} };
      sockets.push(socket);
      return socket;
    },
    now: () => time,
    schedule: (callback, delay) => { const timer = { callback, delay }; timers.push(timer); return timer; },
    cancel: timer => { if (timer) cancelled.push(timer); },
    renderQr: async () => 'data:image/png;base64,fake',
    ...extra,
  });
  const close = code => sockets.at(-1).ev.emit('connection.update', { connection: 'close', lastDisconnect: { error: { output: { statusCode: code } } } });
  t.after(() => { adapter.stop(); store.close(); });
  return { store, adapter, sockets, timers, cancelled, close, advance: value => { time += value; } };
}

test('QR validity follows the first 60-second and subsequent 20-second references', async t => {
  const { adapter, sockets, advance } = setup(t);
  await adapter.connect();
  sockets[0].ev.emit('connection.update', { qr: 'first-private-reference' });
  assert.equal((await adapter.status()).qrValidForMs, 60000);
  sockets[0].ev.emit('connection.update', { qr: 'second-private-reference' });
  assert.equal((await adapter.status()).qrValidForMs, 20000);
  advance(20001);
  assert.equal((await adapter.status()).qr, null);
});

test('Required post-scan restart bypasses even the longest reconnect backoff', async t => {
  const { adapter, store, sockets, timers, close } = setup(t);
  await adapter.connect();
  adapter.retries = 20;
  sockets[0].ev.emit('creds.update', { me: { id: 'test-account@s.whatsapp.net' }, account: { test: true } });
  sockets[0].ev.emit('connection.update', { isNewLogin: true });
  close(DisconnectReason.restartRequired);
  assert.equal(timers.at(-1).delay, 0);
  assert.equal(adapter.state, 'pairing');
  assert.equal(store.auth().state.creds.me.id, 'test-account@s.whatsapp.net');
  await timers.at(-1).callback();
  assert.equal(sockets.length, 2);
  assert.equal(sockets[1].config.auth.creds.me.id, 'test-account@s.whatsapp.net');
  sockets[1].ev.emit('connection.update', { connection: 'open' });
  assert.equal(adapter.ready(), true);
});

test('A successful scan immediately hides the QR while credentials finish linking', async t => {
  const { adapter, sockets } = setup(t);
  await adapter.connect();
  sockets[0].ev.emit('connection.update', { qr: 'private-reference' });
  sockets[0].ev.emit('connection.update', { isNewLogin: true });
  const status = await adapter.status();
  assert.equal(status.state, 'pairing');
  assert.equal(status.qr, null);
  assert.equal(status.qrValidForMs, 0);
});

test('Unscanned QR expiry stops reconnecting until an explicit new attempt', async t => {
  const { adapter, store, timers, sockets, close } = setup(t);
  await adapter.connect();
  const oldKey = Buffer.from(store.auth().state.creds.noiseKey.public);
  close(DisconnectReason.timedOut);
  assert.equal(adapter.state, 'qr_expired');
  assert.equal(timers.length, 0);
  assert.equal(store.enabled(), false);
  await adapter.connect();
  assert.equal(sockets.length, 2);
  assert.notDeepEqual(Buffer.from(store.auth().state.creds.noiseKey.public), oldKey);
});

test('Paired QR sessions reconnect on timeout even if the registered flag is false', async t => {
  const { adapter, store, sockets, timers, close } = setup(t);
  await adapter.connect();
  sockets[0].ev.emit('creds.update', { registered: false, me: { id: 'test-account@s.whatsapp.net' } });
  close(DisconnectReason.timedOut);
  assert.equal(adapter.state, 'reconnecting');
  assert.equal(store.enabled(), true);
  assert.equal(timers[0].delay, 1000);
  await timers[0].callback();
  assert.equal(sockets[1].config.auth.creds.me.id, 'test-account@s.whatsapp.net');
});

test('Terminal errors persist only safe metadata and stop automatic attempts', async t => {
  const { adapter, store, sockets, close, timers } = setup(t);
  await adapter.connect();
  sockets[0].ev.emit('connection.update', { qr: 'secret-qr-reference' });
  close(DisconnectReason.forbidden);
  assert.equal(adapter.state, 'needs_pairing');
  assert.equal(timers.length, 0);
  assert.deepEqual(store.connectionDiagnostic(), { code: 403, at: new Date(100000).toISOString(), paired: false });
  assert.equal(JSON.stringify(store.connectionDiagnostic()).includes('secret-qr-reference'), false);
});

test('An async renderer cannot return a QR after the socket has disconnected', async t => {
  let finish;
  const { adapter, sockets, close } = setup(t, { renderQr: () => new Promise(resolve => { finish = resolve; }) });
  await adapter.connect();
  sockets[0].ev.emit('connection.update', { qr: 'secret-reference' });
  const pending = adapter.status();
  close(DisconnectReason.loggedOut);
  finish('data:image/png;base64,fake');
  assert.equal((await pending).qr, null);
});

test('Disconnect cancels reconnection and ignores credentials from a superseded socket', async t => {
  const { adapter, store, sockets, timers, cancelled, close } = setup(t);
  await adapter.connect();
  close(DisconnectReason.connectionClosed);
  await adapter.disconnect();
  assert.equal(cancelled.includes(timers[0]), true);
  sockets[0].ev.emit('creds.update', { me: { id: 'stale-account@s.whatsapp.net' } });
  assert.equal(store.read('creds', 'main'), null);
  assert.equal(adapter.state, 'disconnected');
});

test('A hanging logout is bounded and cannot erase a new connection started in parallel', async t => {
  const { adapter, sockets, store } = setup(t, { logoutTimeoutMs: 1 });
  await adapter.connect();
  sockets[0].ev.emit('connection.update', { connection: 'open' });
  sockets[0].logout = () => new Promise(() => {});
  const pending = adapter.disconnect();
  await adapter.connect();
  assert.equal(sockets.length, 1);
  await pending;
  assert.equal(adapter.state, 'disconnected');
  await adapter.connect();
  assert.equal(sockets.length, 2);
  assert.ok(store.read('creds', 'main'));
});
