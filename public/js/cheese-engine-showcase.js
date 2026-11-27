(function () {
  "use strict";

  const root = document.getElementById("cheese-engine-showcase");
  if (!root) return;

  const SVG_NS = "http://www.w3.org/2000/svg";
  const XLINK_NS = "http://www.w3.org/1999/xlink";

  const DEPARTMENTS = {
    command: {
      iconCode: "👑 HEAD CHEESEMAKERS",
      cheeseName: "Head Cheesemaker's Throne",
      tagline: "Vision, final taste-testing & ecosystem launch approval"
    },
    ops: {
      iconCode: "🧀 OPS WHEEL",
      cheeseName: "Grand Cheese Command",
      tagline: "Keeps daily community ops, portals & releases spinning smoothly"
    },
    creamery: {
      iconCode: "🥛 DATA CREAMERY",
      cheeseName: "Dairy Pipeline & Vault Refinery",
      tagline: "Guards high-speed APIs, scores & rock-solid game data pipelines"
    },
    cellar: {
      iconCode: "🧠 BRAIN CELLAR",
      cheeseName: "The Aging Cellar & Memory Vault",
      tagline: "Preserves canon lore, squad memory & cross-agent coordination"
    },
    intel: {
      iconCode: "🧬 INTEL LAB",
      cheeseName: "Gemini Cheese DNA & Intel Lab",
      tagline: "Sequences architecture patterns, visual maps & ecosystem strategy"
    },
    arena: {
      iconCode: "⚔️ ARENA",
      cheeseName: "Mousetrap Arena & Riddle Bakery",
      tagline: "Powers 9-League MouseFight battles, riddles & arcade challenges"
    },
    cheddar: {
      iconCode: "🪙 CHEDDAR VAULT",
      cheeseName: "Golden Cheddar Treasury & Mint",
      tagline: "Audits dSPOINC ledgers, SPOINC bridge flow & reward drops"
    },
    lab: {
      iconCode: "🧪 GENETIC LAB",
      cheeseName: "Genetic Cheese & Staking Incubator",
      tagline: "Upgrades permanent Genesis Traits, Lab Store gear & Stake Freezer"
    },
    foundry3d: {
      iconCode: "🧊 3D FOUNDRY",
      cheeseName: "3D Cheese-Verse Foundry",
      tagline: "Builds real-time Three.js 3D worlds, Genesis avatars & loot chests"
    },
    relay: {
      iconCode: "📡 SQUEAK RELAY",
      cheeseName: "Squeak Relay & Bridge Pier",
      tagline: "Connects Discord commands, role perks & verified partner worlds"
    },
    board: {
      iconCode: "📣 CHEESE BOARD",
      cheeseName: "The Community Cheese Board",
      tagline: "Coordinates announcements, events & community storytelling"
    },
    vintage: {
      iconCode: "🗓️ VINTAGE CELLAR",
      cheeseName: "Seasonal Vintage & Reset Cellar",
      tagline: "Archives Hall-of-Fame legends & launches fresh seasonal ladders"
    },
    reserve: {
      iconCode: "🔒 RESERVE CAVE",
      cheeseName: "Secret Aging Cave (Coming Soon)",
      tagline: "Top-secret future 3D adventure aging quietly until launch day"
    }
  };

  const NODES = {
    "narrrf-team": {
      id: "narrrf-team",
      badge: "COMMAND",
      shortLabel: "Narrrf + Team",
      name: "Narrrf + Team — Head Cheesemakers",
      deptKey: "command",
      roleLabel: "Founders & Ecosystem Direction",
      summary: "Sets the Narrrf's World vision, coordinates the 5-head Cheese Council, and taste-tests every new feature before it reaches players.",
      ctaLabel: "🧠 Explore Nerd Lab",
      ctaHref: "nerd-lab.html",
      x: 620, y: 42, w: 248, h: 48, color: "var(--ce-gold)"
    },
    "role-1": {
      id: "role-1",
      badge: "#1",
      shortLabel: "Operations 3.0",
      name: "Operations 3.0 — Wheel Dispatcher",
      deptKey: "ops",
      roleLabel: "Council Head • Daily Fleet Dispatch",
      summary: "Directs day-to-day missions across the website, arcade games, rewards, and community events so every squad rolls in sync.",
      ctaLabel: "🎮 Player Hub",
      ctaHref: "profile.html",
      x: 125, y: 145, w: 194, h: 56, color: "var(--ce-emerald)"
    },
    "role-16": {
      id: "role-16",
      badge: "#16",
      shortLabel: "API & Vault",
      name: "Chief Dairy Engineer — APIs & Data Vault",
      deptKey: "creamery",
      roleLabel: "Council Head • Core Pipeline Authority",
      summary: "Keeps every leaderboard save, match check, login flow, and reward claim pasteurized, reliable, and tamper-resistant.",
      ctaLabel: "🧠 System Overview",
      ctaHref: "nerd-lab.html",
      x: 372, y: 145, w: 194, h: 56, color: "var(--ce-violet)"
    },
    "role-17": {
      id: "role-17",
      badge: "#17",
      shortLabel: "Cheese Brain",
      name: "Master Archivist — Cheese Brain Cellar",
      deptKey: "cellar",
      roleLabel: "Council Head • Canonical Memory Cellar",
      summary: "Ages Narrrf's World documentation, rules, and milestone history so all 28 specialist agents never forget how the universe fits together.",
      ctaLabel: "📜 Read Whitepaper",
      ctaHref: "whitepaper.html",
      x: 620, y: 145, w: 194, h: 56, color: "var(--ce-orange)"
    },
    "role-19": {
      id: "role-19",
      badge: "#19",
      shortLabel: "3D Leader",
      name: "3D Foundry Architect — World Leader",
      deptKey: "foundry3d",
      roleLabel: "Council Head • 8-Agent 3D Division",
      summary: "Leads the 8-agent Three.js 3D Cheese-Verse Foundry building explorable riddle levels, mobile controls, Genesis avatars, and reward chests.",
      ctaLabel: "🧊 Enter 3D World",
      ctaHref: "3d-riddle.html",
      x: 868, y: 145, w: 194, h: 56, color: "var(--ce-coral)"
    },
    "gemini-intel": {
      id: "gemini-intel",
      badge: "GEMINI",
      shortLabel: "Gemini Intel",
      name: "Gemini Cheese DNA & Intelligence Head",
      deptKey: "intel",
      roleLabel: "Council Head • Intelligence & System Maps",
      summary: "Maps complex cross-system DNA flows, builds interactive Cheese Engine visualizers, and crafts strategic R&D blueprints with Narrrf + Team.",
      ctaLabel: "🧠 Nerd Lab Guide",
      ctaHref: "nerd-lab.html",
      x: 1115, y: 145, w: 198, h: 56, color: "var(--ce-cyan)"
    },
    "role-2": {
      id: "role-2",
      badge: "#2",
      shortLabel: "MF Leagues",
      name: "MouseFight Leagues Ref & Matchmaker",
      deptKey: "arena",
      roleLabel: "Arena Specialist • 9 Leagues",
      summary: "Runs Season 17 MouseFight brackets from Crumb to Ultra Champion, daily league loops, fighter cooldowns, and glorious podium finishes.",
      ctaLabel: "⚔️ Enter MouseFight",
      ctaHref: "mousefights.html#leagues",
      x: 88, y: 280, w: 126, h: 50, color: "var(--ce-emerald)"
    },
    "role-3": {
      id: "role-3",
      badge: "#3",
      shortLabel: "MF Bot",
      name: "Arena Announcer & Bot Marshal",
      deptKey: "arena",
      roleLabel: "Arena Specialist • Live Lobbies",
      summary: "Handles Discord fight lobbies, instant battle buttons, and crowd-pleasing play-by-play cheese commentary in real time.",
      ctaLabel: "⚔️ View Battles",
      ctaHref: "mousefights.html",
      x: 222, y: 280, w: 126, h: 50, color: "var(--ce-emerald)"
    },
    "role-6": {
      id: "role-6",
      badge: "#6",
      shortLabel: "Riddle Brain",
      name: "Cheese Riddle & Quest Master",
      deptKey: "arena",
      roleLabel: "Bakery Specialist • Puzzles & Hunts",
      summary: "Inventive architect behind Narrrf's secret Cheese Hunts, clever brain-teasers, and surprise arcade triggers across the site.",
      ctaLabel: "🎮 Arcade Games",
      ctaHref: "profile.html",
      x: 356, y: 280, w: 126, h: 50, color: "var(--ce-emerald)"
    },
    "role-9": {
      id: "role-9",
      badge: "#9",
      shortLabel: "Glyph Memory",
      name: "Glyph Memory Dojo Keeper",
      deptKey: "arena",
      roleLabel: "Bakery Specialist • Memory Matrix",
      summary: "Polishes neon symbol cascades, streak multipliers, and crisp pattern memory gameplay for high-score chasers.",
      ctaLabel: "🏆 High Scores",
      ctaHref: "leaderboard.html",
      x: 490, y: 280, w: 126, h: 50, color: "var(--ce-emerald)"
    },
    "role-4": {
      id: "role-4",
      badge: "#4",
      shortLabel: "Ledger Expert",
      name: "Chief Cheddar Accountant",
      deptKey: "cheddar",
      roleLabel: "Treasury Specialist • Balance Integrity",
      summary: "Watches every single dSPOINC crumb so arena prizes, store purchases, and leaderboard totals balance down to the last bite.",
      ctaLabel: "🏆 Economy Leaders",
      ctaHref: "leaderboard.html",
      x: 624, y: 280, w: 126, h: 50, color: "var(--ce-violet)"
    },
    "role-11": {
      id: "role-11",
      badge: "#11",
      shortLabel: "Reward Drops",
      name: "Reward Chamber Quartermaster",
      deptKey: "cheddar",
      roleLabel: "Treasury Specialist • Crate & Perk Drops",
      summary: "Coordinates verified reward deliveries, milestone prizes, and shiny Chamber crates earned by active players and holders.",
      ctaLabel: "🎁 Reward Chamber",
      ctaHref: "profile.html#profile-chest-section",
      x: 88, y: 385, w: 126, h: 50, color: "var(--ce-violet)"
    },
    "role-18": {
      id: "role-18",
      badge: "#18",
      shortLabel: "SPOINC Bridge",
      name: "SPOINC ↔ dSPOINC Alchemist",
      deptKey: "cheddar",
      roleLabel: "Treasury Specialist • Swap Lab",
      summary: "Operates verified SPOINC gateway paths and Swap Lab conversion routes bridging ecosystem tokens with in-world cheese utility.",
      ctaLabel: "💱 Swap Lab",
      ctaHref: "swap-lab.html",
      x: 222, y: 385, w: 126, h: 50, color: "var(--ce-violet)"
    },
    "role-5": {
      id: "role-5",
      badge: "#5",
      shortLabel: "Lab Shop",
      name: "Lab Store & Genetic Outfitter",
      deptKey: "lab",
      roleLabel: "Lab Specialist • Inventory & Gear",
      summary: "Curates the Lab Store catalog, Reward Chamber crates, boosters, and Genetic items you can equip on your Genesis roster.",
      ctaLabel: "🛒 Visit Lab Store",
      ctaHref: "lab-store.html",
      x: 356, y: 385, w: 126, h: 50, color: "var(--ce-orange)"
    },
    "role-13": {
      id: "role-13",
      badge: "#13",
      shortLabel: "Stake / Freezer",
      name: "Cryo-Cheddar Vault Warden",
      deptKey: "lab",
      roleLabel: "Lab Specialist • Stake Lab & Freezer",
      summary: "Guards Stake Lab positions and the Genesis Mouse Freezer, protecting long-term commitments and staker milestones.",
      ctaLabel: "🧊 Open Stake Lab",
      ctaHref: "stake-lab.html",
      x: 490, y: 385, w: 126, h: 50, color: "var(--ce-orange)"
    },
    "role-15": {
      id: "role-15",
      badge: "#15",
      shortLabel: "Lab System",
      name: "Genesis DNA Geneticist",
      deptKey: "lab",
      roleLabel: "Lab Specialist • Permanent Traits",
      summary: "Tends Genesis Mouse DNA, permanent Traits, unlocked Abilities, and Lab training progression that outlasts every seasonal reset.",
      ctaLabel: "🧬 Enter Genesis Lab",
      ctaHref: "lab.html",
      x: 624, y: 385, w: 126, h: 50, color: "var(--ce-orange)"
    },
    "role-8": {
      id: "role-8",
      badge: "#8",
      shortLabel: "Partner Bridge",
      name: "Universe Alliance Navigator",
      deptKey: "relay",
      roleLabel: "Relay Specialist • Collaborations",
      summary: "Maintains friendly cross-universe bridges with Narrrf's World partner collections and shared community quests.",
      ctaLabel: "🤝 View Partners",
      ctaHref: "partners.html",
      x: 88, y: 495, w: 126, h: 50, color: "var(--ce-gold)"
    },
    "role-10": {
      id: "role-10",
      badge: "#10",
      shortLabel: "Discord Bot",
      name: "Discord Squeak Operator",
      deptKey: "relay",
      roleLabel: "Relay Specialist • Role Sync",
      summary: "Links your Discord profile, holder roles, slash commands, and community notifications directly to your web account.",
      ctaLabel: "🎭 Get Roles",
      ctaHref: "get-roles.html",
      x: 222, y: 495, w: 126, h: 50, color: "var(--ce-gold)"
    },
    "role-12": {
      id: "role-12",
      badge: "#12",
      shortLabel: "Social Crew",
      name: "Town Crier & Cheese Sommelier",
      deptKey: "board",
      roleLabel: "Community Specialist • Updates",
      summary: "Shares major patch notes, Friday event recaps, tournament highlights, and fresh community memes across the feed.",
      ctaLabel: "📣 Project Updates",
      ctaHref: "project-updates.html",
      x: 356, y: 495, w: 126, h: 50, color: "var(--ce-gold)"
    },
    "role-14": {
      id: "role-14",
      badge: "#14",
      shortLabel: "Season Master",
      name: "Season Rollover Timekeeper",
      deptKey: "vintage",
      roleLabel: "Vintage Specialist • Fresh Ladders",
      summary: "Archives Season legends safely into history vaults while launching clean Season 17 competitive leaderboards.",
      ctaLabel: "🏆 Season 17 Board",
      ctaHref: "leaderboard.html",
      x: 490, y: 495, w: 126, h: 50, color: "var(--ce-gold)"
    },
    "role-7": {
      id: "role-7",
      badge: "#7",
      shortLabel: "Secret Cave",
      name: "Top-Secret Reserve Explorer",
      deptKey: "reserve",
      roleLabel: "Future Frontier • Aging in Reserve",
      summary: "A classified future 3D cheese frontier resting quietly behind triple-wax cheddar seals until Narrrf + Team says it is tasting time.",
      ctaLabel: "📜 Roadmap Vision",
      ctaHref: "whitepaper.html",
      x: 624, y: 495, w: 126, h: 50, color: "var(--ce-cyan)",
      dashed: true
    },
    "role-20": {
      id: "role-20",
      badge: "#20",
      shortLabel: "3D Worlds",
      name: "3D World Sculptor & Framerate Wizard",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • Environments",
      summary: "Crafts glowing skyboxes, lush grass shaders, puzzle chambers, and silky 60fps performance inside the Three.js Cheese-Verse.",
      ctaLabel: "🧊 Launch 3D Riddle",
      ctaHref: "3d-riddle.html",
      x: 826, y: 280, w: 128, h: 50, color: "var(--ce-coral)"
    },
    "role-21": {
      id: "role-21",
      badge: "#21",
      shortLabel: "3D Mobile/UI",
      name: "Touch Joysticks & HUD Stylist",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • Mobile Controls",
      summary: "Tunes responsive on-screen thumbsticks, floating HUD controls, and crisp tooltips so desktop and phone explorers move alike.",
      ctaLabel: "🧊 Try 3D Controls",
      ctaHref: "3d-riddle.html",
      x: 966, y: 280, w: 128, h: 50, color: "var(--ce-coral)"
    },
    "role-22": {
      id: "role-22",
      badge: "#22",
      shortLabel: "3D Portal",
      name: "3D Portal Gatekeeper",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • Session Entry",
      summary: "Seamlessly brings your signed-in player identity right through the 3D portal doors without breaking flow.",
      ctaLabel: "🧊 Enter Portal",
      ctaHref: "3d-riddle.html",
      x: 1106, y: 280, w: 128, h: 50, color: "var(--ce-coral)"
    },
    "role-23": {
      id: "role-23",
      badge: "#23",
      shortLabel: "3D Genesis",
      name: "3D Genesis Avatar Mirror",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • Mouse Identity",
      summary: "Projects your verified Genesis Mouse identity cleanly into 3D space while keeping permanent ownership records safely anchored.",
      ctaLabel: "🐭 Strongest Mice",
      ctaHref: "strongest-genesis-mice.html",
      x: 826, y: 385, w: 128, h: 50, color: "var(--ce-coral)"
    },
    "role-24": {
      id: "role-24",
      badge: "#24",
      shortLabel: "3D Abilities",
      name: "3D NFT Trait Spark Weaver",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • RPG Effects",
      summary: "Translates your Genesis Mouse Traits and Lab Abilities into live speed bursts, jump boosts, and dazzling 3D in-world powers.",
      ctaLabel: "🧬 Lab Abilities",
      ctaHref: "lab.html",
      x: 966, y: 385, w: 128, h: 50, color: "var(--ce-coral)"
    },
    "role-25": {
      id: "role-25",
      badge: "#25",
      shortLabel: "3D Loadout",
      name: "Genetic Bag & 3D Loadout",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • Gear & Cosmetics",
      summary: "Lets you equip awesome Genetic items and gear inside the 3D scene while safeguarding your permanent vault inventory.",
      ctaLabel: "🛒 Genetic Catalog",
      ctaHref: "lab-store.html",
      x: 1106, y: 385, w: 128, h: 50, color: "var(--ce-coral)"
    },
    "role-26": {
      id: "role-26",
      badge: "#26",
      shortLabel: "3D Profile",
      name: "3D Stat & Badge Courier",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • Profile Sync",
      summary: "Streams your player rank, mouse stats, and progression overlays into the 3D world HUD in a single clean handshake.",
      ctaLabel: "🎮 Player Profile",
      ctaHref: "profile.html",
      x: 896, y: 495, w: 128, h: 50, color: "var(--ce-coral)"
    },
    "role-27": {
      id: "role-27",
      badge: "#27",
      shortLabel: "3D Chests",
      name: "3D Treasure Chest Artisan",
      deptKey: "foundry3d",
      roleLabel: "3D Specialist • Reward Chests",
      summary: "Polishes hidden 3D puzzle chests, golden opening particles, and future Reward Chamber bridge experiences.",
      ctaLabel: "🎁 Reward Chamber",
      ctaHref: "profile.html#profile-chest-section",
      x: 1044, y: 495, w: 128, h: 50, color: "var(--ce-coral)"
    }
  };

  const EDGES = [
    { from: "narrrf-team", to: "role-1", type: "direct" },
    { from: "narrrf-team", to: "role-16", type: "direct" },
    { from: "narrrf-team", to: "role-17", type: "direct" },
    { from: "narrrf-team", to: "role-19", type: "direct" },
    { from: "narrrf-team", to: "gemini-intel", type: "direct" },
    { from: "role-19", to: "role-20", type: "direct" },
    { from: "role-19", to: "role-21", type: "direct" },
    { from: "role-19", to: "role-22", type: "direct" },
    { from: "role-19", to: "role-23", type: "direct" },
    { from: "role-19", to: "role-24", type: "direct" },
    { from: "role-19", to: "role-25", type: "direct" },
    { from: "role-19", to: "role-26", type: "direct" },
    { from: "role-19", to: "role-27", type: "direct" },
    { from: "role-1", to: "role-16", type: "operational" },
    { from: "role-1", to: "role-2", type: "operational" },
    { from: "role-1", to: "role-3", type: "operational" },
    { from: "role-1", to: "role-6", type: "operational" },
    { from: "role-1", to: "role-9", type: "operational" },
    { from: "role-1", to: "role-4", type: "operational" },
    { from: "role-1", to: "role-11", type: "operational" },
    { from: "role-1", to: "role-18", type: "operational" },
    { from: "role-1", to: "role-5", type: "operational" },
    { from: "role-1", to: "role-13", type: "operational" },
    { from: "role-1", to: "role-15", type: "operational" },
    { from: "role-1", to: "role-8", type: "operational" },
    { from: "role-1", to: "role-10", type: "operational" },
    { from: "role-1", to: "role-12", type: "operational" },
    { from: "role-1", to: "role-14", type: "operational" },
    { from: "role-1", to: "role-7", type: "operational" },
    { from: "gemini-intel", to: "role-17", type: "serializer" }
  ];

  const TOUR_SCENARIOS = [
    {
      label: "🌙 Quiet Cellar Heartbeat",
      group: "calm-cellar",
      working: ["role-17"],
      recent: ["narrrf-team"],
      focus: "role-17",
      minMs: 9500,
      maxMs: 14500
    },
    {
      label: "👑 Morning Taste-Test Check",
      group: "command",
      working: ["narrrf-team", "role-1"],
      recent: ["role-16"],
      focus: "narrrf-team",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "⚔️ Arena Bracket Matchmaking",
      group: "arena",
      working: ["role-1", "role-2"],
      recent: ["role-3"],
      focus: "role-2",
      minMs: 8500,
      maxMs: 13000
    },
    {
      label: "🥊 Live Fight Lobby Relay",
      group: "arena-bot",
      working: ["role-1", "role-3"],
      recent: ["role-10"],
      focus: "role-3",
      minMs: 8500,
      maxMs: 12500
    },
    {
      label: "🧩 Secret Cheese Hunt Spark",
      group: "bakery",
      working: ["role-1", "role-6"],
      recent: ["role-16"],
      focus: "role-6",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "🔮 Glyph Dojo Streak Pulse",
      group: "glyph",
      working: ["role-1", "role-9"],
      recent: ["role-4"],
      focus: "role-9",
      minMs: 8500,
      maxMs: 12500
    },
    {
      label: "🥛 Dairy API Health Check",
      group: "creamery",
      working: ["narrrf-team", "role-16"],
      recent: ["role-1"],
      focus: "role-16",
      minMs: 9500,
      maxMs: 14000
    },
    {
      label: "🪙 Cheddar Ledger Balance Audit",
      group: "cheddar",
      working: ["role-1", "role-4", "role-16"],
      recent: [],
      focus: "role-4",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "🎁 Chamber Crate Drop",
      group: "drops",
      working: ["role-1", "role-11"],
      recent: ["role-5"],
      focus: "role-11",
      minMs: 8500,
      maxMs: 13000
    },
    {
      label: "💱 SPOINC ↔ dSPOINC Alchemist",
      group: "swap",
      working: ["role-1", "role-18"],
      recent: ["role-4"],
      focus: "role-18",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "🛒 Lab Store Shelf Restock",
      group: "shop",
      working: ["role-1", "role-5"],
      recent: ["role-15"],
      focus: "role-5",
      minMs: 8500,
      maxMs: 13000
    },
    {
      label: "🧊 Cryo-Freezer Vault Watch",
      group: "stake",
      working: ["role-1", "role-13"],
      recent: ["role-16"],
      focus: "role-13",
      minMs: 9500,
      maxMs: 14500
    },
    {
      label: "🧬 Permanent Trait Incubation",
      group: "lab",
      working: ["role-1", "role-15"],
      recent: ["role-5"],
      focus: "role-15",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "📡 Discord Holder Role Sync",
      group: "discord",
      working: ["role-1", "role-10"],
      recent: ["role-8"],
      focus: "role-10",
      minMs: 8500,
      maxMs: 12500
    },
    {
      label: "🤝 Partner Bridge & Town Crier",
      group: "community",
      working: ["role-1", "role-8", "role-12"],
      recent: [],
      focus: "role-12",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "🗓️ Season 17 Ladder Archive",
      group: "vintage",
      working: ["role-1", "role-14"],
      recent: ["role-17"],
      focus: "role-14",
      minMs: 9500,
      maxMs: 14000
    },
    {
      label: "🧊 3D World & Mobile Stick Pass",
      group: "foundry3d-a",
      working: ["role-19", "role-20", "role-21"],
      recent: ["role-22"],
      focus: "role-20",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "🐭 3D Genesis Avatar & Chest Glow",
      group: "foundry3d-b",
      working: ["role-19", "role-23", "role-27"],
      recent: ["role-24"],
      focus: "role-23",
      minMs: 9000,
      maxMs: 13500
    },
    {
      label: "🧬 Gemini Blueprint → Brain Cellar",
      group: "intel",
      working: ["narrrf-team", "gemini-intel", "role-17"],
      recent: [],
      focus: "gemini-intel",
      minMs: 9500,
      maxMs: 14000
    },
    {
      label: "🌙 Midnight Vault Standby",
      group: "calm-command",
      working: ["narrrf-team"],
      recent: ["role-17", "gemini-intel"],
      focus: "narrrf-team",
      minMs: 10000,
      maxMs: 15000
    }
  ];

  const QUICK_CHIPS = [
    { id: "narrrf-team", label: "👑 Narrrf + Team" },
    { id: "role-2", label: "⚔️ Mousetrap Arena" },
    { id: "role-4", label: "🪙 Cheddar Vault" },
    { id: "role-15", label: "🧪 Genetic Lab" },
    { id: "role-19", label: "🧊 3D Foundry" },
    { id: "gemini-intel", label: "🧬 Gemini Intel" },
    { id: "role-17", label: "🧠 Brain Cellar" }
  ];

  const reducedMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);

  const state = {
    flowMode: reducedMotion ? "off" : "both",
    tourActive: false,
    tourIndex: 0,
    tourDeck: [],
    ambientSeed: Math.floor(Math.random() * 15),
    tourTimer: null,
    selectedId: "narrrf-team",
    tracedId: "narrrf-team",
    pan: { x: 0, y: 0 },
    scale: 1
  };

  function getGroupPrefix(idx) {
    if (idx < 0 || !TOUR_SCENARIOS[idx]) return "";
    const grp = TOUR_SCENARIOS[idx].group || "";
    return grp.split("-")[0];
  }

  function refillTourDeck() {
    const indices = TOUR_SCENARIOS.map(function (_, idx) { return idx; });
    for (let i = indices.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      const tmp = indices[i];
      indices[i] = indices[j];
      indices[j] = tmp;
    }
    state.tourDeck = indices;
  }

  function drawNextScenarioIndex(prevIndex) {
    if (!state.tourDeck || state.tourDeck.length === 0) {
      refillTourDeck();
    }
    const prevPrefix = getGroupPrefix(prevIndex);
    let pickPos = -1;

    for (let i = 0; i < state.tourDeck.length; i++) {
      const candidate = state.tourDeck[i];
      if (candidate !== prevIndex && getGroupPrefix(candidate) !== prevPrefix) {
        pickPos = i;
        break;
      }
    }

    if (pickPos === -1) {
      for (let i = 0; i < state.tourDeck.length; i++) {
        if (state.tourDeck[i] !== prevIndex) {
          pickPos = i;
          break;
        }
      }
    }

    if (pickPos === -1) pickPos = 0;
    return state.tourDeck.splice(pickPos, 1)[0];
  }

  state.tourIndex = drawNextScenarioIndex(-1);

  function clearNode(node) {
    while (node.firstChild) node.removeChild(node.firstChild);
  }

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  }

  function svgEl(tag, attrs) {
    const node = document.createElementNS(SVG_NS, tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        node.setAttribute(k, String(attrs[k]));
      });
    }
    return node;
  }

  function getActiveStatusMap() {
    const map = {};
    if (!state.tourActive) return map;
    const sc = TOUR_SCENARIOS[state.tourIndex % TOUR_SCENARIOS.length];
    sc.working.forEach(function (id) { map[id] = "working"; });
    sc.recent.forEach(function (id) { if (!map[id]) map[id] = "recent"; });
    return map;
  }

  function applyTransform() {
    const vp = document.getElementById("ce-viewport");
    if (vp) {
      vp.setAttribute("transform", "translate(" + state.pan.x + "," + state.pan.y + ") scale(" + state.scale + ")");
    }
  }

  function appendParticle(parent, edgeId, symbolId, durSec, offsetSec, reverse, opacityVal) {
    const g = svgEl("g", { opacity: opacityVal });
    const useEl = svgEl("use", { href: "#" + symbolId });
    useEl.setAttributeNS(XLINK_NS, "xlink:href", "#" + symbolId);

    const animAttrs = {
      dur: durSec.toFixed(2) + "s",
      begin: (-offsetSec).toFixed(2) + "s",
      repeatCount: "indefinite",
      rotate: "auto"
    };
    if (reverse) {
      animAttrs.keyPoints = "1;0";
      animAttrs.keyTimes = "0;1";
      animAttrs.calcMode = "linear";
    }
    const anim = svgEl("animateMotion", animAttrs);
    const mpath = svgEl("mpath", { href: "#" + edgeId });
    mpath.setAttributeNS(XLINK_NS, "xlink:href", "#" + edgeId);
    anim.appendChild(mpath);
    useEl.appendChild(anim);
    g.appendChild(useEl);
    parent.appendChild(g);
  }

  function getParticleSpecs(edge, edgeIdx, isWorkingEdge, isRecentEdge, isMatch) {
    if (state.flowMode === "off") return [];

    function resolveSym(pref) {
      if (state.flowMode === "crumbs") {
        return (isWorkingEdge || isMatch || edge.type !== "operational") ? "ce-pub-crumb-wedge" : "ce-pub-crumb-mini";
      }
      if (state.flowMode === "dna") {
        return isWorkingEdge ? "ce-pub-dna-active" : "ce-pub-dna-helix";
      }
      return pref;
    }

    if (isWorkingEdge) {
      const fwdDur = 4.1 + (edgeIdx % 3) * 0.35;
      const revDur = 5.2 + (edgeIdx % 2) * 0.45;
      return [
        { sym: resolveSym("ce-pub-dna-active"), dur: fwdDur, offset: (edgeIdx * 0.61) % fwdDur, rev: false, opacity: "0.92" },
        { sym: resolveSym("ce-pub-crumb-wedge"), dur: revDur, offset: (edgeIdx * 0.97 + 1.4) % revDur, rev: true, opacity: "0.78" }
      ];
    }

    if (isMatch) {
      const fSym = edge.type === "operational" ? "ce-pub-crumb-wedge" : "ce-pub-dna-helix";
      const rSym = edge.type === "operational" ? "ce-pub-dna-helix" : "ce-pub-crumb-wedge";
      return [
        { sym: resolveSym(fSym), dur: 3.5, offset: (edgeIdx * 0.53) % 3.5, rev: false, opacity: "0.9" },
        { sym: resolveSym(rSym), dur: 4.5, offset: (edgeIdx * 0.87 + 1.1) % 4.5, rev: true, opacity: "0.8" }
      ];
    }

    if (isRecentEdge) {
      const rDur = 5.6 + (edgeIdx % 3) * 0.4;
      const rSym = edge.type === "operational" ? "ce-pub-crumb-wedge" : "ce-pub-dna-helix";
      return [
        { sym: resolveSym(rSym), dur: rDur, offset: (edgeIdx * 0.79) % rDur, rev: false, opacity: "0.72" }
      ];
    }

    if (edge.to === "role-7") return [];
    if ((edgeIdx + state.ambientSeed) % 15 !== 0) return [];

    const ambientDur = 10.5 + (edgeIdx % 4) * 1.1;
    const ambientSym = (edge.type === "direct" || edge.type === "serializer") ? "ce-pub-dna-helix" : "ce-pub-crumb-mini";
    return [
      {
        sym: resolveSym(ambientSym),
        dur: ambientDur,
        offset: (edgeIdx * 1.37) % ambientDur,
        rev: edgeIdx % 2 === 1,
        opacity: "0.34"
      }
    ];
  }

  function renderGraph() {
    const vp = document.getElementById("ce-viewport");
    if (!vp) return;
    clearNode(vp);

    const statusMap = getActiveStatusMap();
    const traceId = state.tracedId;
    const connected = {};
    if (traceId) connected[traceId] = true;

    if (traceId) {
      EDGES.forEach(function (e) {
        if (e.from === traceId || e.to === traceId) {
          connected[e.from] = true;
          connected[e.to] = true;
        }
      });
    }

    const zones = svgEl("g", { "pointer-events": "none" });
    [
      { x: 16, y: 96, w: 1208, h: 90, label: "🧀 CHEESE COUNCIL — 5 PEER SQUAD HEADS REPORTING TO NARRRF + TEAM" },
      { x: 16, y: 224, w: 682, h: 312, label: "⚔️ ARENA • 🪙 CHEDDAR VAULT • 🧪 GENETIC LAB • 📡 SQUEAK RELAYS" },
      { x: 746, y: 224, w: 478, h: 312, label: "🧊 3D CHEESE-VERSE FOUNDRY — 8-AGENT REAL-TIME SQUAD (#20–#27)" }
    ].forEach(function (z) {
      zones.appendChild(svgEl("rect", {
        x: z.x, y: z.y, width: z.w, height: z.h, rx: 12,
        fill: "var(--ce-panel)", "fill-opacity": "0.52",
        stroke: "var(--ce-border)", "stroke-dasharray": "4 4", opacity: "0.78"
      }));
      const txt = svgEl("text", {
        x: z.x + 12, y: z.y + 18,
        fill: "var(--ce-muted)", "font-size": "10", "font-weight": "700", "letter-spacing": "0.04em"
      });
      txt.textContent = z.label;
      zones.appendChild(txt);
    });
    vp.appendChild(zones);

    const edgesLayer = svgEl("g", {});
    const particlesLayer = svgEl("g", { "pointer-events": "none" });
    const nodesLayer = svgEl("g", {});
    vp.appendChild(edgesLayer);
    vp.appendChild(particlesLayer);
    vp.appendChild(nodesLayer);

    EDGES.forEach(function (edge, idx) {
      const a = NODES[edge.from];
      const b = NODES[edge.to];
      if (!a || !b) return;

      const isMatch = traceId ? (edge.from === traceId || edge.to === traceId) : false;
      const dimmed = !!(traceId && !isMatch);
      const sFrom = statusMap[edge.from] || "standby";
      const sTo = statusMap[edge.to] || "standby";
      const isWorkingEdge = sFrom === "working" && sTo === "working";
      const isRecentEdge = !isWorkingEdge && (
        (sFrom === "working" && sTo === "recent") ||
        (sFrom === "recent" && sTo === "working") ||
        (sFrom === "recent" && sTo === "recent")
      );

      let d;
      if (edge.type === "serializer") {
        const sx = a.x, sy = a.y - a.h / 2, tx = b.x, ty = b.y - b.h / 2, ctrlY = Math.min(sy, ty) - 34;
        d = "M " + sx + " " + sy + " C " + sx + " " + ctrlY + ", " + tx + " " + ctrlY + ", " + tx + " " + ty;
      } else {
        const sx = a.x, sy = a.y + a.h / 2, tx = b.x, ty = b.y - b.h / 2, midY = sy + (ty - sy) * 0.5;
        d = "M " + sx + " " + sy + " C " + sx + " " + midY + ", " + tx + " " + midY + ", " + tx + " " + ty;
      }

      let strokeColor = "var(--ce-border)";
      if (isWorkingEdge && !dimmed) strokeColor = "var(--ce-emerald)";
      else if (edge.type === "direct") strokeColor = isMatch ? "var(--ce-gold)" : "rgba(245, 184, 46, 0.55)";
      else if (edge.type === "operational") strokeColor = isMatch ? "var(--ce-emerald)" : "rgba(148, 163, 184, 0.34)";
      else if (edge.type === "serializer") strokeColor = "var(--ce-cyan)";

      const edgeId = "ce-pub-edge-" + idx;
      edgesLayer.appendChild(svgEl("path", {
        id: edgeId,
        d: d,
        fill: "none",
        stroke: strokeColor,
        "stroke-width": isWorkingEdge && !dimmed ? "2.4" : (isMatch ? "2.4" : (edge.type === "operational" ? "1.1" : "1.6")),
        "stroke-dasharray": edge.type === "serializer" ? "5 4" : (edge.type === "operational" ? "2 2" : "none"),
        opacity: dimmed ? "0.14" : (isWorkingEdge ? "0.95" : (edge.type === "operational" && !isMatch ? "0.48" : "0.9"))
      }));

      if (isWorkingEdge && !dimmed) {
        edgesLayer.appendChild(svgEl("path", {
          d: d,
          fill: "none",
          class: "ce-edge-flow",
          stroke: "var(--ce-gold)",
          "stroke-width": "1.6",
          opacity: "0.58",
          style: "animation-duration: 2.6s;"
        }));
      }

      if (!dimmed) {
        const specs = getParticleSpecs(edge, idx, isWorkingEdge, isRecentEdge, isMatch);
        specs.forEach(function (sp) {
          appendParticle(particlesLayer, edgeId, sp.sym, sp.dur, sp.offset, sp.rev, sp.opacity);
        });
      }
    });

    Object.keys(NODES).forEach(function (nodeId) {
      const n = NODES[nodeId];
      const dept = DEPARTMENTS[n.deptKey] || DEPARTMENTS.command;
      const isSelected = state.selectedId === nodeId;
      const isDimmed = !!(traceId && !connected[nodeId]);
      const liveState = statusMap[nodeId] || "standby";

      const g = svgEl("g", {
        class: "ce-node",
        style: "cursor:pointer; transition:opacity 0.15s ease;",
        opacity: isDimmed ? "0.24" : "1",
        transform: "translate(" + (n.x - n.w / 2) + "," + (n.y - n.h / 2) + ")"
      });

      const tip = svgEl("title");
      tip.textContent = n.shortLabel + " — " + dept.cheeseName;
      g.appendChild(tip);

      if (liveState === "working") {
        g.appendChild(svgEl("rect", {
          x: -4, y: -4, width: n.w + 8, height: n.h + 8, rx: 11,
          fill: "none", stroke: "#10b981", class: "ce-node-halo",
          style: "animation-duration: 2.8s;"
        }));
      } else if (liveState === "recent") {
        g.appendChild(svgEl("rect", {
          x: -3, y: -3, width: n.w + 6, height: n.h + 6, rx: 10,
          fill: "none", stroke: "var(--ce-cyan)", "stroke-width": "1.8"
        }));
      }

      let boxStroke = isSelected ? "var(--ce-gold)" : n.color;
      if (liveState === "working") boxStroke = "#10b981";

      g.appendChild(svgEl("rect", {
        x: 0, y: 0, width: n.w, height: n.h, rx: 8,
        fill: "var(--ce-card)", stroke: boxStroke,
        "stroke-width": (isSelected || liveState === "working") ? "2.4" : "1.5",
        "stroke-dasharray": n.dashed ? "4 3" : "none"
      }));
      g.appendChild(svgEl("rect", {
        x: 0, y: 0, width: 5, height: n.h, rx: 3,
        fill: liveState === "working" ? "#10b981" : n.color
      }));

      const badgeText = svgEl("text", {
        x: 13, y: 19, fill: "var(--ce-gold)",
        "font-size": "11.5", "font-weight": "700", "font-family": "ui-monospace, SFMono-Regular, Menlo, monospace"
      });
      badgeText.textContent = n.badge;
      g.appendChild(badgeText);

      const labelText = svgEl("text", {
        x: 13, y: 38, fill: "var(--ce-fg)",
        "font-size": "12", "font-weight": "700"
      });
      labelText.textContent = n.shortLabel;
      g.appendChild(labelText);

      if (liveState === "working") {
        g.appendChild(svgEl("rect", { x: n.w - 52, y: 6, width: 46, height: 15, rx: 4, fill: "#10b981" }));
        const pTxt = svgEl("text", {
          x: n.w - 29, y: 16.5, fill: "#022c22",
          "font-size": "9", "font-weight": "800", "text-anchor": "middle"
        });
        pTxt.textContent = "ACTIVE";
        g.appendChild(pTxt);
      } else {
        g.appendChild(svgEl("circle", {
          cx: n.w - 11, cy: 15, r: 3.8,
          fill: liveState === "recent" ? "var(--ce-cyan)" : (n.dashed ? "var(--ce-cyan)" : "var(--ce-emerald)")
        }));
      }

      g.addEventListener("click", function (evt) {
        evt.stopPropagation();
        selectNode(nodeId, true);
      });

      nodesLayer.appendChild(g);
    });

    applyTransform();
  }

  function renderSpotlight() {
    const box = document.getElementById("ce-spotlight");
    if (!box) return;
    clearNode(box);

    const n = NODES[state.selectedId] || NODES["narrrf-team"];
    const dept = DEPARTMENTS[n.deptKey] || DEPARTMENTS.command;

    const neighbors = [];
    EDGES.forEach(function (e) {
      if (e.from === n.id && NODES[e.to]) neighbors.push(NODES[e.to]);
      else if (e.to === n.id && NODES[e.from]) neighbors.push(NODES[e.from]);
    });

    const leftCol = el("div", "flex-1 min-w-0 space-y-2");
    const topPills = el("div", "flex flex-wrap items-center gap-2");
    topPills.appendChild(el("span", "px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-400/20 text-amber-200 border border-amber-300/35 font-mono", n.badge));
    topPills.appendChild(el("span", "px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-cyan-400/15 text-cyan-200 border border-cyan-300/30", dept.iconCode + " • " + dept.cheeseName));
    topPills.appendChild(el("span", "text-xs text-slate-400 font-semibold", n.roleLabel));
    leftCol.appendChild(topPills);

    leftCol.appendChild(el("h3", "text-lg md:text-xl font-black text-white", n.name));
    leftCol.appendChild(el("p", "text-sm text-slate-300 leading-relaxed", n.summary));

    if (neighbors.length > 0) {
      const linksRow = el("div", "flex flex-wrap items-center gap-1.5 pt-1");
      linksRow.appendChild(el("span", "text-xs font-bold text-slate-400 mr-1", "Connected Squad (" + neighbors.length + "):"));
      neighbors.slice(0, 7).forEach(function (nb) {
        const chip = el("button", "px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-800/90 hover:bg-amber-500/20 text-amber-100 border border-white/10 hover:border-amber-300/40 transition", nb.shortLabel);
        chip.type = "button";
        chip.addEventListener("click", function () {
          selectNode(nb.id, true);
        });
        linksRow.appendChild(chip);
      });
      if (neighbors.length > 7) {
        linksRow.appendChild(el("span", "text-xs text-slate-400", "+" + (neighbors.length - 7) + " more"));
      }
      leftCol.appendChild(linksRow);
    }

    const rightCol = el("div", "flex flex-wrap sm:flex-nowrap lg:flex-col items-stretch gap-2 shrink-0 lg:w-56");
    const primaryCta = el("a", "inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 px-4 py-2.5 text-xs md:text-sm font-black text-slate-950 shadow-lg transition hover:scale-[1.02]", n.ctaLabel + " →");
    primaryCta.href = n.ctaHref;
    const secondaryCta = el("a", "inline-flex items-center justify-center rounded-xl border border-cyan-300/30 bg-cyan-400/10 hover:bg-cyan-400/20 px-4 py-2 text-xs font-bold text-cyan-100 transition", "🧠 Full Cheese Engine Guide");
    secondaryCta.href = "nerd-lab.html";
    rightCol.appendChild(primaryCta);
    rightCol.appendChild(secondaryCta);

    box.appendChild(leftCol);
    box.appendChild(rightCol);
  }

  function renderChips() {
    const wrap = document.getElementById("ce-dept-chips");
    if (!wrap) return;
    clearNode(wrap);
    QUICK_CHIPS.forEach(function (c) {
      const isActive = state.tracedId === c.id;
      const btn = el(
        "button",
        "px-3 py-1.5 rounded-full text-xs font-extrabold border transition " +
          (isActive
            ? "bg-amber-400 text-slate-950 border-amber-300 shadow"
            : "bg-slate-900/80 text-slate-200 border-white/15 hover:border-amber-300/50 hover:text-amber-200"),
        c.label
      );
      btn.type = "button";
      btn.addEventListener("click", function () {
        selectNode(c.id, true);
      });
      wrap.appendChild(btn);
    });
  }

  function updateToolbarUI() {
    document.querySelectorAll("[data-ce-flow]").forEach(function (btn) {
      const m = btn.getAttribute("data-ce-flow");
      const active = state.flowMode === m;
      btn.className =
        "px-2.5 py-1 rounded-lg text-xs font-extrabold border transition " +
        (active
          ? "bg-amber-400 text-slate-950 border-amber-300"
          : "bg-slate-900/90 text-slate-200 border-white/15 hover:border-amber-300/40");
    });

    const tourBtn = document.getElementById("ce-tour-btn");
    if (tourBtn) {
      tourBtn.textContent = state.tourActive ? "⚡ Live Tour: ON" : "⚡ Live Tour: OFF";
      tourBtn.className =
        "px-2.5 py-1 rounded-lg text-xs font-extrabold border transition " +
        (state.tourActive
          ? "bg-emerald-500/20 text-emerald-200 border-emerald-400/40"
          : "bg-slate-900/90 text-slate-300 border-white/15");
    }

    const statusEl = document.getElementById("ce-tour-status");
    if (statusEl) {
      if (state.tracedId && NODES[state.tracedId]) {
        statusEl.textContent = "Inspecting: " + NODES[state.tracedId].shortLabel;
      } else if (state.tourActive) {
        const sc = TOUR_SCENARIOS[state.tourIndex % TOUR_SCENARIOS.length];
        statusEl.textContent = "Simulated Wave: " + sc.label;
      } else {
        statusEl.textContent = "Interactive View • Click any node to explore";
      }
    }
  }

  function selectNode(nodeId, trace) {
    if (!NODES[nodeId]) return;
    state.selectedId = nodeId;
    state.tracedId = trace ? nodeId : null;
    updateToolbarUI();
    renderChips();
    renderGraph();
    renderSpotlight();
  }

  function scheduleNextTourStep() {
    if (state.tourTimer) {
      clearTimeout(state.tourTimer);
      state.tourTimer = null;
    }
    if (!state.tourActive) return;
    const sc = TOUR_SCENARIOS[state.tourIndex % TOUR_SCENARIOS.length];
    const minMs = sc.minMs || 9000;
    const maxMs = sc.maxMs || 14000;
    const delayMs = minMs + Math.floor(Math.random() * (maxMs - minMs + 1));
    state.tourTimer = setTimeout(stepTour, delayMs);
  }

  function stepTour() {
    if (!state.tourActive) return;
    if (!state.tracedId && !document.hidden) {
      state.tourIndex = drawNextScenarioIndex(state.tourIndex);
      state.ambientSeed = (state.ambientSeed + 4) % 15;
      state.selectedId = TOUR_SCENARIOS[state.tourIndex].focus;
      updateToolbarUI();
      renderGraph();
      renderSpotlight();
    }
    scheduleNextTourStep();
  }

  function setupInteractions() {
    const svg = document.getElementById("ce-public-svg");
    if (svg) {
      let dragging = false;
      let startPt = { x: 0, y: 0 };
      let startPan = { x: 0, y: 0 };

      svg.addEventListener("pointerdown", function (e) {
        if (e.target.closest && e.target.closest(".ce-node")) return;
        dragging = true;
        startPt = { x: e.clientX, y: e.clientY };
        startPan = { x: state.pan.x, y: state.pan.y };
        svg.style.cursor = "grabbing";
      });
      window.addEventListener("pointermove", function (e) {
        if (!dragging) return;
        const rect = svg.getBoundingClientRect();
        const sx = rect.width ? (1240 / rect.width) : 1;
        const sy = rect.height ? (568 / rect.height) : 1;
        state.pan.x = startPan.x + (e.clientX - startPt.x) * sx;
        state.pan.y = startPan.y + (e.clientY - startPt.y) * sy;
        applyTransform();
      });
      window.addEventListener("pointerup", function () {
        if (dragging) {
          dragging = false;
          svg.style.cursor = "grab";
        }
      });
      svg.addEventListener("wheel", function (e) {
        if (!e.ctrlKey) return;
        e.preventDefault();
        const f = e.deltaY < 0 ? 1.1 : 0.9;
        state.scale = Number(Math.min(2.0, Math.max(0.7, state.scale * f)).toFixed(2));
        applyTransform();
      }, { passive: false });
    }

    const zIn = document.getElementById("ce-zoom-in");
    const zOut = document.getElementById("ce-zoom-out");
    const zReset = document.getElementById("ce-zoom-reset");
    const clearBtn = document.getElementById("ce-clear-trace");
    const tourBtn = document.getElementById("ce-tour-btn");

    if (zIn) zIn.addEventListener("click", function () {
      state.scale = Number(Math.min(2.0, state.scale * 1.14).toFixed(2));
      applyTransform();
    });
    if (zOut) zOut.addEventListener("click", function () {
      state.scale = Number(Math.max(0.7, state.scale * 0.88).toFixed(2));
      applyTransform();
    });
    if (zReset) zReset.addEventListener("click", function () {
      state.pan = { x: 0, y: 0 };
      state.scale = 1;
      applyTransform();
    });
    if (clearBtn) clearBtn.addEventListener("click", function () {
      state.tracedId = null;
      updateToolbarUI();
      renderChips();
      renderGraph();
      renderSpotlight();
      scheduleNextTourStep();
    });
    if (tourBtn) tourBtn.addEventListener("click", function () {
      state.tourActive = !state.tourActive;
      if (state.tourActive) {
        state.tracedId = null;
        state.tourIndex = drawNextScenarioIndex(state.tourIndex);
        state.ambientSeed = (state.ambientSeed + 4) % 15;
        state.selectedId = TOUR_SCENARIOS[state.tourIndex].focus;
      }
      updateToolbarUI();
      renderChips();
      renderGraph();
      renderSpotlight();
      scheduleNextTourStep();
    });

    document.querySelectorAll("[data-ce-flow]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const mode = btn.getAttribute("data-ce-flow");
        if (mode === "both" || mode === "crumbs" || mode === "dna" || mode === "off") {
          state.flowMode = mode;
          updateToolbarUI();
          renderGraph();
        }
      });
    });

    scheduleNextTourStep();
  }

  renderChips();
  updateToolbarUI();
  renderGraph();
  renderSpotlight();
  setupInteractions();
})();
