/*! Third-party notices: gensuki-v1-codec.licenses.txt */
// Decode V1 with Umi; web3.js 1.x cannot serialize V1 messages itself.
// Callers must still bind the returned transaction to the authenticated intent.
import { getTransactionV1Serializer } from '@metaplex-foundation/umi';
import { toWeb3JsTransaction } from '@metaplex-foundation/umi-web3js-adapters';

export function decodeV1(bytes) {
  if (!(bytes instanceof Uint8Array) || bytes.length === 0 || bytes.length > 4096 || bytes[0] !== 0x81) {
    throw new Error('Invalid V1 transaction payload.');
  }
  const [transaction, offset] = getTransactionV1Serializer().deserialize(bytes);
  if (offset !== bytes.length || transaction.message.version !== 1) {
    throw new Error('Invalid or trailing V1 transaction data.');
  }
  const result = toWeb3JsTransaction(transaction);
  const serialized = result.serialize();
  if (serialized.length !== bytes.length || serialized.some((value, index) => value !== bytes[index])) {
    throw new Error('V1 transaction did not round-trip exactly.');
  }
  return result;
}
// Only an explicit numeric 1 from the selected wallet enables a V1 request.
// Missing, malformed or throwing capability data conservatively selects V0.
export function detectWalletVersion(provider) {
  try {
    const candidates = [
      provider?.supportedTransactionVersions,
      provider?.wallet?.supportedTransactionVersions,
      provider?.adapter?.supportedTransactionVersions,
      provider?.wallet?.features?.['solana:signTransaction']?.supportedTransactionVersions,
      provider?.wallet?.features?.['solana:signAndSendTransaction']?.supportedTransactionVersions,
      provider?.features?.['solana:signTransaction']?.supportedTransactionVersions,
      provider?.features?.['solana:signAndSendTransaction']?.supportedTransactionVersions,
      provider?.provider?.wallet?.features?.['solana:signTransaction']?.supportedTransactionVersions
    ];
    for (const versions of candidates) {
      if (Array.isArray(versions) && versions.includes(1)) return 'v1';
      if (versions && typeof versions.has === 'function' && versions.has(1)) return 'v1';
    }
  } catch (_) { return 'v0'; }
  return 'v0';
}