// Execute the real buy-confirmation UI with mocked wallet and network responses.
// No browser, wallet, real RPC, application database or external service is used.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');

const html = fs.readFileSync(path.join(__dirname, 'public/swap-lab.html'), 'utf8').replace(/\r\n/g, '\n');
const start = html.indexOf('async function confirmGensukiBuyIntent()');
const end = html.indexOf('\n    elements.directionSelect.addEventListener', start);
if (start < 0 || end < 0) throw new Error('Buy confirmation source anchors missing');
const code = html.slice(start, end) + '\nthis.run = confirmGensukiBuyIntent;';

async function test(mode) {
  let sends = 0;
  let fetches = 0;
  let cleanups = 0;
  const result = { innerHTML: '', textContent: '' };
  const signatureInput = { value: '' };
  const intent = { intent_id: 123, route_key: 'SOL_TO_SPOINC' };
  const context = {
    state: { latestGensukiBuyIntent: intent },
    elements: { gensukiBuyResult: result, gensukiBuySignature: signatureInput },
    document: { getElementById: () => ({ textContent: '123' }) },
    getSelectedGensukiBuyToken: () => ({ label: 'SOL', routeKey: 'SOL_TO_SPOINC' }),
    canUseBridgeExecution: () => true,
    escapeHtml: String,
    getActiveBridgeUserId: () => 'test',
    SPOINC_BUY_CONFIRM_ENDPOINT: '/mock',
    sendGensukiBuyTransactionWithPhantom: async () => {
      sends++;
      if (mode === 'ambiguous') throw Error('wallet response lost');
      return 'mock-signature';
    },
    fetch: async () => {
      fetches++;
      return { json: async () => ({ success: false, error: 'confirmation response lost' }) };
    },
    failAbandonedGensukiBuyIntent: async () => { cleanups++; throw Error('cleanup should not run'); },
    loadGensukiProjectDetails: async () => {}
  };
  vm.createContext(context);
  vm.runInContext(code, context);
  await context.run();
  assert.strictEqual(cleanups, 0);
  assert.match(result.innerHTML, /status needs verification/);
  assert.strictEqual(context.state.latestGensukiBuyIntent, intent);
  if (mode === 'ambiguous') {
    assert.strictEqual(fetches, 0);
    assert.strictEqual(sends, 1);
    await context.run();
    assert.strictEqual(sends, 1);
    assert.strictEqual(cleanups, 0);
  } else {
    assert.strictEqual(signatureInput.value, 'mock-signature');
    await context.run();
    assert.strictEqual(sends, 1);
    assert.strictEqual(fetches, 2);
  }
}

(async () => {
  await test('ambiguous');
  console.log('PASS ambiguous broadcast is not cleaned up or resent in this page session');
  await test('confirm');
  console.log('PASS confirmation retry reuses the retained signature');
})().catch(error => { console.error(error); process.exitCode = 1; });
