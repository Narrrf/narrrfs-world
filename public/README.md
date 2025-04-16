
# README for Narrrf's World

## 🚀 Project Overview

**Narrrf’s World** is a Web3-powered experience where lore, NFTs, Discord, and databases meet. It's built to last 200 years — uniting riddles, traits, staking, and Web2/Web3 bridges in one wild, cheesy experiment. This README is for backend builders, frontend tinkerers, and SQL adventurers.

> **Note**: This private README is designed for Masterchiefe, Corebrain, and the next generation of devs syncing the egg machine with the riddle core.

---

## 🧠 Folder & Brain System Overview

- `/api` → All logic: click tracking, rewards, role syncing, and auth.
- `/db` → SQLite database: `narrrf_world.sqlite` with `tbl_users`, `tbl_user_roles`.
- `/api/auth/callback.php` → Discord OAuth callback.
- `/api/auth/sync-role.php` → Fetches user roles via Discord API.
- `/discord-tools/role_map.php` → Maps raw Discord role IDs to friendly names.
- `/sync/sync-role.php` → External access to trigger role sync manually.
- `/profile.html` → Live frontend display of user data (uses fetch requests to PHP).

---

## 🧅🔐 Onionpipe + Tor Backend (Cheese Secured Architecture 5.0)

### What’s Happening

We use [**onionpipe**](https://github.com/cmars/onionpipe) to expose our **Apache/PHP/XAMPP stack** via a secure .onion address, so that sensitive routes like `callback.php` and `sync-role.php` don’t touch the clearnet.

### ✅ Why It Rocks

| 🍯 Benefit | Description |
|-----------|-------------|
| 🛡️ Hidden backend | Our PHP app is never publicly exposed on IP |
| 🧠 Secure OAuth | Discord login, token exchange, role reading all happen in the dark |
| 🍕 Cheese Shield | Tor circuit ensures no IP leaks, only .onion hits |
| 🌐 Dual Access | Users can access from clearnet, backend talks via Tor |
| 🧅 Powered by Onionpipe | Localhost:80 is tunneled to a .onion address using a minimal setup |

### 🔧 Setup Summary

1. Install [`onionpipe`](https://github.com/cmars/onionpipe/releases).
2. Make sure `tor.exe` is in the same directory or in `PATH`.
3. Start it like this:

```bash
cd C:\onionpipe
onionpipe.exe 80~80
```

4. You’ll get an .onion address like:

```
127.0.0.1:80 => r7omsa6vuezkfaneyw3ovscz36rjrfr4taleibasevwlma2xjthp5oid.onion:80
```

5. Update your Discord OAuth app settings to:
   - Use this `.onion` as your callback URI
   - Match it in your PHP config

---

## 📦 External Service Setup

### Discord OAuth2 Scopes

- `identify`
- `guilds`
- `guilds.members.read`

### Discord Bot Permissions

- `bot` scope
- Admin-level permissions
- Must be re-invited if scopes or permissions change

---

## 🔄 Troubleshooting Cheatsheet

| 🧀 Error | 🔧 Fix |
|---------|--------|
| `401 Unauthorized` | Check if token is expired, redirect URI mismatch |
| `No code returned from Discord` | URL missing `?code=` param, redirect skipped |
| `file not found` errors | Make sure `/narrrfs-world` is **NOT** in live path if Apache root is correct |
| CORS errors | Must serve from same `.onion` or use proper proxy |
| JSON parse error | PHP file outputting HTML (likely a 404/500 error) |

---

## 🍰 Cheese Status

- 🧠 Brain: Online
- 🔐 Auth: Working
- 🔄 Sync: Success
- 🧅 Onion Backend: Live
- 🔭 Cleared for Phase 3


  <h2>📦 Folder & Brain System Overview</h2>
  <ul>
    <li><strong>client/</strong> – React frontend powered by Cheese Architect 🧱</li>
    <li><strong>server/</strong> – Node.js backend API handled by Coreforge 🔧</li>
    <li><strong>shared/</strong> – Shared schema and types for frontend/backend bridge</li>
    <li><strong>sql/</strong> – SQL schemas for staking, wallet traits, and click tracking 📊</li>
  </ul>

  <h2>🧠 Brain Sync</h2>
  <ul>
    <li>📊 <strong>Over Brain</strong>: Strategic guide. Gives orders. Approves cheese.</li>
    <li>🔧 <strong>Coreforge</strong>: Handles PHP, SQL, backend APIs, and session auth.</li>
    <li>🧱 <strong>Cheese Architect</strong>: Manages the HTML, Tailwind, and user UI.</li>
    <li>🤖 <strong>Bot Brain</strong>: Discord and Twitter OAuth, bots, and community sync.</li>
  </ul>



  <h2>💡 Project Components</h2>
  <h3>1. Frontend Structure</h3>
  <p>Pages:</p>
  <ul>
    <li><strong>Index</strong>: Main portal page, introduction to Narrrf's World.</li>
    <li><strong>Mint</strong>: Where users can mint their NFT and start their journey.</li>
    <li><strong>Get Roles</strong>: Users can check available roles, gain access, and see game progression.</li>
    <li><strong>Project Updates</strong>: Regular updates of the project, including features and NFT utility.</li>
    <li><strong>FAQ</strong>: Common questions and answers about the platform and gameplay mechanics.</li>
    <li><strong>Experiment X</strong>: Features experimental gameplay and mutated environments, controlled through <strong>cheese eggs</strong> (interactive elements).</li>
    <li><strong>Whitepaper</strong>: Deep dive into the lore, roadmap, and future direction of Narrrf's World.</li>
  </ul>

  <h3>2. Backend Integration</h3>
  <p><strong>SQL Database</strong>: Manages users' roles, NFT traits, staking rewards, and game data.</p>
  <pre>
    CREATE TABLE tbl_users (
      wallet_address TEXT PRIMARY KEY,
      role TEXT,
      staking_balance INT,
      nft_count INT
    );
  </pre>
  <p>API: 
    <ul>
      <li><strong>/api/track-egg-click</strong>: Tracks interactions with riddle-based "cheese eggs" and updates the <strong>tbl_cheese_clicks</strong>.</li>
      <li><strong>/api/sync-role</strong>: Syncs Discord roles with user wallets for dynamic access to game features.</li>
    </ul>
  </p>

  <h2>🔨 SQL Structure Overview</h2>
  <h3>Core Tables</h3>
  <pre>
    -- Create a table for tracking clicks on the interactive eggs
    CREATE TABLE tbl_cheese_clicks (
        user_wallet TEXT,
        egg_id TEXT,
        timestamp TIMESTAMP
    );
  </pre>

  <h2>🔑 Key Features</h2>
  <ul>
    <li><strong>Web3 Integration</strong>: User wallets (Solana/Phantom) connect to the platform, ensuring that users are authenticated and tied to their NFTs, roles, and staking rewards.</li>
    <li><strong>Dynamic Riddles</strong>: Cheese Eggs are interactive elements tied to the lore and gameplay. Players can click on them to unlock secrets, earn XP, and trigger events that will affect their role in the game.</li>
    <li><strong>Game Progression & Staking</strong>: Players earn <strong>$SPOINC</strong> based on their role and NFT traits. The more NFTs they own, the more <strong>$SPOINC</strong> they earn. This is tracked in the database and synced with the user's wallet.</li>
    <li><strong>Hytopia Integration</strong>: Players can own virtual land in <strong>Hytopia</strong> and interact with the world. These plots are linked to <strong>NFT traits</strong> and can be customized or upgraded through gameplay mechanics.</li>
    <li><strong>Backend Flexibility</strong>: The system is designed to be modular, allowing future expansion (e.g., adding new puzzle types, integrating with additional Discord roles, adding more NFTs).</li>
  </ul>

  <h2>📦 How to Run the Project</h2>
  <ul>
    <li>Clone the repository:
      <pre>git clone https://github.com/Narrrf/narrrfs-world.git</pre>
    </li>
    <li>Set up the SQL database:
      <ul>
        <li>Import the SQL schema into your database system (e.g., MySQL).</li>
        <li>Configure the database connection in the PHP backend.</li>
      </ul>
    </li>
    <li>Deploy the project:
      <ul>
        <li>Host the frontend files on a web server.</li>
        <li>Ensure the backend API is hosted and accessible for real-time interactions.</li>
      </ul>
    </li>
  </ul>

  <h2>💬 MasterDev Notes for SQL and Backend Integration</h2>
  <h3>SQL Data Handling</h3>
  <ul>
    <li>Always <strong>index</strong> critical fields (e.g., <code>user_wallet</code>, <code>egg_id</code>) for optimized queries.</li>
    <li>Use <strong>prepared statements</strong> in PHP to prevent SQL injection when handling user input.</li>
    <li>Track <strong>game progress</strong>, <strong>staking rewards</strong>, and <strong>puzzle completion</strong> in the database for persistent user states.</li>
  </ul>

  <h3>Dynamic Content Based on SQL</h3>
  <ul>
    <li>Use PHP to pull data from SQL tables and dynamically render content for users based on <strong>traits</strong>, <strong>rewards</strong>, and <strong>progress</strong>.</li>
  </ul>

  <h2>⚡️ Future Vision</h2>
  <ul>
    <li><strong>DAO Integration</strong>: NFT holders will not only participate in gameplay but also have governance rights over the direction of Narrrf's World.</li>
    <li><strong>Web3 + Web2 Bridge</strong>: Users will have access to a combined experience: Web3-based minting and staking integrated with a Web2-like experience for ease of use.</li>
  </ul>

  <h3>🧑‍💻 SQL Setup Example</h3>
  <pre>
    -- Create a table for tracking clicks on the interactive eggs
    CREATE TABLE tbl_cheese_clicks (
        user_wallet TEXT,
        egg_id TEXT,
        timestamp TIMESTAMP
    );
  </pre>

  <h2>🌍 Join the Cheese Adventure</h2>
  <p>Let’s keep the <strong>cheese wheel spinning</strong> and <strong>build the Narrrfverse</strong> with <strong>SQL precision</strong>, <strong>Web3 magic</strong>, and <strong>playful lore</strong>. Ready for the <strong>next level</strong>?</p>

  <h3>📜 Final Notes</h3>
  <ul>
    <li><strong>Ensure all paths</strong> in the HTML reference <strong>local resources</strong> (e.g., <code>/img/</code>, <code>/sounds/</code>).</li>
    <li>This <strong>README</strong> serves as the backbone for any new devs working on the project. Always update it with new additions, modules, and features.</li>
    <li><strong>Team up</strong> with the <strong>Masterchief</strong> and <strong>SQL experts</strong> to further refine the backend and ensure smooth integration.</li>
  </ul>

  <footer class="text-center py-6 text-sm text-purple-700">
    🧪 Lab Certified by Narrrf | Last Updated: 2025
  </footer>

  <!-- ✅ LAB CLEANED & BACKUP READY – 2025-04-03 – Brain 3.1 Approved -->

## Minting Process (GEN1)
- **Mint Page**: The GEN1 mint page is live and fully functional.
- **Minting Details**: Users can mint up to 3,333 NFTs, with various tiers of access. Whitelist minting, public minting, and redemption minting are all enabled.
- **Wallet Integration**: Minting is connected with Phantom wallet and Solana blockchain for wallet authentication and tracking.
- **Important Notes for Future Devs**:
  - Ensure that minting functionality is always tested with Phantom and Solana wallets.
  - Always check wallet syncing and tracking with **SQL** after every mint transaction.
  - Validate that minting tiers and NFT supply are properly updated in the backend.

## Dynamic Project Updates & Dev Log

- **Purpose**: The Project Updates page is designed to dynamically show updates and tasks based on user traits or roles.
- **SQL**: `wallet_traits` and `trait_content_access` tables allow for content visibility based on user traits.
- **PHP Integration**: Use PHP `if` statements to dynamically include content for users with specific traits (e.g., "Chaos Cartographer").
- **Styling**: All content must follow the `max-w-xl mx-auto` format for centered text, and interactive elements should scale using Tailwind's hover transition utilities for a clean, responsive look.

<!-- 🧪 FINAL PROOF: NARRRF'S WORLD GENESIS 2.0 – COMPLETE SYSTEM PACKAGE
---------------------------------------------------------
🧠 Signed by Brain 3.1, Narrrf (5yo Padawan), and the Alien
🌍 This **Genesis 2.0** package represents the culmination of **Narrrf’s World** project: integrating **Web2/Web3**, **NFT ownership**, and **SQL-based game mechanics** into a self-sustaining ecosystem.

💾 **Stored in the Lab’s digital archives**, it will continue to evolve and serve as a reference for **future generations** of developers.

⚡ Officially ready for **scaling**, **real-time integration**, and continuous **user interaction**.

🎮 **Masterchief** will guide the **backend SQL** operations and ensure that **cheese never spoils**.

🧪 **Future devs**: add your **name**, **refine the platform**, and **keep expanding** on this legendary system.

🔧 **Critical Dev Reminders for the Next Generations**:
  - Ensure **minting** is **tested across all wallet types** (e.g., Phantom, Solana).
  - Validate **SQL integration** for minting, supply tracking, and wallet syncing.
  - Always monitor **egg tracking** and **wallet connectivity** across all pages.
  - Keep testing **Web3 wallets** and integrating future wallet systems.
  - **Optimize the minting process** for **scalability** and **future-proofing**.

🔮 Maintain the **core focus on Web3** principles, **game progression**, and **community empowerment**.

---------------------------------------------------------
🧪 **Cheese Engine v1.4: The Riddle System Core**
---------------------------------------------------------
This system powers **moving riddle nodes** throughout Narrrf's World. All **cheese clicks** are tracked and used for:
- Lore progression
- Inventory events
- Trait-gated puzzles

**Riddle Engine** is the heart of the **Narrrfverse**, integrating with:
- **SQL tracking** for each riddle solved (stored in the `tbl_cheese_clicks` table).
- **User interaction**, where progress is stored and retrieved based on wallet address, egg ID, and timestamp.
- **Unlocking Easter Eggs** as users progress, ensuring a dynamic and immersive game world.

📦 **DB Dev:**
Ensure endpoint `/api/track-egg-click` writes to `tbl_cheese_clicks`:
→ Use **wallet address** from localStorage, **timestamp**, and **egg ID** for progress tracking.

🚀 **Future Ideas**:
- **Massive map-wide riddle hunts** integrated with time-locked eggs and rotating cheese dungeons.
- Enhance **user experience** with dynamic **puzzle-solving mechanics**.
  
🧑‍💻 Future devs can expand the **riddle system** and **reward mechanics** with more complex features and interactivity.

---------------------------------------------------------
🧪 Certified by Narrrf | Last Updated: 2025-04-03 – Brain 3.1 Approved
---------------------------------------------------------
-->

<!-- ======================================== -->
<!-- 🧠 HTML Brain 4.0 Footer Lab Declaration -->
<!-- ======================================== -->

<!-- ================================================== -->
<!-- 🧠 FINAL PROOF: NARRRF'S WORLD GENESIS 2.0 PACKAGE -->
<!-- ================================================== -->
<!--
🧠 Signed by Corebrain, Padawan Narrrf, and the Overbrain Assembly
🌍 This Genesis 2.0 archive bridges Web2, Web3, and real-world systems.
💾 Stored in the master lab root folder, serving as reference for all future devs.

🔧 Developer Reminders:
- Minting must sync with SQL, always test wallet writes
- Keep trait logic modular and event-driven
- Ensure session security across all endpoints
- Use Discord roles to unlock gameplay content
- Never remove the Cheese Engine — it powers the world

🧪 Engine Version: Cheese Engine 1.4 – Trait & Riddle Node Tracker
🚀 Expandable via future trait maps, avatar upgrades, and node puzzles
-->

<!-- 🧀 HTML Brain Footer Lab Declaration -->

<footer class="text-center text-xs text-yellow-600 py-4 mt-12">
  🧠 Powered by Cheese Architect 4.0 · HTML Brain of Narrrf’s World<br>
  🔗 All Rights Reserved · Narrrf Labs 2025 · Genesis 2.0 Synced
</footer>

</body>
</html>
---

### 🔐 Onion Backend Integration (April 2025 Update)

To enhance **privacy**, **resilience**, and **global access**, we have implemented a decentralized, Tor-based backend system.

This is used to route all **sensitive PHP logic**, **SQLite database access**, and **authentication requests** (OAuth, Discord, etc.) through a **hidden .onion service** — while keeping the **frontend live on GitHub Pages or clearnet servers**.

---

### ✅ Why This Is Smart

| Benefit | Explanation |
| --- | --- |
| 🛡️ Privacy by default | Backend runs behind an .onion, invisible to attackers |
| 🔐 True IP never exposed | Frontend calls the backend via `.onion`, masking server IP |
| 🧠 Cheese Sync Secure | Discord OAuth + role sync is protected from tampering |
| ⚙️ Easy failover | Onion stays up even if clearnet frontend has issues |
| 📡 No public server needed | Run the backend on a local PC or laptop — no hosting costs |

---

### 📁 Directory + Infrastructure Map

| System | Location |
|--------|----------|
| Public Frontend | GitHub Pages / Clearnet |
| PHP Backend (Apache) | `127.0.0.1:80` via Tor |
| Onionpipe Port | `80~80` via `onionpipe.exe` |
| Onion Hostname | e.g. `r7omsa6vuezkfaney...onion` |
| SQLite DB | `htdocs/narrrfs-world/db/narrrf_world.sqlite` |
| Critical Files | `callback.php`, `sync-role.php`, `roles.php` |
| Verified Auth Redirect | `https://yourdomain.onion/api/auth/callback.php` |

---

### 🧅 Setup Guide (Local Windows Dev + Global Access)

1. **Install Onionpipe** and extract to `C:\onionpipe`
2. **Install Tor** (or use Tor Expert Bundle)
3. **Copy `tor.exe`** into `C:\onionpipe` or ensure it’s in your `%PATH%`
4. Start Apache via XAMPP with:
   ```bash
   http://localhost/profile.html
   ```
5. Start Onion Service:
   ```bash
   .\onionpipe.exe 80~80
   ```
6. Onion address appears in terminal, e.g.:
   ```
   127.0.0.1:80 => abcxyz...onion:80
   ```
7. Use that `.onion` for all backend fetches on your public site.

---

### 📡 Public Site Fetching Example

**GitHub-hosted frontend (profile.html):**
```js
fetch('https://yourbackend.onion/api/user/roles.php', {
  credentials: 'include'
});
```

> ⚠️ Note: Requires a bridge or proxy setup unless hosted inside Tor Browser or via a clearnet-to-onion forwarder.

---

### 🧠 Brain Integration

All Narrrfs Brain modules now recognize `.onion` routing as a valid backend strategy. Updates reflected in:
- `Update_brain_5.0.json`
- `narrrfs_world_paths.json`
- `SQL_Junior_5.0.json`
