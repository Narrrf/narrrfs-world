// Execute the actual widget flow using mocked wallet, storage and endpoints only.
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, 'public/js/dspoinc-buy-widget.js'), 'utf8').replace(/\r\n/g, '\n');
const start = source.indexOf('  async function startDspoincBuy(');
const end = source.indexOf('\n  function initializeWidget(', start);
if (start < 0 || end < 0) throw new Error('Widget anchors missing');
const code = source.slice(start, end) + '\nthis.run = startDspoincBuy;';

function harness(initial, mode) {
  let saved = initial, sends = 0, cleanups = 0;
  const input = { value: '1000000' };
  const widget = { dataset: { dspoincAmount: '1000000', wallet: 'wallet', estimatedSolAmount: '1', spoincAmount: '100' }, querySelector: () => input };
  const provider = {};
  const context = {
    DEFAULT_DSPOINC_AMOUNT: 1000000, NATIVE_SOL_ADDRESS: 'native', ENDPOINTS: { buyAbandonedFail: '/buy-fail', depositAbandonedFail: '/deposit-fail' },
    normalizeInteger: Number, normalizeDecimalString: String, formatDspoincInputValue: String,
    resolveDiscordId: () => 'user', resolveDiscordName: () => 'name', getSelectedWidgetProvider: () => provider,
    connectWallet: async () => ({ wallet: 'wallet', provider }),
    readResumeState: () => ({ key: 'fixture', state: saved }),
    writeResumeState: (_key, patch) => (saved = { ...saved, ...patch }),
    clearResumeState: () => { saved = null; }, summarizeResumeState: () => '', setStatus: () => {}, escapeHtml: String,
    sendGensukiTransactionWithProvider: async () => { sends++; const error = new Error('mock wallet interruption'); if (mode === 'confirmed-no-broadcast') error.bridgeNoBroadcastConfirmed = true; throw error; },
    requestAbandonedCleanup: async () => { cleanups++; return { success: true }; },
    requestJson: async () => { throw Error('unexpected network request'); }
  };
  vm.createContext(context); vm.runInContext(code, context);
  return { run: () => context.run(widget), get saved() { return saved; }, get sends() { return sends; }, get cleanups() { return cleanups; } };
}
(async () => {
  let h = harness({ buyIntentId: 1, buyIntent: {}, stage: 'buy_payload_ready' }, 'ambiguous');
  await assert.rejects(h.run(), /interruption/); assert.strictEqual(h.saved.stage, 'buy_wallet_pending');
  await assert.rejects(h.run(), /uncertain outcome/); assert.strictEqual(h.sends, 1); assert.strictEqual(h.cleanups, 0);
  console.log('PASS buy interruption remains pending and is not resent');

  const completedBuy = { buyIntentId: 1, buyIntent: {}, buySignature: 'sig', buyConfirmed: true, buyConfirm: {}, depositIntentId: 2, depositIntent: {}, stage: 'deposit_payload_ready' };
  h = harness(completedBuy, 'ambiguous');
  await assert.rejects(h.run(), /interruption/); assert.strictEqual(h.saved.stage, 'deposit_wallet_pending');
  await assert.rejects(h.run(), /uncertain outcome/); assert.strictEqual(h.sends, 1); assert.strictEqual(h.cleanups, 0);
  console.log('PASS deposit interruption remains pending and is not resent');

  h = harness(completedBuy, 'confirmed-no-broadcast');
  await assert.rejects(h.run(), /interruption/); assert.strictEqual(h.cleanups, 1);
  assert.strictEqual(h.saved.buyConfirmed, true); assert.strictEqual(h.saved.buySignature, 'sig');
  assert.strictEqual(h.saved.depositIntentId, null); assert.strictEqual(h.saved.stage, 'buy_confirmed_no_dspoinc_yet');
  console.log('PASS failed deposit cleanup preserves the completed buy');
})().catch(error => { console.error(error); process.exitCode = 1; });
