// 🧬 Nerd Lab - User-Friendly Overview Content
// This file contains overview summaries for each technical documentation area

const NERD_LAB_OVERVIEWS = {
  masterIndex: {
    title: "📚 Master Index - Complete Technical Documentation",
    summary: "Welcome to the Narrrf's World technical documentation hub! This is your complete guide to all 7 games, systems, and the Cheese Engine 13.0 Agent System we've built in 2025. This documentation represents thousands of hours of development, testing, and refinement.",
    achievements: [
      "✅ 7 Complete Games - Fully documented and integrated (Tetris, Snake, Space Invaders, Cheese Hunt, Discord Race, Cheese Rumble, 3D Riddle Game)",
      "✅ Admin Interface - Enterprise-level management system with 17 tabs and 90+ API endpoints",
      "✅ Discord Bot - 50+ commands for complete community management and game integration",
      "✅ Database System - 66 tables managing all user data, scores, achievements, and system state",
      "✅ Frontend Website - 20+ pages with complete backend integration and 50+ API connections",
      "✅ Cheese Engine 13.0 - 12 AI agents working in harmony to maintain and develop the system",
      "✅ 100+ Achievements - Comprehensive achievement systems across all games",
      "✅ Season Management - Enterprise-level season system with data preservation",
      "✅ Role-Based System - Complete multiplier and theme system for all roles",
      "✅ Complete Integration - All systems seamlessly connected and synchronized"
    ],
    description: "Navigate through all technical documentation using the tabs above. Each section provides comprehensive details about our game systems, architecture, API integrations, database schemas, and implementation guides. This documentation is designed to serve developers for decades, with complete code examples and architectural explanations.",
    stats: {
      totalGames: 7,
      totalSystems: 5,
      totalDocuments: 12,
      totalTables: 66,
      totalAPIs: 90,
      totalAchievements: 100,
      totalCodeLines: "100,000+",
      developmentYear: 2025
    }
  },

  tetris: {
    title: "🎮 Tetris - Complete Technical Documentation",
    summary: "Our classic Tetris game with modern enhancements including role-based multipliers, particle effects, achievement system, and full integration with Narrrf's World ecosystem. Built with vanilla JavaScript for maximum performance and compatibility.",
    achievements: [
      "✅ 25 Achievements - Complete achievement system with dynamic loading from database",
      "✅ Role-Based Multipliers - VIP/Holder bonuses (2.0x, 1.5x, 1.4x, 1.3x, 1.2x, 1.1x)",
      "✅ Store Upgrades - 10+ purchasable enhancements (Cheese Drop Reactor, Matrix Mode, etc.)",
      "✅ Particle Effects - Cheese-themed visual effects with 200+ particles per line clear",
      "✅ Boss System - Challenging end-game content with multiple boss types",
      "✅ Full Integration - Profile page, Admin Interface, Discord bot, leaderboards",
      "✅ Season Support - Complete season management with data preservation",
      "✅ Real-Time Scoring - Instant DSPOINC rewards with role multipliers",
      "✅ Mobile Optimized - Touch controls and responsive design"
    ],
    description: "Tetris game built with vanilla JavaScript (no frameworks), featuring real-time score tracking, DSPOINC rewards, role-based multipliers, store upgrades, and seamless integration with our backend systems. All achievements are dynamically loaded from the database, ensuring easy updates without code changes.",
    techStack: "Vanilla JavaScript, Canvas API, Web Audio API, PHP Backend, SQLite Database",
    fileSize: "~62KB HTML + ~145KB JavaScript",
    databaseTables: ["tbl_tetris_scores", "tbl_tetris_achievements", "tbl_user_scores"]
  },

  snake: {
    title: "🐍 Snake - Complete Technical Documentation",
    summary: "Classic Snake game enhanced with Giant Cheese Snake Boss, Golden Apple system, store upgrades, and 20 achievements. Full integration with our reward system. Features advanced boss AI and power-up mechanics.",
    achievements: [
      "✅ 20 Achievements - Comprehensive achievement system with database-driven loading",
      "✅ Giant Cheese Snake Boss - Epic boss battles with dynamic HP and attack patterns",
      "✅ Golden Apple System - Special power-ups that grant bonuses and special effects",
      "✅ Store Upgrades - Enhanced gameplay mechanics (Speed Core, Apple Booster)",
      "✅ Role-Based Scoring - Multiplier bonuses for all Discord roles",
      "✅ Full Backend Integration - Real-time score tracking and DSPOINC rewards",
      "✅ Boss AI System - Intelligent boss movement and attack patterns",
      "✅ Score Multipliers - Role-based DSPOINC multipliers applied in real-time"
    ],
    description: "Snake game featuring boss mechanics, power-up systems, and full integration with our database and reward distribution systems. The boss system includes advanced AI that adapts to player skill level.",
    techStack: "Vanilla JavaScript, Canvas API, PHP Backend, SQLite Database",
    databaseTables: ["tbl_tetris_scores", "tbl_snake_achievements", "tbl_user_scores"]
  },

  spaceInvaders: {
    title: "👾 Space Invaders - Complete Technical Documentation",
    summary: "Space Invaders with cheese-themed enemies, Phoenix shooting mechanics, 4 boss types, Giant Cheese Boss, and 28 achievements. Advanced weapon and power-up systems. Our most feature-rich game!",
    achievements: [
      "✅ 28 Achievements - Most achievements in any game (database-driven)",
      "✅ 4 Boss Types - Regular, Phoenix, Mini-Phoenix, Giant Cheese Boss",
      "✅ Phoenix Shooting - Special weapon mechanics with fire trails",
      "✅ 10:1 Score Conversion - Efficient DSPOINC rewards (100 score = 10 DSPOINC)",
      "✅ Store Upgrades - Triple shot, ship colors, enhanced weapons",
      "✅ Advanced Enemy AI - Multiple enemy types with different behaviors",
      "✅ Combo System - Kill streaks multiply DSPOINC rewards",
      "✅ Power-Up System - Multiple power-ups (Speed Boost, Bomb, Laser)",
      "✅ Screen Shake Effects - Immersive visual feedback",
      "✅ Dynamic Starfield - Parallax scrolling stars for depth"
    ],
    description: "Space Invaders game with advanced mechanics, multiple boss types, achievement system, and full integration with our reward and tracking systems. Features the most comprehensive achievement system with 28 unique achievements, combo multipliers, and advanced visual effects.",
    techStack: "Vanilla JavaScript, Canvas API, Web Audio API, PHP Backend, SQLite Database",
    databaseTables: ["tbl_tetris_scores", "tbl_space_invaders_achievements", "tbl_user_scores"]
  },

  cheeseHunt: {
    title: "🧀 Cheese Hunt - Complete Technical Documentation",
    summary: "Click-based cheese hunting game with 3 unique cheese eggs, personality systems, quest integration, and 3D game extension. Simple yet engaging gameplay.",
    achievements: [
      "✅ 3 Cheese Eggs - Unique personalities and behaviors",
      "✅ Quest Integration - Connects to main quest system",
      "✅ 3D Game Extension - Cheese Temple captures",
      "✅ Mobile Optimized - Touch-friendly controls",
      "✅ Anti-Cheating Measures - Secure click tracking",
      "✅ Real-Time Analytics - Comprehensive tracking"
    ],
    description: "Cheese Hunt game featuring click mechanics, personality-based eggs, quest system integration, and connection to our 3D Riddle Game."
  },

  discordRace: {
    title: "🏁 Discord Race - Complete Technical Documentation",
    summary: "Text-based racing game played entirely in Discord! Join races, compete for first place, and earn DSPOINC rewards. Full Discord bot integration.",
    achievements: [
      "✅ Discord Bot Integration - Play directly in Discord",
      "✅ Real-Time Racing - Interactive race mechanics",
      "✅ Reward System - Winner rewards + participation",
      "✅ Database Tracking - Complete race history",
      "✅ Admin Integration - Full race management",
      "✅ Profile Integration - Race stats on website"
    ],
    description: "Discord-based racing game where players compete in text-based races, earning rewards and tracking their performance across the Narrrf's World ecosystem."
  },

  cheeseRumble: {
    title: "⚔️ Cheese Rumble - Complete Technical Documentation",
    summary: "Text-based battle royale game in Discord! 200+ unique events, multiple rounds, winner and first-out rewards. Our most complex Discord game.",
    achievements: [
      "✅ 200+ Events - Unique event variations",
      "✅ Battle Royale System - Multi-round elimination",
      "✅ Winner Rewards - Grand prize system",
      "✅ First Out Rewards - Consolation prizes",
      "✅ Event Pool System - Random event generation",
      "✅ Full Discord Integration - Seamless gameplay"
    ],
    description: "Cheese Rumble is our most complex Discord game, featuring a battle royale format with hundreds of unique events and comprehensive reward systems."
  },

  hytopia3d: {
    title: "🧩 3D Riddle Game - Complete Technical Documentation",
    summary: "Three.js-based 3D riddle game with 6 levels, puzzle-solving system, boss battles (Phoenix & Alien Spider), weapon system, chest system, and full 3D mechanics.",
    achievements: [
      "✅ 6 Levels - Complete level system",
      "✅ Riddle System - Puzzle-solving mechanics",
      "✅ Boss Battles - Phoenix & Alien Spider bosses",
      "✅ Weapon System - Full 3D weapon rendering",
      "✅ Chest System - Loot and reward mechanics",
      "✅ Audio System - Complete sound integration"
    ],
    description: "Our flagship 3D game built with Three.js, featuring advanced 3D mechanics, boss battles, puzzle solving, and full integration with our reward systems."
  },

  adminInterface: {
    title: "🖥️ Admin Interface - Complete Technical Documentation",
    summary: "Enterprise-level admin interface with 17 main tabs, complete game management, season management, user management, and 90+ API endpoints. The central command center for all Narrrf's World operations.",
    achievements: [
      "✅ 17 Main Tabs - Complete system management (Dashboard, Users, Games, Store, Quests, Bosses, Discord, etc.)",
      "✅ 90+ API Endpoints - Full admin toolset for every operation",
      "✅ Season Management - Unlimited seasons, data preservation, cross-season analytics",
      "✅ Game Management - All 7 games fully integrated with individual sub-tabs",
      "✅ User Management - Accounts, roles, permissions, score adjustments, audit trails",
      "✅ Store Management - Complete inventory control, item creation, purchase tracking",
      "✅ Quest System - Mission management, reward distribution, claim tracking",
      "✅ Boss Management - 4 boss configurations with notification system",
      "✅ Database Overview - Complete system health and table statistics",
      "✅ Security Crawler - Admin session management and authentication"
    ],
    description: "Our comprehensive admin interface provides complete control over all aspects of Narrrf's World, from game management to user administration. Built with enterprise-level patterns ensuring scalability, security, and maintainability for decades of operation.",
    techStack: "Vanilla JavaScript, PHP Backend, SQLite Database, Tailwind CSS",
    databaseTables: ["All 66 tables accessible via admin APIs"]
  },

  discordBot: {
    title: "🤖 Discord Bot - Complete Technical Documentation",
    summary: "Custom Discord bot with 50+ commands, game integrations, giveaway system, quest system, store integration, and complete community management.",
    achievements: [
      "✅ 50+ Commands - Complete command system",
      "✅ Game Integration - Discord Race, Cheese Rumble, leaderboards",
      "✅ Giveaway System - Epic cheese-themed giveaways",
      "✅ Quest System - Mission tracking and rewards",
      "✅ Store Integration - In-Discord shopping",
      "✅ Admin Tools - Complete moderation system"
    ],
    description: "Our Discord bot is the backbone of community management, providing game access, rewards, quests, and admin tools all within Discord."
  },

  database: {
    title: "🗄️ Database System - Complete Technical Documentation",
    summary: "SQLite database with 66 tables managing all user data, game scores, achievements, store items, Discord events, and complete system state. The single source of truth for all Narrrf's World data.",
    achievements: [
      "✅ 66 Tables - Complete data structure organized into 16 categories",
      "✅ User Management - Accounts (tbl_users), roles (tbl_user_roles), scores (tbl_user_scores)",
      "✅ Game Data - All 7 games tracked (tbl_tetris_scores, tbl_cheese_clicks, tbl_race_participants, etc.)",
      "✅ Achievement System - All achievements stored (tbl_tetris_achievements, tbl_snake_achievements, etc.)",
      "✅ Store System - Items (tbl_store_items), inventory (tbl_user_inventory), purchases (tbl_purchase_history)",
      "✅ Season Management - Historical data preservation (tbl_seasons, tbl_season_leaderboards)",
      "✅ Discord Events - Complete bot event tracking (tbl_giveaways, tbl_giveaway_participants, etc.)",
      "✅ Quest System - Mission definitions and claims (tbl_quests, tbl_quest_claims)",
      "✅ Bug Tracking - Complete bug report system (tbl_bug_reports, tbl_bug_comments)",
      "✅ NFT Integration - Holder verification and collections (tbl_nft_ownership, tbl_holder_verifications)"
    ],
    description: "Our database system is the foundation of Narrrf's World, storing all user data, game progress, achievements, and system state across all platforms. The database is optimized for performance with proper indexes, and all data is preserved across seasons and system updates.",
    techStack: "SQLite3, PHP PDO, Database migrations system",
    databaseSize: "~6.8MB (production), growing with user data",
    backupSystem: "Automated backups before deployments, manual backup tools available"
  },

  frontend: {
    title: "🌐 Frontend Website - Complete Technical Documentation",
    summary: "Complete frontend website with 20+ pages, profile system, game portals, store integration, achievement displays, and full backend integration.",
    achievements: [
      "✅ 20+ Pages - Complete website structure",
      "✅ Profile System - Comprehensive user profiles",
      "✅ Game Portals - All 7 games accessible",
      "✅ Store Integration - Full shopping experience",
      "✅ Achievement Display - All achievements shown",
      "✅ 50+ API Integrations - Complete backend connection"
    ],
    description: "Our frontend website provides the user interface for all Narrrf's World features, seamlessly connecting users to games, store, profile, and community."
  },

  cheeseEngine: {
    title: "🧠 Cheese Engine 13.0 Agent System - Complete Technical Documentation",
    summary: "Unique AI agent system with 12 specialized LLMs working together. Cursor LLM coordinates 10 specialized agents plus Discord Bot for complete system management. This revolutionary approach ensures specialized expertise while maintaining perfect synchronization across all development efforts.",
    achievements: [
      "✅ 12 AI Agents - Complete agent system (Cursor LLM + 10 specialized + Discord Bot)",
      "✅ Specialized Agents - Each agent has unique expertise (Update Brain, Coreforge, Cheese Architect, SQL Junior, etc.)",
      "✅ Synchronization System - All agents work in harmony via JSON sync files",
      "✅ Cursor LLM - Main coordinator and leader, orchestrating all development",
      "✅ Discord Bot Agent - Community management and game integration",
      "✅ LLM Sync Files - Complete system state tracking in 12.0/LLM_SYNC_SYSTEM/",
      "✅ Genesis Master File - Central sync status for all agents",
      "✅ Individual Agent Files - Each agent maintains its own status and achievements",
      "✅ Handover System - Complete documentation for agent transitions",
      "✅ Multi-Language Support - English and German documentation"
    ],
    description: "Cheese Engine 13.0 is our unique AI agent system where 12 specialized LLMs collaborate to manage, develop, and maintain Narrrf's World. This revolutionary approach ensures that each aspect of the system (frontend, backend, database, games, Discord bot) is handled by an expert agent, while maintaining perfect synchronization through our sync file system. This is our secret sauce - a scalable, maintainable, and innovative approach to AI-assisted development!",
    agents: [
      "Cursor LLM (Main Coordinator)",
      "Update Brain (LLM Updates & Memory)",
      "Corebrain (Core Systems)",
      "Coreforge (Backend & APIs)",
      "Cheese Architect (UI/UX & Frontend)",
      "Riddle Brain (Puzzles & Game Logic)",
      "SQL Junior (Database & Queries)",
      "Social Brain (Community & Discord)",
      "Hytopia Integrator (3D Game SDK)",
      "NFT Architect (NFT & Wallet Systems)",
      "Discord Bot (Community Management)"
    ],
    syncFiles: "12.0/LLM_SYNC_SYSTEM/GENESIS_MASTER/LLM_SYNC_STATUS_GENESIS_12.0.json"
  }
};

// Function to generate overview HTML
function generateOverviewHTML(key) {
  const overview = NERD_LAB_OVERVIEWS[key];
  if (!overview) return '';

  return `
    <div class="overview-section mb-8">
      <h1 class="text-4xl font-black text-yellow-400 mb-4">${overview.title}</h1>
      
      <div class="bg-yellow-900/20 border border-yellow-500/50 rounded-xl p-6 mb-6">
        <p class="text-xl text-gray-200 leading-relaxed">${overview.summary}</p>
      </div>

      <div class="bg-green-900/20 border border-green-500/50 rounded-xl p-6 mb-6">
        <h2 class="text-2xl font-bold text-green-300 mb-4">🏆 2025 Achievements</h2>
        <ul class="space-y-2">
          ${overview.achievements.map(ach => `<li class="text-green-100">${ach}</li>`).join('')}
        </ul>
      </div>

      <div class="bg-blue-900/20 border border-blue-500/50 rounded-xl p-6">
        <h2 class="text-2xl font-bold text-blue-300 mb-4">📖 Description</h2>
        <p class="text-blue-100 leading-relaxed">${overview.description}</p>
      </div>

      <div class="mt-8 pt-8 border-t border-yellow-400/30">
        <p class="text-gray-400 text-sm">
          💡 <strong>Full Technical Documentation:</strong> Scroll down to view the complete technical documentation including code examples, API details, database schemas, and implementation guides.
        </p>
      </div>
    </div>
  `;
}

