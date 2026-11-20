// Actual claim UI flow with mocked wallet and confirmation; no network or database.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const html = fs.readFileSync(path.join(__dirname, 'public/swap-lab.html'), 'utf8').replace(/\r\n/g, '\n');
const start = html.indexOf('async function confirmRealSpoincDeposit()');
const end = html.indexOf('\n/**\n * Create a Gensuki buy intent', start);
if (start < 0 || end < 0) throw Error('Claim source anchors missing');
const code = html.slice(start, end) + '\nthis.run = confirmRealSpoincDeposit;';
async function test(mode) {
  let sends = 0, confirms = 0;
  const intent = { intent_id: 123 }, sig = { value: '' }, result = { textContent: '', innerHTML: '' };
  const state = { latestRealDepositIntent: intent };
  const ctx = { state, elements: { realDepositSignature: sig, realDepositResult: result },
    document: { getElementById: () => ({ textContent: '123' }) }, canUseBridgeExecution: () => true,
    getActiveBridgeUserId: () => 'user', escapeHtml: String, SPOINC_DEPOSIT_CONFIRM_ENDPOINT: '/mock',
    resolveGensukiClaimTransactionHash: async () => { sends++; if (mode === 'ambiguous') throw Error('wallet response lost'); return 'signature'; },
    fetch: async () => { confirms++; return { json: async () => ({ success: false, error: 'confirmation unavailable' }) }; },
    loadUserPreview: async () => {} };
  vm.createContext(ctx); vm.runInContext(code, ctx);
  await ctx.run(); await ctx.run();
  assert.strictEqual(sends, 1);
  assert.strictEqual(confirms, mode === 'ambiguous' ? 0 : 2);
  assert.strictEqual(state.uncertainDepositIntentId, '123');
  if (mode !== 'ambiguous') assert.strictEqual(sig.value, 'signature');
}
(async () => { await test('ambiguous'); console.log('PASS unknown claim wallet outcome is not resent');
  await test('confirm'); console.log('PASS claim confirmation retry reuses saved signature');
})().catch(error => { console.error(error); process.exitCode = 1; });
