# 🌉 On Chain Bridges → 3D Riddle Game Integration Discussion

**Date:** February 1, 2026  
**Status:** ✅ **COMPATIBILITY ASSESSMENT COMPLETE**  
**Source:** NFT Bridges Litepaper V.4.1 (nftbridges.xyz)  
**Target:** Narrrfs World 3D riddle game (Cheese Temple)

---

## 📚 **SOURCE MATERIAL**

| Item | Location |
|------|----------|
| **Litepaper** | `12.0/TECHNICAL_DOCUMENTATION/On Chain Bridges/NFT Bridges Litepaper V.4.1_a.pdf` |
| **3D Game Doc** | `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` |
| **Riddle Rules** | `12.0/RULES/13_3D_GAME_DSPOINC_SYNC_RULE.md` |

---

## ✅ **COMPATIBILITY ASSESSMENT – VERDICT: FEASIBLE WITH ADDITIONS**

### Summary

| Aspect | Compatibility | Notes |
|--------|---------------|-------|
| **Bridge-as-iFrame** | ✅ **High** | 4 lines of JS – plug-and-play, fits pause menu or dedicated UI |
| **In-Game NFT Import** | ✅ **High** | Players bridge NFTs from other chains → use in game (Cheese Scepter) |
| **Cheese Scepter Gate** | ✅ **Strong fit** | Level 1 already has gate – can require verified NFT ownership |
| **Auth / Wallet** | ⚠️ **Gap** | Narrrfs uses Discord; NFT Bridges uses wallet – need both |
| **Supported Chains** | ✅ **Broad** | Ethereum, Solana, Polygon, Base, Soneium, Astar, Stacks, Bitcoin, BSC, Flow |
| **Technical Stack** | ✅ **Compatible** | Three.js in browser – iframe embed works |

---

## 🎯 **KEY INTEGRATION POINTS FROM LITEPAPER**

### 1. Bridge-as-an-iFrame (Primary Integration Path)

> *"NFT Bridges has developed a lightweight, embeddable bridge widget in the form of an iFrame. This solution enables any Web3 game, launchpad, or dApp to integrate cross-chain NFT transfers with just four lines of JavaScript."*

**Benefits for Narrrfs 3D Game:**
- **Rapid integration** – No custom contracts or UI
- **Cross-chain player acquisition** – Onboard players from Solana, Polygon, etc.
- **In-game NFT importing** – Players bridge Cheese Scepter (or other NFTs) from other chains into the game's native chain
- **Monetization** – New revenue from bridged players and assets

### 2. Cheese Scepter Gate – Natural Fit

Level 1 already shows: *"You need a Cheese Scepter to start the riddle"* (Feb 1, 2026).

**Integration:**
- **Before:** Conceptual gate (no verification)
- **After:** Wallet connection + NFT Bridges verification → only players with Cheese Scepter NFT (on supported chain) can start the riddle
- **Flow:** Connect wallet → verify Cheese Scepter ownership → unlock riddle

### 3. Lock-and-Mint Flow (Reference)

1. User selects NFT + destination chain
2. NFT locked on source chain
3. Chainlink CCIP relays message
4. NFT minted on destination chain with metadata
5. Return path: burn destination → unlock original

**Relevance:** Players can bring NFTs from Solana/Polygon/etc. into the chain where the game expects them.

---

## 🔧 **TECHNICAL GAPS & REQUIREMENTS**

### Current Narrrfs Stack

| Component | Current | NFT Bridges Needs |
|-----------|---------|-------------------|
| **Auth** | Discord ID (`discord_id`) | Wallet address (Hiro, Phantom, WalletConnect) |
| **User identity** | `localStorage` + session | Wallet + optional Discord link |
| **NFT verification** | None | On-chain ownership check |
| **UI** | Pause menu, HUD, options | Add "Bridge" or "Wallet" section |

### Required Additions

1. **Wallet connection**
   - Hiro (Stacks), Phantom (Solana), WalletConnect (EVM)
   - Store `wallet_address` and optionally link to `discord_id`

2. **NFT Bridges iFrame**
   - Embed in pause menu or dedicated "Bridge" tab
   - 4 lines of JavaScript (per litepaper)

3. **Cheese Scepter verification**
   - API or client-side check: does connected wallet own Cheese Scepter NFT?
   - Depends on collection being on a supported chain and whitelisted in NFT Bridges

4. **Dual identity**
   - Keep Discord for DSPOINC, traits, profile
   - Add wallet for NFT-gated content and bridging

---

## 📋 **INTEGRATION MAPPING**

| NFT Bridges Concept | 3D Riddle Game Element | Integration Approach |
|---------------------|------------------------|----------------------|
| **Bridge-as-iFrame** | Pause menu / Options | Add "Bridge NFTs" tab with embedded iframe |
| **In-game NFT import** | Cheese Scepter gate | Verify ownership before allowing riddle start |
| **Cross-chain players** | Player acquisition | Players from Solana/Polygon can bridge in and play |
| **TLPT payments** | Bridge fees | Users pay TLPT for bridge (handled by NFT Bridges) |
| **Collection whitelist** | Cheese Scepter collection | Register collection in NFT Bridges registry |

---

## 🚀 **IMPLEMENTATION ROADMAP (DRAFT)**

### Phase 1: Wallet + iFrame (Low Risk)

- [ ] Add wallet connection (Phantom + WalletConnect for EVM)
- [ ] Embed NFT Bridges iFrame in pause menu or new "Bridge" tab
- [ ] No riddle gating yet – bridge available for players who want it

### Phase 2: Cheese Scepter Verification (Medium Risk)

- [ ] Define Cheese Scepter collection (chain + contract)
- [ ] Add ownership check before Level 1 riddle start
- [ ] Link wallet to Discord (optional) for unified profile

### Phase 3: Cross-Chain Rewards (Future)

- [ ] Riddle completions could mint/bridge rewards on other chains
- [ ] Requires deeper NFT Bridges + backend integration

---

## ⚠️ **RISKS & CONSIDERATIONS**

| Risk | Mitigation |
|------|------------|
| **Auth complexity** | Keep Discord primary; wallet optional for NFT features |
| **Chain support** | Confirm Cheese Scepter / Artanova on supported chains |
| **User friction** | Bridge optional; only gate Level 1 riddle if NFT required |
| **NFT Bridges availability** | iFrame and docs may still be in development – confirm with NFT Bridges team |

---

## 📞 **NEXT STEPS**

### ✅ Completed (Feb 2, 2026)
- **Contacted NFT Bridges team** – Testnet + iframe code confirmed incoming
- **Wallet stack** – WalletConnect (confirmed by NFT Bridges)

### ⏳ Awaiting
1. **Testnet credentials** – URL, config from NFT Bridges
2. **Iframe embed code** – The easy connect snippet from NFT Bridges
3. **Define Cheese Scepter** – Chain, contract (for Phase 2)

---

## 📝 **LITEPAPER EXCERPTS (REFERENCE)**

**Supported networks:** Ethereum, Stacks, Bitcoin, Solana, Polygon, Base, BSC, Astar, Soneium, Flow

**Wallets:** Hiro, Phantom, WalletConnect (EVM)

**Bridge fee:** $5 per NFT (paid in TLPT)

**Contact:** ilan@nftbridges.xyz, Telegram @ilanklein

---

**Last Updated:** February 2, 2026
