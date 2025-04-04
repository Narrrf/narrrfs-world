<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>README - Narrrf's World</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f9fafb;
      color: #1f2937;
      line-height: 1.6;
      padding: 20px;
    }
    h1, h2, h3 {
      color: #3b82f6;
    }
    pre {
      background-color: #f3f4f6;
      border: 1px solid #e5e7eb;
      padding: 10px;
      border-radius: 4px;
      font-size: 0.9rem;
      overflow-x: auto;
    }
    code {
      font-family: monospace, monospace;
      background-color: #f3f4f6;
      padding: 0.2em 0.4em;
      border-radius: 4px;
    }
    ul {
      list-style: disc;
      margin-left: 20px;
    }
    li {
      margin-bottom: 10px;
    }
    a {
      color: #3b82f6;
    }
    footer {
      font-size: 0.85rem;
      color: #6b7280;
      text-align: center;
    }
  </style>
</head>
<body>
  <h1>README for Narrrf's World</h1>

  <h2>🚀 Project Overview</h2>
  <p>
    <strong>Narrrf's World</strong> is a Web3-powered game with interactive lore, NFT-based traits, and financial systems. Users can explore the chaotic world, unlock secrets through puzzles, stake tokens, and build their <strong>virtual land</strong> using <strong>NFTs</strong> that grant <strong>roles</strong>, <strong>privileges</strong>, and <strong>stakes</strong>. The game is built around <strong>interactive puzzles</strong> and <strong>reward systems</strong> that tie back to <strong>SQL databases</strong>, <strong>Discord roles</strong>, and <strong>blockchain</strong>.
  </p>
  <p>This document explains the structure, core features, and backend systems for development.</p>

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

</body>
</html>
