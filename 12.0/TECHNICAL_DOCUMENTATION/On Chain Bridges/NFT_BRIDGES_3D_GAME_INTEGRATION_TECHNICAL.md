# 🌉 NFT Bridges → 3D Riddle Game – Technical Integration Specification

**Created:** February 2, 2026  
**Status:** 📋 **ACTIVE – AWAITING TESTNET & IFRAME CODE**  
**Target:** Narrrfs World 3D game (Cheese Temple, Levels 1–6)

---

## 📋 **OVERVIEW**

Integrate NFT Bridges (nftbridges.xyz) into the 3D riddle game to enable:
1. **Bridge-as-iFrame** – Players bridge NFTs cross-chain from within the game
2. **Cheese Scepter verification** – NFT-gated Level 1 riddle (optional)
3. **Cross-chain player acquisition** – Onboard players from Solana, Polygon, etc.

**Confirmed by NFT Bridges team (Feb 2, 2026):**
- Testnet will be set up for Narrrfs World
- Easy iframe connect code will be provided
- WalletConnect used – straightforward integration

---

## 🏗️ **ARCHITECTURE**

### Current 3D Game Stack

| Component | Technology |
|-----------|------------|
| **Engine** | Three.js (v0.181.1) |
| **Build** | Vite (v7.2.2) |
| **Auth** | Discord ID (`discord_id`) |
| **UI** | gui-system.js, pause menu, options |
| **Entry** | `public/three.js/index.html` |

### Integration Points

```
┌─────────────────────────────────────────────────────────────┐
│                    3D RIDDLE GAME                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │ Pause Menu  │  │ Options     │  │ Level 1 Blue Cheese  │ │
│  │             │  │             │  │ (Cheese Scepter)     │ │
│  │ [Bridge]    │  │ [Wallet]    │  │ → Ownership check    │ │
│  │   tab       │  │   (future)  │  │   (Phase 2)          │ │
│  └──────┬──────┘  └──────┬──────┘  └──────────┬──────────┘ │
│         │                │                     │             │
│         ▼                ▼                     ▼             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              NFT BRIDGES IFRAME                       │   │
│  │  (WalletConnect → bridge UI → lock/mint flow)        │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 **FILE STRUCTURE (PLANNED)**

### New/Modified Files

| File | Purpose |
|------|---------|
| `public/three.js/main.js` | Pause menu Bridge tab, Level 1 ownership gate |
| `public/three.js/gui-system.js` | Bridge tab UI, iframe container, WalletConnect init |
| `public/three.js/index.html` | WalletConnect script (if CDN) |
| `public/three.js/package.json` | WalletConnect dependency (if npm) |

### Optional New Module

| File | Purpose |
|------|---------|
| `public/three.js/bridge-integration.js` | NFT Bridges iframe wrapper, WalletConnect helpers |

---

## 🔌 **WALLETCONNECT INTEGRATION**

### Why WalletConnect

- NFT Bridges uses WalletConnect
- Supports EVM (MetaMask, etc.), Phantom (Solana), Hiro (Stacks)
- Single integration for multi-chain

### Implementation Notes

- Add WalletConnect SDK (via npm or CDN)
- Initialize on game load or when Bridge tab opened
- Persist connection state in `localStorage` (optional)
- On disconnect: clear bridge state, keep Discord auth

### Wallet ↔ Discord (Optional)

- Store `wallet_address` in `tbl_users` or new `tbl_user_wallets`
- Link via `discord_id` for unified profile
- Enables: "This wallet owns Cheese Scepter" → unlock riddle for Discord user

---

## 🖼️ **IFRAME INTEGRATION**

### Placement

- **Primary:** New "Bridge NFTs" tab in pause menu (alongside Options, Controls, etc.)
- **Alternative:** Sub-tab under Options

### Container Specs (To Be Confirmed by NFT Bridges)

- Recommended dimensions (desktop/mobile)
- Responsive behavior
- URL parameters for pre-config (chains, collection)

### Callbacks (If Supported)

- `onBridgeComplete` – Refresh game state, show toast
- `onBridgeStart` – Disable UI or show loading
- `onBridgeError` – Show error toast

---

## 🧀 **CHEESE SCEPTER GATE (Phase 2)**

### Current Behavior (Feb 1, 2026)

- Proximity to blue cheese (12 units) → "Press [E] to interact"
- E key / VR grip → Toast: "You need a Cheese Scepter to start the riddle"
- No on-chain verification

### Target Behavior (Phase 2)

1. Player approaches blue cheese
2. If wallet not connected → "Connect wallet to verify Cheese Scepter" (or keep current message)
3. If wallet connected → Check ownership via NFT Bridges or direct RPC
4. If owns Cheese Scepter → Start riddle
5. If not → "You need a Cheese Scepter to start the riddle"

### Ownership Check Options

- **A:** NFT Bridges API (if provided)
- **B:** Direct chain RPC (e.g. `balanceOf` on ERC-721 contract)
- **C:** Backend proxy – our API calls on helius RPC, returns ownership to game

---

## 📊 **DATA FLOW**

### Phase 1 (Bridge Only)

```
User opens Pause → Clicks "Bridge NFTs" → Iframe loads
→ WalletConnect (if not connected) → User bridges NFT
→ Done (no game state change)
```

### Phase 2 (Cheese Scepter Gate)

```
User approaches blue cheese → E key
→ Wallet connected? No → Show "Connect wallet" or current message
→ Wallet connected? Yes → Ownership check
→ Owns Cheese Scepter? Yes → Start riddle
→ Owns Cheese Scepter? No → "You need a Cheese Scepter"
```

---

## ⚠️ **CONSTRAINTS**

| Constraint | Notes |
|------------|-------|
| **Discord remains primary** | Wallet optional; DSPOINC, traits, profile stay Discord-based |
| **No breaking changes** | Players without wallet can still play (except gated riddle if we enforce) |
| **Mobile/VR** | Bridge tab must work on touch and in VR (or be hidden in VR) |
| **Testnet first** | All integration tested on NFT Bridges testnet before mainnet |

---

## 📞 **NFT BRIDGES CONTACTS**

- **Email:** ilan@nftbridges.xyz
- **Telegram:** @ilanklein
- **Docs:** https://docs.nftbridges.xyz
- **Website:** https://nftbridges.xyz

---

## 📋 **RELATED DOCUMENTATION**

| Doc | Path |
|-----|------|
| **To-Do Worklist** | `12.0/TECHNICAL_DOCUMENTATION/On Chain Bridges/NFT_BRIDGES_3D_GAME_INTEGRATION_TODO.md` |
| **Integration discussion** | `12.0/LAB_NOTES/2026/02_FEBRUARY/DAILY_NOTES/2026-02-02/ON_CHAIN_BRIDGES_INTEGRATION_DISCUSSION_2026-02-02.md` |
| **3D Game technical** | `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` |
| **Litepaper** | `12.0/TECHNICAL_DOCUMENTATION/On Chain Bridges/NFT Bridges Litepaper V.4.1_a.pdf` |

---

**Last Updated:** February 2, 2026
