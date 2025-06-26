
# @Project-overview-NarrrfsWorld.v10.0.md

🧠 Certified for Genesis 10.0 · Issued by Update Brain 10.0 · Sealed in Vault #003

---

## 🧠 ULTRA CODE DIRECTIVES
- ✅ ALWAYS write clean, modular, maintainable, and **version-stamped** code
- ✅ ALWAYS use **LLM-specific function names** (`renderCheeseGate()`, `logTraitClick()`)
- ✅ ALWAYS document scroll-based logic explicitly
- 🚫 NEVER modify code outside your scroll, zone, or LLM directive
- 🚫 NEVER overwrite `/api/` routes without explicit approval by Corebrain + Update Brain
- ✳️ Tag suggestions clearly: `// suggest_update → reason` and WAIT for approval

## 🔍 SYSTEM SNAPSHOT
- 🧬 Trait-driven puzzles, NFT metadata integration, dynamic rewards
- 🧾 Discord OAuth & Wallet Integration via `/api/auth/callback.php`
- 🔄 DOM ↔ PHP ↔ SQLite trait synchronization
- 🐳 Deployment via Docker on Render; local dev with XAMPP

## 🧩 LLM ROLES & DOMAINS
| Domain            | LLM                 | API Ref / Trigger                         |
|-------------------|---------------------|-------------------------------------------|
| Puzzle Logic      | Riddle Brain 10.0   | `/api/track-egg-click`                    |
| DOM Rendering     | Cheese Architect 10.0 | `/api/user/traits.php`                  |
| Backend & API     | Coreforge 10.0      | `/sync-role.php`                          |
| Schema Manager    | SQL Junior 10.0     | `narrrf_world.sqlite`                     |
| Wallet & SDK      | Hytopia Integrator 10.0 | `onTraitUnlock`, `onWalletConnect`     |
| Comms & Lore      | Social Brain 10.0   | Broadcast Queue + event hooks             |
| Scroll Logs       | Update Brain 10.0   | `trigger_confirmed.json` + archive paths  |
| Runtime Engine    | Cursor LLM 10.0     | DOM trait watchers, manifest synchronization |
| NFT Mint & Metadata| NFT Architect 10.0  | Trait-driven mint logic                   |
| Overbrain         | Corebrain 10.0      | Final scroll authority & trait validation |

## 📜 TRAIT + SCROLL RULES
- Trait naming convention: `CHEESE_<ZONE>_<ACTION>`
- DOM trait activation example:
```
// zone:cheese_shrine_start
// trait_trigger → CHEESE_SHRINE_EGGCLICK
```
- Ensure all traits explicitly map to DOM/UI elements, API calls, or DB schema

## 🧬 TRAIT + EVENT FLOW
```javascript
TraitManager.has("CHEESE_SHRINE_EGGCLICK")  
→ /api/track-egg-click  
→ tbl_user_traits (SQLite)  
→ DOM: `#core-altar-glow` visibility  
→ Log: `trigger_confirmed.json`
```

## 🧱 ENVIRONMENT PATHS
- **Local (XAMPP):** `C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite`
- **Render Deployment:** `/var/www/html/db/narrrf_world.sqlite`
- **PHP:** `$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';`

## 🌐 SDK & CURSOR GUIDELINES
- Validate SDK hooks (`onTraitUnlock`, `onWalletConnect`) through Corebrain
- Cursor LLM scroll bindings must be authorized explicitly by Update Brain
- `Cursor.config.json` remains sealed unless explicitly authorized

## 📂 FOLDER STRUCTURE
```
/api/
 ├── user/
 │   └── traits.php
 ├── auth/
 │   └── callback.php
 └── track-egg-click.php
/db/
 └── narrrf_world.sqlite
/public/
 ├── index.html
 ├── profile.html
 ├── manifest.js
 └── render_gate.js // Puzzle visibility logic
/scroll_journal/
 ├── ghosts/
 ├── trigger_confirmed.json
 └── vault_sync_manifest_10.0.mdc
```

## 📦 DEPLOYMENT RULES
- All scroll files must include:
  - `"version"` (e.g., `"10.0"`)
  - `"certified_by"` (Corebrain + Update Brain)
  - `"update_date"` (UTC timestamp)
- Store logs in `/scroll_journal/`, `/trigger_confirmed.json`, `/LLM_*.json`

## 🔐 VAULT SYNC FRAMEWORK v10.0
- Include in `vault_sync_manifest_10.0.mdc`:
  - Scroll hash, trait interaction, DOM region IDs, reward/lore mappings
- Linked via trait echo ID to `trigger_confirmed.json`

## 🌐 NODE & DOM DEEP LINKING
- Track scroll logs, trait activation, and Vault sync on each DOM node
- Validate DOM visibility through Cursor LLM trait-triggered renders

## 🧾 NFT METADATA RULES
- Traits dynamically lock upon mint (`VAULT_003_TRIGGER`)
- Utilize certified templates (`Template-Metadata10.0.json`)
- Secure metadata via Council-validated cryptographic hashes

## 🧮 DATABASE IMMUTABILITY RULES
- Columns `trait_origin`, `echo_timestamp` required
- Encrypt reward paths with validated cryptographic trait-origin hashes

## 🔭 FUTURE-PROOF SYSTEM EXPANSION (GENESIS 10.0+)
- Traits support mutation tracking
- DOM elements react dynamically to trait combinations
- Puzzle gates preserve states through scroll reboots

## 🧠 FINAL PROPHECY
> “The scroll remembers every click, every trait, every choice. Genesis 10.0 is our reality—forever evolving.”  
— Update Brain 10.0, Sealed & Logged
