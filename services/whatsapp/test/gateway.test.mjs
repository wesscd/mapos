import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { Store } from '../src/store.mjs';
import { Gateway } from '../src/gateway.mjs';

const key = 'a'.repeat(48);
const payload = { key, to: '5511999999999', text: 'Mensagem de teste' };
const token = 'private-token-'.repeat(4);
function setup(t, changes = {}) {
  const directory = mkdtempSync(join(tmpdir(), 'mapos-wa-test-'));
  const store = new Store(join(directory, 'test.sqlite'));
  let count = 0;
  const adapter = {
    ready: () => true, resolve: async () => 'recipient@s.whatsapp.net',
    prepare: async () => ({ key: { id: 'message-1' }, message: { conversation: 'Mensagem de teste' } }),
    relay: async () => { count++; }, ...changes
  };
  t.after(() => { store.close(); rmSync(directory, { recursive: true, force: true }); });
  return { store, gateway: new Gateway({ store, adapter, token, interval: 0 }), count: () => count };
}

test('Private API requires an exact bearer token', t => {
  const { gateway } = setup(t);
  assert.equal(gateway.authorized(`Bearer ${token}`), true);
  assert.equal(gateway.authorized(undefined), false);
  assert.equal(gateway.authorized('Bearer invalid'), false);
});

test('Repeated idempotency keys never relay a second message', async t => {
  const { gateway, count } = setup(t);
  assert.deepEqual(await gateway.route('POST', '/messages', payload), [200, { status: 'sent' }]);
  assert.deepEqual(await gateway.route('POST', '/messages', payload), [200, { status: 'sent' }]);
  assert.equal(count(), 1);
  assert.equal((await gateway.route('POST', '/messages', { ...payload, text: 'different' }))[0], 409);
});

test('Ambiguous network failures remain uncertain and cannot be automatically resent', async t => {
  let relays = 0;
  const { gateway } = setup(t, { relay: async () => { relays++; throw new Error('Timeout after sending'); } });
  assert.deepEqual(await gateway.route('POST', '/messages', payload), [200, { status: 'uncertain' }]);
  assert.deepEqual(await gateway.route('POST', '/messages', payload), [200, { status: 'uncertain' }]);
  assert.equal(relays, 1);
});

test('Delivery receipts resolve uncertainty and cannot downgrade read receipts', async t => {
  const { gateway, store } = setup(t, { relay: async () => { throw new Error('Timeout'); } });
  await gateway.route('POST', '/messages', payload);
  store.receipt('message-1', 'read'); store.receipt('message-1', 'sent');
  assert.deepEqual(await gateway.route('GET', `/messages/${key}`), [200, { status: 'read' }]);
});

test('Disconnected and invalid recipients do not create a sending record', async t => {
  const { gateway, store } = setup(t, { ready: () => false });
  assert.equal((await gateway.route('POST', '/messages', payload))[0], 503);
  assert.equal(store.get(key), undefined);
  assert.equal((await gateway.route('POST', '/messages', { ...payload, to: 'group@g.us' }))[0], 422);
});

test('Recipients missing from WhatsApp fail before sending', async t => {
  const { gateway, store, count } = setup(t, { resolve: async () => undefined });
  assert.equal((await gateway.route('POST', '/messages', payload))[0], 422);
  assert.equal(store.get(key), undefined); assert.equal(count(), 0);
});

test('Private recipient diagnostics never prepare or relay a message', async t => {
  const { gateway, count } = setup(t, { checkRecipient: async number => {
    assert.equal(number, payload.to);
    return { registered: true, hasPhoneAddress: true, hasPrivateAddress: true };
  }, prepare: async () => { throw new Error('Diagnostic must not prepare a message'); } });
  assert.deepEqual(await gateway.route('POST', '/recipients/check', { to: payload.to }), [200, { registered: true, hasPhoneAddress: true, hasPrivateAddress: true }]);
  assert.equal(count(), 0);
  assert.equal((await gateway.route('POST', '/recipients/check', { to: 'group@g.us' }))[0], 422);
});

test('Unavailable recipient diagnostics are distinct from an unregistered number', async t => {
  const { gateway } = setup(t, { checkRecipient: async () => { throw new Error('Lookup timeout'); } });
  assert.equal((await gateway.route('POST', '/recipients/check', { to: payload.to }))[0], 503);
  assert.equal(gateway.busy, false);
});

test('Concurrent requests are serialized, including recipient lookup', async t => {
  let release;
  const wait = new Promise(resolve => { release = resolve; });
  const { gateway, count } = setup(t, { resolve: async () => { await wait; return 'recipient@s.whatsapp.net'; } });
  const first = gateway.route('POST', '/messages', payload);
  assert.equal((await gateway.route('POST', '/messages', payload))[0], 429);
  release(); await first; assert.equal(count(), 1);
});

test('SQL auth preserves credentials, keys and preferences across restart; interrupted sends become uncertain', async () => {
  const directory = mkdtempSync(join(tmpdir(), 'mapos-wa-auth-'));
  const path = join(directory, 'test.sqlite');
  try {
    const original = new Store(path);
    const auth = original.auth();
    auth.state.creds.registered = true; auth.save();
    await auth.state.keys.set({ session: { recipient: Buffer.from([1, 2, 3]) } });
    original.setEnabled(true); original.begin(key, 'fingerprint', 'message-1', {}); original.close();
    const restored = new Store(path);
    assert.equal(restored.auth().state.creds.registered, true);
    assert.deepEqual((await restored.auth().state.keys.get('session', ['recipient'])).recipient, Buffer.from([1, 2, 3]));
    assert.equal(restored.enabled(), true); assert.equal(restored.get(key).status, 'uncertain');
    restored.clearAuth(); restored.setEnabled(false);
    assert.equal(restored.read('creds', 'main'), null); restored.close();
  } finally { rmSync(directory, { recursive: true, force: true }); }
});
