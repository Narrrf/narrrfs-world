// Exercises actual browser decoder/sender functions with synthetic transactions and mocked wallets.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const crypto = require('crypto');
const kit = require('@solana/kit');
const web3 = require('@solana/web3.js');
const root = path.join(__dirname, '../..');
const html = fs.readFileSync(path.join(root, 'public/swap-lab.html'), 'utf8').replace(/\r\n/g, '\n');
const widget = fs.readFileSync(path.join(root, 'public/js/dspoinc-buy-widget.js'), 'utf8').replace(/\r\n/g, '\n');
function cut(source, start, end) {
  const a = source.indexOf(start), b = source.indexOf(end, a);
  if (a < 0 || b < 0) throw new Error('Source anchors not found: ' + start);
  return source.slice(a, b);
}
const keypair = web3.Keypair.generate();
function fixture(version) {
  let message = kit.createTransactionMessage({ version });
  message = kit.setTransactionMessageFeePayer(kit.address(keypair.publicKey.toBase58()), message);
  message = kit.setTransactionMessageLifetimeUsingBlockhash({ blockhash: kit.blockhash('11111111111111111111111111111111'), lastValidBlockHeight: 100n }, message);
  if (version === 1) message = kit.setTransactionMessageComputeUnitLimit(200000, message);
  message = kit.appendTransactionMessageInstruction({ programAddress: kit.address('11111111111111111111111111111111'), data: new Uint8Array(version ? 1800 : 8).fill(7) }, message);
  return Buffer.from(kit.getBase64EncodedWireTransaction(kit.compileTransaction(message)), 'base64').toString('base64');
}
const codecContext = { console, TextEncoder, TextDecoder, Uint8Array, ArrayBuffer, Buffer, crypto: crypto.webcrypto, setTimeout, clearTimeout };
vm.createContext(codecContext);
vm.runInContext(fs.readFileSync(path.join(root, 'public/js/gensuki-v1-codec.js'), 'utf8'), codecContext);
const codec = codecContext.NarrrfsGensukiV1;
const bytes = (s) => Buffer.from(s, 'base64').toString('binary');
const swapCode = cut(html, 'function decodeGensukiTransactionPayload(', '\n// Select the named extension') +
  cut(html, 'async function sendGensukiClaimTransactionWithPhantom(', '\n/**\n * Resolve the transaction hash') +
  cut(html, 'async function sendGensukiBuyTransactionWithPhantom(', '\n/**\n * Confirm a SOL_TO_SPOINC') +
  '\nthis.api = { decodeGensukiTransactionPayload, sendGensukiClaimTransactionWithPhantom, sendGensukiBuyTransactionWithPhantom };';
let calls = 0;
const provider = { supportedTransactionVersions: new Set([0, 1]), publicKey: keypair.publicKey,
  connect: async () => ({ publicKey: keypair.publicKey }),
  signAndSendTransaction: async (tx) => { calls++; return { signature: 'offline-' + tx.version }; } };
const ctx = { window: { solanaWeb3: web3, NarrrfsGensukiV1: codec }, Uint8Array, atob: bytes,
  getSelectedSwapWalletProvider: () => provider };
vm.createContext(ctx); vm.runInContext(swapCode, ctx);
(async () => {
  for (const version of [0, 1]) {
    const wire = fixture(version);
    assert(Buffer.from(ctx.api.decodeGensukiTransactionPayload(wire).serialize()).equals(Buffer.from(wire, 'base64')));
    const intent = { transaction: wire, transactionVersion: 'v' + version, wallet: keypair.publicKey.toBase58() };
    assert.strictEqual(await ctx.api.sendGensukiClaimTransactionWithPhantom(intent), 'offline-' + version);
    assert.strictEqual(await ctx.api.sendGensukiBuyTransactionWithPhantom(intent), 'offline-' + version);
  }
  assert.strictEqual(calls, 4);
  provider.supportedTransactionVersions = new Set([0]);
  await assert.rejects(ctx.api.sendGensukiClaimTransactionWithPhantom({ transaction: fixture(1), transactionVersion: 'v1', wallet: keypair.publicKey.toBase58() }), /version or selected wallet/);
  assert.strictEqual(calls, 4);
  delete ctx.window.NarrrfsGensukiV1;
  assert.throws(() => ctx.api.decodeGensukiTransactionPayload(fixture(1)), /codec is unavailable/);
  assert.strictEqual(calls, 4);
  console.log('PASS actual Swap Lab V0/V1 decoder and mocked buy/claim sender');
  console.log('PASS unsupported wallet and missing codec fail before signing');

  const widgetCode = cut(widget, '  async function ensureV1Codec()', '\n  async function requestAbandonedCleanup(') +
    '\nthis.api = { decodeGensukiTransactionPayload, sendGensukiTransactionWithProvider };';
  const wc = { window: { NarrrfsGensukiV1: codec }, v1CodecLoadPromise: null, Uint8Array, atob: bytes,
    ensureSolanaWeb3: async () => web3,
    connectWallet: async (wallet) => ({ wallet: wallet || keypair.publicKey.toBase58() }),
    createConfirmedNoBroadcastError: (message) => Object.assign(new Error(message), { bridgeNoBroadcastConfirmed: true }),
    isExplicitWalletRejection: () => false, providerLabel: () => 'mock wallet' };
  vm.createContext(wc); vm.runInContext(widgetCode, wc);
  provider.supportedTransactionVersions = new Set([0, 1]);
  for (const version of [0, 1]) {
    const wire = fixture(version);
    assert(Buffer.from((await wc.api.decodeGensukiTransactionPayload(wire)).serialize()).equals(Buffer.from(wire, 'base64')));
    assert.strictEqual(await wc.api.sendGensukiTransactionWithProvider(provider, { transaction: wire, transactionVersion: 'v' + version, wallet: keypair.publicKey.toBase58() }, 'fixture'), 'offline-' + version);
  }
  provider.supportedTransactionVersions = new Set([0]);
  await assert.rejects(wc.api.sendGensukiTransactionWithProvider(provider, { transaction: fixture(1), transactionVersion: 'v1', wallet: keypair.publicKey.toBase58() }, 'fixture'), /version or selected wallet/);
  console.log('PASS actual widget V0/V1 decoder and mocked sender');
  console.log('PASS widget unsupported wallet fails before signing');
})().catch((error) => { console.error(error); process.exitCode = 1; });
