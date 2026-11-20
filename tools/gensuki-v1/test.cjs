// Offline synthetic fixtures only: no wallets, RPC or database access.
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const crypto = require('crypto');
const kit = require('@solana/kit');
const web3 = require('@solana/web3.js');

const context = { console, TextEncoder, TextDecoder, Uint8Array, ArrayBuffer, Buffer,
  crypto: crypto.webcrypto, setTimeout, clearTimeout };
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../../public/js/gensuki-v1-codec.js'), 'utf8'), context);
const codec = context.NarrrfsGensukiV1;
const keypair = web3.Keypair.generate();
const payer = kit.address(keypair.publicKey.toBase58());
const lifetime = { blockhash: kit.blockhash('11111111111111111111111111111111'), lastValidBlockHeight: 100n };

function build(version, size) {
  let message = kit.createTransactionMessage({ version });
  message = kit.setTransactionMessageFeePayer(payer, message);
  message = kit.setTransactionMessageLifetimeUsingBlockhash(lifetime, message);
  if (version === 1) message = kit.setTransactionMessageComputeUnitLimit(200000, message);
  message = kit.appendTransactionMessageInstruction({
    programAddress: kit.address('11111111111111111111111111111111'), data: new Uint8Array(size).fill(7)
  }, message);
  const compiled = kit.compileTransaction(message);
  return { compiled, wire: Buffer.from(kit.getBase64EncodedWireTransaction(compiled), 'base64') };
}

const v0 = build(0, 15);
const oldTransaction = web3.VersionedTransaction.deserialize(v0.wire);
assert.strictEqual(oldTransaction.version, 0);
assert(Buffer.from(oldTransaction.serialize()).equals(v0.wire));
console.log('PASS V0 web3 decoder round-trip');

const v1 = build(1, 1800);
assert(v1.wire.length > 1232 && v1.wire.length < 4096);
const transaction = codec.decodeV1(v1.wire);
assert.strictEqual(transaction.version, 1);
assert(Buffer.from(transaction.serialize()).equals(v1.wire));
transaction.sign([keypair]);
const key = crypto.createPublicKey({
  key: Buffer.concat([Buffer.from('302a300506032b6570032100', 'hex'), keypair.publicKey.toBuffer()]),
  format: 'der', type: 'spki'
});
assert(crypto.verify(null, v1.compiled.messageBytes, key, Buffer.from(transaction.signatures[0])));
console.log('PASS V1 large browser codec round-trip and offline signature');
assert.throws(() => codec.decodeV1(Buffer.concat([v1.wire, Buffer.from([0])])));
assert.throws(() => codec.decodeV1(v1.wire.subarray(0, 5)));
assert.throws(() => codec.decodeV1(v0.wire));
console.log('PASS V1 malformed, trailing and wrong-version rejection');

assert.strictEqual(codec.detectWalletVersion({ supportedTransactionVersions: new Set([0, 1]) }), 'v1');
assert.strictEqual(codec.detectWalletVersion({ wallet: { features: { 'solana:signTransaction': { supportedTransactionVersions: [1] } } } }), 'v1');
assert.strictEqual(codec.detectWalletVersion({ supportedTransactionVersions: ['1'] }), 'v0');
assert.strictEqual(codec.detectWalletVersion({ supportedTransactionVersions: [0] }), 'v0');
assert.strictEqual(codec.detectWalletVersion({}), 'v0');
assert.strictEqual(codec.detectWalletVersion({ get supportedTransactionVersions() { throw Error('unavailable'); } }), 'v0');
console.log('PASS wallet capability detection and conservative fallback');