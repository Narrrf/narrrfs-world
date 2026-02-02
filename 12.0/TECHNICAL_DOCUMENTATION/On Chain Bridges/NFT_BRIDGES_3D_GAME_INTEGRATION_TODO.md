# 🌉 NFT Bridges → 3D Riddle Game – Integration To-Do Worklist

**Created:** February 2, 2026  
**Status:** 📋 **ACTIVE – AWAITING TESTNET & IFRAME CODE**  
**Contact:** NFT Bridges team – testnet + iframe connect code confirmed incoming  
**Wallet:** WalletConnect (easy integration)

---

## ✅ **CONFIRMED BY NFT BRIDGES TEAM (Feb 2, 2026)**

- [x] Testnet will be set up for Narrrfs World
- [x] Easy iframe connect code will be provided
- [x] WalletConnect used – compatible with our integration plans

---

## 📋 **PHASE 1: IFRAME + WALLET (Low Risk)**

### Pre-Integration (Awaiting NFT Bridges)

- [ ] **Receive testnet credentials** – URL, config, any API keys
- [ ] **Receive iframe embed code** – The "4 lines" or full snippet from NFT Bridges
- [ ] **Document iframe config** – Params, dimensions, callbacks

### Implementation

- [ ] **Add WalletConnect dependency** – `npm install` or script include
- [ ] **Create Bridge UI entry point** – Pause menu tab or Options sub-tab "Bridge NFTs"
- [ ] **Embed iframe** – Insert NFT Bridges widget in Bridge tab
- [ ] **Wire WalletConnect** – Ensure game can pass/use connected wallet for bridge
- [ ] **Test on testnet** – Verify bridge flow works in 3D game context
- [ ] **Mobile/VR check** – Ensure Bridge tab works on mobile and in VR (if applicable)

### Files to Modify

- [ ] `public/three.js/main.js` – Pause menu structure, Bridge tab
- [ ] `public/three.js/gui-system.js` – Bridge tab UI, iframe container
- [ ] `public/three.js/index.html` – WalletConnect script if needed
- [ ] `public/three.js/package.json` – WalletConnect dependency (if npm)

---

## 📋 **PHASE 2: CHEESE SCEPTER VERIFICATION (Medium Risk)**

### Prerequisites

- [ ] Phase 1 complete (iframe + wallet working)
- [ ] Cheese Scepter collection defined (chain + contract)
- [ ] Collection whitelisted in NFT Bridges (if required)

### Implementation

- [ ] **Wallet connection on Level 1** – Prompt or optional "Connect Wallet" near blue cheese
- [ ] **Ownership check** – Query: does connected wallet own Cheese Scepter NFT?
- [ ] **Gate logic** – If yes → allow riddle start; if no → keep "You need a Cheese Scepter" toast
- [ ] **Optional: Link wallet ↔ Discord** – Store `wallet_address` linked to `discord_id` for profile

### Files to Modify

- [ ] `public/three.js/main.js` – Level 1 blue cheese interaction, ownership check
- [ ] `api/user/` – New or extended endpoint for wallet–Discord link (if needed)
- [ ] `db/` – Schema for `wallet_address` if persisting link

---

## 📋 **PHASE 3: CROSS-CHAIN REWARDS (Future)**

- [ ] Define reward NFTs or tokens to mint/bridge on riddle completion
- [ ] Integrate with NFT Bridges for cross-chain minting
- [ ] Backend support for reward distribution

---

## 🔧 **TECHNICAL DEPENDENCIES**

| Dependency | Purpose | Status |
|------------|---------|--------|
| **WalletConnect** | Wallet connection for bridge + verification | ✅ Confirmed by NFT Bridges |
| **NFT Bridges iframe** | Bridge widget embed | ⏳ Awaiting code from team |
| **Testnet access** | Development/testing | ⏳ Awaiting setup from team |

---

## 📁 **RELATED DOCS**

| Doc | Location |
|-----|----------|
| **Integration discussion** | `12.0/LAB_NOTES/2026/02_FEBRUARY/DAILY_NOTES/2026-02-02/ON_CHAIN_BRIDGES_INTEGRATION_DISCUSSION_2026-02-02.md` |
| **Technical spec** | `12.0/TECHNICAL_DOCUMENTATION/On Chain Bridges/NFT_BRIDGES_3D_GAME_INTEGRATION_TECHNICAL.md` |
| **Litepaper** | `12.0/TECHNICAL_DOCUMENTATION/On Chain Bridges/NFT Bridges Litepaper V.4.1_a.pdf` |
| **3D Game doc** | `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` |

---

**Last Updated:** February 2, 2026
