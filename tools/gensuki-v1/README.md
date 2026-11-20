# Gensuki V1 browser codec

This directory builds the browser-only Umi adapter for Gensuki-prepared V1 transactions. V0 and legacy transactions continue through the existing web3.js decoder. The codec does not access wallets, RPC, or the application database.

From this directory, use Node.js 22 with the exact committed lockfile:

```sh
npm ci --ignore-scripts --no-audit --no-fund
npm run build
node test.cjs
node test-integration.cjs
node test-intent-client.cjs
node test-settlement.cjs
```

`npm run build` writes `public/js/gensuki-v1-codec.js` and its generated `gensuki-v1-codec.licenses.txt` third-party notices. The repository ignores `*.json` and `package-lock.json`; the exact `package.json` and `package-lock.json` for this directory must be explicitly included when an approved commit is staged. Do not include `node_modules`.

The tests use synthetic transactions, ephemeral keys, mock wallets, mock partner/RPC responses, and disposable SQLite databases. They do not establish actual wallet, Ledger, Gensuki, production-RPC, or token-recipient compatibility. The V1 adapter accepts only V1 wire data and rejects malformed, oversized, trailing, and non-round-tripping payloads.