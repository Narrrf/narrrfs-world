# 🎯 Bingo × Helius Live NFT Verification — Technical Integration Plan

Created: 2025-10-17
Owner: Coreforge + Cheese Architect
Status: Proposal (Ready for implementation)

---

## 1) Goal
- Verify Bingo tickets as real Solana NFTs in real time (ownership + integrity).
- Show “Verified NFT” status on tickets; auto-revoke when transferred/burned.

## 2) Scope (MVP)
- One collection (Bingo Tickets) or per-ticket mint support.
- Wallet connects → server verifies via Helius → badge shown.
- Re-verify on demand and on schedule.

## 3) Dependencies
- Helius API key (env: HELIUS_API_KEY)
- Existing patterns: `api/wallet/get-nfts.php`, `api/admin/get-community-wallet-nfts.php`, `api/debug/test-helius-api.php`.
- DB: SQLite `/var/www/html/db/narrrf_world.sqlite`.

## 4) Data Model (DB)
- New table `tbl_bingo_ticket_nft_links`
  - id INTEGER PK
  - user_id TEXT NOT NULL (Discord ID)
  - ticket_id TEXT NOT NULL
  - wallet TEXT NOT NULL
  - mint TEXT NULL
  - collection TEXT NULL
  - grid_hash TEXT NOT NULL
  - verified INTEGER DEFAULT 0
  - last_verified_at DATETIME
  - created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  - UNIQUE(user_id, ticket_id)
- Indexes: `(user_id)`, `(wallet)`, `(mint)`.

## 5) Endpoints
- POST `/api/bingo/link-ticket-nft.php`
  - Input: user_id, ticket_id, wallet, mint(optional), collection(optional), signature, signed_message
  - Steps:
    1. Verify signature (Ed25519) over server nonce-bound message.
    2. If `mint` given: confirm wallet holds `mint` via Helius.
       Else if `collection` given: list wallet NFTs and filter to collection.
    3. Fetch NFT metadata; check `attributes.ticket_id` and `grid_hash` vs server-computed hash.
    4. Upsert row with `verified=1`, set `last_verified_at`.
    5. Return `{ success, verified, details }`.

- GET `/api/bingo/verify-ticket-status.php?user_id=&ticket_id=`
  - Re-run the checks above; update `verified` + `last_verified_at`.

- GET `/api/bingo/list-ticket-nfts.php?user_id=`
  - List user tickets + NFT linkage + `verified` status.

## 6) Helius Calls
- Enhanced API (preferred): `GET https://api.helius.xyz/v0/addresses/{wallet}/nfts?api-key=...`
- RPC fallback (robustness): `POST https://mainnet.helius-rpc.com/?api-key=...`
- Collection detection:
  - Prefer `nft.collection.{name,key}`
  - Fallback to `nft.grouping[{groupKey:'collection',groupValue:'<address>'}]`

## 7) Frontend (Bingo.html)
- Add button per ticket: “Verify NFT”.
- Flow: connect wallet → sign nonce → POST to `/api/bingo/link-ticket-nft.php`.
- UI: show “Verified NFT” badge; “Refresh Status” button (calls verify endpoint).

## 8) Security
- Server nonce per user/session for signed_message (prevent replay).
- Real Ed25519 signature verification (libsodium/sodium_compat) — replace placeholder.
- Validate wallet (base58), mint format, ticket_id format.
- Rate-limit verification endpoints.

## 9) Integrity Checks
- Compute `grid_hash = sha256(JSON.stringify(flatGrid25))` on server.
- Compare to NFT metadata attribute `grid_hash` (mint-time set).
- If mismatch → reject verification.

## 10) Revocation & Freshness
- Re-verify on:
  - Manual “Refresh Status”
  - Daily cron (server) for all verified tickets
  - Before tournament/paid sessions
- On failure (no longer owned) → set `verified=0`.

## 11) Telemetry & Logs
- Log Helius HTTP code, method used (enhanced vs rpc), response previews (truncated).
- Store `last_verified_at` for audits.

## 12) Rollout Plan
- T0: Create table + 3 endpoints (feature-flagged).
- T1: Wire minimal UI (Verify / Refresh buttons + badge).
- T2: Enable daily cron re-verify.
- T3: Expand to additional collections (if needed).

## 13) Acceptance Criteria
- User with valid NFT sees Verified badge within 3s.
- Transfer NFT → next verify flips to unverified.
- Helius outages → graceful fallback, retry path.
- No false positives on `grid_hash`/ticket_id checks.

## 14) Open Questions
- Are Bingo tickets always from one collection or per-ticket mint?
- Do we mint tickets ourselves (control metadata), or verify external mints?
- Frequency of scheduled re-verification (daily vs hourly)?

---

Implementation time (MVP): 1–2 days (backend 1d, frontend 0.5d, ops 0.5d).
