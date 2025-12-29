# 🌐 FRONTEND WEBSITE - COMPLETE TECHNICAL DOCUMENTATION

**Created:** December 20, 2025  
**Status:** ✅ **COMPLETE**  
**Version:** 1.0.0  
**Purpose:** Complete technical documentation for Narrrf's World Frontend Website  
**Scope:** All public pages, API integrations, database connections, and user interfaces

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Website Architecture](#website-architecture)
3. [Public Pages Reference](#public-pages-reference)
4. [Profile.html - Game Portal](#profilehtml---game-portal)
5. [Game Pages](#game-pages)
6. [API Integration](#api-integration)
7. [Database Integration](#database-integration)
8. [Authentication & Security](#authentication--security)
9. [Mobile Optimization](#mobile-optimization)
10. [Styling & Themes](#styling--themes)
11. [JavaScript Architecture](#javascript-architecture)
12. [Asset Management](#asset-management)

---

## 🎯 **OVERVIEW**

### **Frontend System**
Narrrf's World frontend is a comprehensive web application built with:
- **HTML5/CSS3** - Modern responsive design
- **Vanilla JavaScript** - Interactive gameplay and UI
- **Tailwind CSS** - Utility-first styling framework
- **Progressive Web App** - Mobile-optimized experience

### **Frontend Statistics**
- **Total HTML Pages:** 22+ public pages
- **Game Pages:** 7 game implementations
- **API Endpoints Used:** 55+ backend API endpoints
- **Database Tables Accessed:** 67 tables via APIs
- **Total Frontend Code:** ~16,000+ lines

### **Key Features**
- ✅ **User Authentication** - Discord OAuth integration
- ✅ **Game Portal** - Centralized player profile and stats
- ✅ **7 Live Games** - Tetris, Snake, Space Invaders, Cheese Hunt, Discord Race, Cheese Rumble, 3D Riddle Game
- ✅ **Achievement System** - Visual achievement galleries
- ✅ **Store System** - In-game purchases and inventory
- ✅ **Leaderboards** - Real-time rankings
- ✅ **Mobile Optimization** - Responsive design for all devices

---

## 🏗️ **WEBSITE ARCHITECTURE**

### **Directory Structure**

```
public/
├── 📄 HTML Pages (22+ files)
│   ├── index.html - Landing page
│   ├── profile.html - Game portal & player stats (6,573 lines)
│   ├── tetris.html - Tetris game
│   ├── snake.html - Snake game
│   ├── space-cheese-invaders.html - Space Invaders game
│   ├── admin-interface.html - Admin dashboard (1.2MB)
│   ├── mint.html - NFT minting page
│   ├── whitepaper.html - Project whitepaper
│   ├── whitepaper-pro.html - Professional whitepaper
│   ├── partners.html - Partner portal
│   ├── get-roles.html - Role acquisition guide
│   ├── project-updates.html - Development updates
│   ├── faq.html - Frequently asked questions
│   ├── Bingo.html - Bingo game
│   ├── 3d-riddle.html - 3D riddle interface
│   ├── bug-tracker-collab.html - Bug tracker
│   ├── bug-report.html - Bug reporting
│   ├── finances.html - Financial information
│   ├── stake-lab.html - DSPOINC staking system (681 lines)
│   ├── nerd-lab.html - Holder-exclusive dev logs (820 lines)
│   ├── 404.html - Error page
│   └── Terms.html, privacy.html, privacy-policy.html - Legal pages
│
├── 📁 scripts/ - Game JavaScript files
│   ├── tetris-scroll.js - Tetris game logic
│   ├── snake-scroll.js - Snake game logic
│   └── space-cheese-invaders.js - Space Invaders game logic
│
├── 📁 js/ - Utility JavaScript
│   ├── role-gate.js - Role-based access control
│   └── wallet.js - Web3 wallet integration
│
├── 📁 img/ - Image assets (364+ files)
├── 📁 sounds/ - Audio assets
├── 📁 textures/ - 3D model textures
├── 📁 videos/ - Video assets
│
├── 📄 discord-config.js - Discord configuration system
├── 📄 discord-invite.php - Discord invite handler
├── 📄 api-proxy.php - API proxy endpoint
└── 📄 manifest.json - PWA manifest
```

### **Technology Stack**

#### **Frontend Technologies**
- **HTML5** - Semantic markup
- **CSS3** - Modern styling with Tailwind CSS
- **JavaScript ES6+** - Modern JavaScript features
- **Fetch API** - HTTP requests to backend
- **LocalStorage/SessionStorage** - Client-side data storage
- **WebSocket** - Real-time updates (future)

#### **Backend Integration**
- **RESTful APIs** - JSON-based API communication
- **Discord OAuth** - User authentication
- **Session Management** - PHP session handling
- **Cookie-based Auth** - Secure session tokens

---

## 📄 **PUBLIC PAGES REFERENCE**

### **Core Pages**

#### **1. `index.html` - Landing Page**
**Purpose:** Main entry point for Narrrf's World  
**Key Features:**
- Hero section with game showcase
- Navigation to all games
- Discord authentication
- Season 6 announcements
- Links to profile, mint, whitepaper
- NFT gallery preview
- VR gallery link

**API Integrations:**
- `/api/config/get-discord-config.php` - Discord invite configuration
- `/api/track-egg-click` - Cheese Hunt click tracking (embedded)

**Database Tables:**
- `tbl_cheese_clicks` - Cheese egg click tracking
- `tbl_user_roles` - Discord role display

**Navigation Links:**
- `profile.html` - Player profile
- `mint.html` - NFT minting
- `whitepaper.html` - Project whitepaper
- `get-roles.html` - Role acquisition
- `partners.html` - Partner portal
- `project-updates.html` - Development updates
- `faq.html` - FAQ

---

#### **2. `profile.html` - Game Portal & Player Stats**
**Purpose:** Central hub for player profile, game stats, achievements, store, and inventory  
**Size:** 6,573 lines (largest frontend file)  
**Status:** Primary game portal page

**Key Sections:**
1. **User Profile Header** - Discord avatar, username, roles
2. **DSPOINC Balance** - Current balance display
3. **DSPOINC Staking Overview** - Staking statistics and link to stake-lab.html
4. **All-Time Statistics** - Historical game statistics
5. **Current Season Statistics** - Season-based game stats
6. **Game Stats** - All 7 games statistics
7. **Achievements** - Tetris, Snake, Space Invaders, 3D Puzzles
8. **Store Catalog** - Item browsing and purchasing
9. **Inventory** - User item inventory
10. **Purchase History** - Transaction history
11. **Leaderboards** - Game rankings
12. **Quest System** - Mission tracking
13. **NFT Verification** - Wallet connection
14. **Recent Score Adjustments** - Score change history

**API Integrations (30+ endpoints):**

**User Data:**
- `/api/user/profile.php` - Basic user profile (includes staking stats)
- `/api/user/enhanced-profile.php` - Enhanced profile with inventory
- `/api/user/details.php` - User details
- `/api/user/score-total.php` - DSPOINC balance
- `/api/user/recent-adjustments.php` - Score adjustment history
- `/api/user/roles.php` - Discord roles
- `/api/user/check-season-tester-role.php` - Role verification

**Staking System:**
- `/api/user/get-staking-stats.php` - Staking overview statistics
- `/api/user/get-stakes.php` - Active and completed stakes
- `/api/user/create-stake.php` - Create new DSPOINC stake
- `/api/user/complete-stake.php` - Process completed stakes (cron/admin)

**Game Stats:**
- `/api/user/user-game-missions.php` - All 7 games mission stats
- `/api/user/all-time-stats.php` - Historical statistics
- `/api/dev/get-leaderboard.php` - Leaderboard data

**Achievements:**
- `/api/user/get-tetris-achievements.php` - Tetris achievements (25)
- `/api/user/get-snake-achievements.php` - Snake achievements (20)
- `/api/user/get-space-invaders-achievements.php` - Space Invaders achievements (28)
- `/api/user/get-3d-puzzles-achievements.php` - 3D game achievements

**Store System:**
- `/api/store/items.php` - Store catalog
- `/api/store/inventory.php` - User inventory
- `/api/store/purchase.php` - Item purchases
- `/api/store/get-user-settings.php` - Per-game settings
- `/api/store/update-user-setting.php` - Settings updates

**NFT/Wallet:**
- `/api/wallet/get-nfts.php` - NFT ownership
- `/api/user/verify-nft-holder.php` - NFT verification
- `/api/auth/sync-role.php` - Role synchronization

**Discord:**
- `/api/discord/score-history.php` - Score history

**Database Tables Accessed:**
- `tbl_users` - User accounts
- `tbl_user_scores` - DSPOINC balance
- `tbl_tetris_scores` - Tetris, Snake, Space Invaders scores
- `tbl_cheese_clicks` - Cheese Hunt stats
- `tbl_race_participants` - Discord Race stats
- `tbl_rumble_participants` - Cheese Rumble stats
- `tbl_riddle_completions` - 3D game riddles
- `tbl_cheese_hunt_captures` - 3D game captures
- `tbl_tetris_achievements` - Tetris achievements
- `tbl_snake_achievements` - Snake achievements
- `tbl_space_invaders_achievements` - Space Invaders achievements
- `tbl_user_traits` - User traits (3D achievements)
- `tbl_store_items` - Store catalog
- `tbl_user_inventory` - User inventory
- `tbl_purchase_history` - Purchase history
- `tbl_user_store_settings` - Game settings
- `tbl_score_adjustments` - Score adjustments
- `tbl_dspoinc_stakes` - DSPOINC staking records
- `tbl_user_roles` - Discord roles
- `tbl_quests` - Quest definitions
- `tbl_quest_claims` - Quest claims
- `tbl_nft_ownership` - NFT ownership
- `tbl_holder_verifications` - NFT verifications

**Critical Features:**

**1. Game Stats Display**
- Real-time statistics for all 7 games
- Season-aware data filtering
- All-time statistics
- Mission progress tracking
- Visual progress indicators

**2. Achievement System**
- 4 achievement galleries (Tetris, Snake, Space Invaders, 3D Puzzles)
- Dynamic loading from database
- Unlocked/locked status display
- Achievement icons and descriptions
- Progress tracking

**3. Store Integration**
- Full store catalog browsing
- DSPOINC balance display
- Item purchasing
- Inventory management
- Per-game settings (e.g., Space Invaders ship color)

**4. Leaderboard Display**
- All games leaderboards
- Top players ranking
- Season-specific rankings
- Real-time updates

**5. Quest System**
- Active quests display
- Quest claiming interface
- Reward tracking
- Quest progress monitoring

**Environment Detection:**
- Production: `https://narrrfs.world`
- Local: `http://localhost`
- Automatic API base URL switching

**Local Development Support:**
- Test user override (Discord ID: 328601656659017732)
- Local API endpoints
- Development logging

---

#### **3. `tetris.html` - Tetris Game**
**Purpose:** Classic Tetris gameplay with cheese theme  
**Script:** `scripts/tetris-scroll.js`

**Features:**
- Classic Tetris mechanics
- Role-based multipliers (2.0x VIP, 1.5x Holder, etc.)
- Role-based themes (Golden, Silver, Red, Green, Blue, Cheese)
- Store upgrades integration
- Achievement system (25 achievements)
- Particle effects
- Boss system
- Mobile controls
- Score saving to database

**API Integrations:**
- `/api/user/roles.php` - Role-based multipliers
- `/api/dev/save-score.php` - Score saving
- `/api/user/save-tetris-achievement.php` - Achievement unlocking

**Database Tables:**
- `tbl_user_roles` - Role fetching
- `tbl_tetris_scores` - Score storage (`discord_id`, `game='tetris'`)
- `tbl_tetris_achievements` - Achievement storage
- `tbl_user_scores` - DSPOINC rewards
- `tbl_user_inventory` - Store upgrades
- `tbl_user_store_settings` - Game settings

**Integration Points:**
- Profile page stats display
- Admin interface statistics
- Leaderboard system
- Achievement gallery

---

#### **4. `snake.html` - Snake Game**
**Purpose:** Classic Snake gameplay with cheese theme  
**Script:** `scripts/snake-scroll.js`

**Features:**
- Classic Snake mechanics
- Giant Cheese Snake Boss
- Golden Apple system
- Role-based multipliers
- Role-based themes
- Store upgrades (speed, visuals)
- Achievement system (20 achievements)
- Mobile controls
- Score saving to database

**API Integrations:**
- `/api/user/roles.php` - Role-based multipliers
- `/api/dev/save-score.php` - Score saving
- `/api/user/save-snake-achievement.php` - Achievement unlocking

**Database Tables:**
- `tbl_user_roles` - Role fetching
- `tbl_tetris_scores` - Score storage (`discord_id`, `game='snake'`)
- `tbl_snake_achievements` - Achievement storage
- `tbl_user_scores` - DSPOINC rewards
- `tbl_user_inventory` - Store upgrades

---

#### **5. `space-cheese-invaders.html` - Space Invaders Game**
**Purpose:** Space Invaders arcade shooter with cheese theme  
**Script:** `scripts/space-cheese-invaders.js`

**Features:**
- Classic Space Invaders mechanics
- 4 Boss types + Giant Cheese Boss
- Phoenix shooting system
- Enemy variations (Regular, Tetris Danger, Phoenix, Mini-Phoenix)
- Power-ups and weapons
- Role-based multipliers
- Store upgrades (triple shot, ship color)
- Achievement system (28 achievements)
- 10:1 score conversion to DSPOINC
- Mobile controls
- Score saving to database

**API Integrations:**
- `/api/user/roles.php` - Role-based multipliers
- `/api/dev/save-score.php` - Score saving
- `/api/user/save-space-invaders-achievement.php` - Achievement unlocking

**Database Tables:**
- `tbl_user_roles` - Role fetching
- `tbl_tetris_scores` - Score storage (`discord_id`, `game='space_invaders'`)
- `tbl_space_invaders_achievements` - Achievement storage
- `tbl_user_scores` - DSPOINC rewards (10:1 conversion)
- `tbl_user_inventory` - Store upgrades
- `tbl_user_store_settings` - Ship color settings

---

#### **6. `admin-interface.html` - Admin Dashboard**
**Purpose:** Enterprise-grade admin interface for system management  
**Size:** 1.2 MB (largest single file)

**Features:**
- 17 main tabs (Dashboard, User Management, Missions Status, Point Management, Store Management, Quest System, Game Management, Boss Management, Boss Notifications, Discord Config, Holder Verification, Cheese Guide, Community Funds, Bug Tracker, Twitter Missions, Database Overview, Security Crawler, 12.0 Management, Partners)
- 6 game sub-tabs (Tetris, Snake, Space Invaders, Cheese Hunt, Discord Race, Cheese Rumble)
- Season management
- User management
- Store administration
- Bug tracking
- Database management

**API Integrations:**
- 90+ admin API endpoints
- See `ADMIN_INTERFACE_COMPLETE_TECHNICAL.md` for full details

**Database Tables:**
- All 67 tables accessible via admin APIs

---

#### **7. `mint.html` - NFT Minting Page**
**Purpose:** NFT minting interface via Gensuki launchpad

**Features:**
- NFT collection display
- Minting interface
- Wallet connection
- Collection information
- Mint status tracking

**API Integrations:**
- `/api/wallet/get-nfts.php` - NFT ownership
- `/api/user/verify-nft-holder.php` - Verification

**Database Tables:**
- `tbl_nft_ownership` - NFT records
- `tbl_holder_verifications` - Verification records

---

#### **8. `whitepaper.html` & `whitepaper-pro.html` - Project Documentation**
**Purpose:** Project documentation and technical details

**Features:**
- Project overview
- Technical specifications
- Roadmap
- Tokenomics
- Team information

---

#### **9. `partners.html` - Partner Portal**
**Purpose:** Partner management and display

**Features:**
- Partner showcase
- Partner information
- Partner links

**API Integrations:**
- `/api/admin/get-partners.php` - Partner data

**Database Tables:**
- `tbl_partners` - Partner records

---

#### **10. `get-roles.html` - Role Acquisition Guide**
**Purpose:** Guide for acquiring Discord roles

**Features:**
- Role requirements
- Achievement tracking
- Role benefits
- Links to games

**API Integrations:**
- `/api/user/user-game-missions.php` - Game stats
- `/api/user/roles.php` - User roles

**Database Tables:**
- `tbl_user_roles` - Role data
- `tbl_tetris_scores` - Game stats
- `tbl_cheese_clicks` - Cheese Hunt stats

---

#### **11. `Bingo.html` - Bingo Game**
**Purpose:** Bingo game interface

**Features:**
- Bingo ticket management
- Ticket creation
- Ticket deletion
- Bingo gameplay

**API Integrations:**
- `/api/load-bingo-tickets.php` - Ticket loading
- `/api/save-bingo-ticket.php` - Ticket saving
- `/api/delete-bingo-ticket.php` - Ticket deletion

**Database Tables:**
- `tbl_bingo_tickets` - Bingo tickets

---

#### **12. `3d-riddle.html` - 3D Riddle Interface**
**Purpose:** 3D game riddle interface

**Features:**
- Riddle display
- Answer submission
- Reward tracking
- Database persistence

**API Integrations:**
- `/api/dev/riddle-reward.php` - Riddle completion
- `/api/user/traits.php` - Trait unlocking

**Database Tables:**
- `tbl_riddle_completions` - Riddle completions
- `tbl_user_scores` - DSPOINC rewards
- `tbl_user_traits` - Trait unlocks

---

#### **13. `bug-tracker-collab.html` & `bug-report.html` - Bug Tracking**
**Purpose:** Bug reporting and tracking system

**Features:**
- Bug submission
- Bug categorization
- Bug status tracking
- Bug comments

**API Integrations:**
- `/api/admin/get-bug-data.php` - Bug data
- `/api/admin/update-bug-report.php` - Bug updates

**Database Tables:**
- `tbl_bug_reports` - Bug reports
- `tbl_bug_categories` - Bug categories
- `tbl_bug_priorities` - Bug priorities
- `tbl_bug_statuses` - Bug statuses
- `tbl_bug_status_history` - Status history
- `tbl_bug_comments` - Bug comments
- `tbl_bug_assignments` - Bug assignments

---

#### **14. `stake-lab.html` - DSPOINC Staking System**
**Purpose:** Dedicated page for DSPOINC staking/freezing system  
**Size:** 681 lines  
**Status:** Live - December 25, 2025

**Features:**
- **Balance Dashboard** - Total, available, and frozen DSPOINC display
- **Create Stake Form** - Freeze DSPOINC for selected time windows (1, 3, 6, 12, 24, 36 months)
- **Reward Calculator** - Real-time reward calculation based on duration
- **Active Stakes List** - Display all active stakes with countdown timers
- **Completed Stakes History** - View completed stakes and rewards
- **Ice/Blue Gradient Theme** - Staking-themed visual design
- **Local Development Bypass** - Test user support for local testing
- **Discord Authentication** - Required for access

**API Integrations:**
- `/api/user/get-staking-stats.php` - Staking overview statistics
- `/api/user/get-stakes.php` - Active and completed stakes
- `/api/user/create-stake.php` - Create new DSPOINC stake
- `/api/user/profile.php` - User authentication and balance

**Database Tables:**
- `tbl_dspoinc_stakes` - Staking records
- `tbl_user_scores` - DSPOINC balance (frozen amount deducted)
- `tbl_score_adjustments` - Audit trail for stake creation

**Staking System Details:**
- **Reward Rates:** 2% (1 month), 5% (3 months), 10% (6 months), 20% (12 months), 35% (24 months), 50% (36 months)
- **Freeze Mechanism:** DSPOINC is deducted from available balance when stake is created
- **Reward Payment:** Automatic reward payment when stake completes (via cron or admin)
- **Status Tracking:** Active, completed, cancelled statuses
- **Integration:** Linked from profile.html staking overview section

**Created:** December 25, 2025

---

#### **15. `nerd-lab.html` - Holder-Exclusive Dev Logs**
**Purpose:** Exclusive development logs and technical documentation for Holders and VIP Holders  
**Size:** 820 lines  
**Status:** Live - December 25, 2025

**Features:**
- **Role-Based Access Control** - Exclusive to Holder (ID: 1402668301414563971) and VIP Holder (ID: 1332016526848692345) roles
- **Discord Authentication** - Discord OAuth login required
- **Development Logs** - Technical documentation and updates
- **Holder Community** - Exclusive content for NFT holders
- **Access Denied Page** - Clear messaging for non-holders with Discord login option

**Access Control:**
- **Role Verification:** Checks both role IDs and role names (matches profile.html pattern)
- **Discord OAuth:** Uses standard Discord OAuth URL for authentication
- **Redirect Handling:** Returns to page after successful login

**API Integrations:**
- `/api/user/profile.php` - User authentication and role verification

**Database Tables:**
- `tbl_user_roles` - Role verification
- `tbl_users` - User authentication

**Integration Points:**
- Discord bot auto-welcome message includes link to nerd-lab.html
- Holder channel (ID: 1402671592386986074) bot integration

**Created:** December 25, 2025

---

#### **14. `stake-lab.html` - DSPOINC Staking System**
**Purpose:** Dedicated page for DSPOINC staking/freezing system  
**Size:** 681 lines  
**Status:** Live - December 25, 2025

**Features:**
- **Balance Dashboard** - Total, available, and frozen DSPOINC display
- **Create Stake Form** - Freeze DSPOINC for selected time windows (1, 3, 6, 12, 24, 36 months)
- **Reward Calculator** - Real-time reward calculation based on duration
- **Active Stakes List** - Display all active stakes with countdown timers
- **Completed Stakes History** - View completed stakes and rewards
- **Ice/Blue Gradient Theme** - Staking-themed visual design
- **Local Development Bypass** - Test user support for local testing
- **Discord Authentication** - Required for access

**API Integrations:**
- `/api/user/get-staking-stats.php` - Staking overview statistics
- `/api/user/get-stakes.php` - Active and completed stakes
- `/api/user/create-stake.php` - Create new DSPOINC stake
- `/api/user/profile.php` - User authentication and balance

**Database Tables:**
- `tbl_dspoinc_stakes` - Staking records
- `tbl_user_scores` - DSPOINC balance (frozen amount deducted)
- `tbl_score_adjustments` - Audit trail for stake creation

**Staking System Details:**
- **Reward Rates:** 2% (1 month), 5% (3 months), 10% (6 months), 20% (12 months), 35% (24 months), 50% (36 months)
- **Freeze Mechanism:** DSPOINC is deducted from available balance when stake is created
- **Reward Payment:** Automatic reward payment when stake completes (via cron or admin)
- **Status Tracking:** Active, completed, cancelled statuses
- **Integration:** Linked from profile.html staking overview section

**Created:** December 25, 2025

---

#### **15. `nerd-lab.html` - Holder-Exclusive Dev Logs**
**Purpose:** Exclusive development logs and technical documentation for Holders and VIP Holders  
**Size:** 820 lines  
**Status:** Live - December 25, 2025

**Features:**
- **Role-Based Access Control** - Exclusive to Holder (ID: 1402668301414563971) and VIP Holder (ID: 1332016526848692345) roles
- **Discord Authentication** - Discord OAuth login required
- **Development Logs** - Technical documentation and updates
- **Holder Community** - Exclusive content for NFT holders
- **Access Denied Page** - Clear messaging for non-holders with Discord login option

**Access Control:**
- **Role Verification:** Checks both role IDs and role names (matches profile.html pattern)
- **Discord OAuth:** Uses standard Discord OAuth URL for authentication
- **Redirect Handling:** Returns to page after successful login

**API Integrations:**
- `/api/user/profile.php` - User authentication and role verification

**Database Tables:**
- `tbl_user_roles` - Role verification
- `tbl_users` - User authentication

**Integration Points:**
- Discord bot auto-welcome message includes link to nerd-lab.html
- Holder channel (ID: 1402671592386986074) bot integration

**Created:** December 25, 2025

---

#### **16. `project-updates.html` - Development Updates**
**Purpose:** Project development news and updates

**Features:**
- Update posts
- Development timeline
- Feature announcements

---

#### **17. `faq.html` - Frequently Asked Questions**
**Purpose:** Common questions and answers

**Features:**
- FAQ categories
- Search functionality
- Links to relevant pages

---

#### **18. Legal Pages**
- `privacy.html` - Privacy policy
- `privacy-policy.html` - Detailed privacy policy
- `Terms.html` - Terms of service
- `404.html` - Error page

---

## 🎮 **PROFILE.HTML - GAME PORTAL**

### **Complete Integration Overview**

Profile.html is the central hub of Narrrf's World, integrating all games, systems, and user data.

### **Section Breakdown**

#### **1. User Profile Header**
```javascript
// API: /api/user/profile.php
// Database: tbl_users
// Displays: Username, avatar, Discord ID, roles
```

**Features:**
- Discord avatar display
- Username display
- Role badges
- Login/logout functionality
- Wallet connection status

#### **2. DSPOINC Balance Section**
```javascript
// API: /api/user/score-total.php
// Database: tbl_user_scores (SUM of scores)
// Displays: Current balance, recent adjustments
```

**Features:**
- Real-time balance display
- Recent score adjustments
- Balance history link
- Transaction tracking

#### **3. Game Stats Section**
```javascript
// API: /api/user/user-game-missions.php
// Database: Multiple tables (see below)
// Displays: All 7 games statistics
```

**Games Displayed:**
1. **Tetris** - `tbl_tetris_scores` (`discord_id`, `game='tetris'`)
2. **Snake** - `tbl_tetris_scores` (`discord_id`, `game='snake'`)
3. **Space Invaders** - `tbl_tetris_scores` (`discord_id`, `game='space_invaders'`)
4. **Cheese Hunt** - `tbl_cheese_clicks` (`user_wallet`)
5. **Discord Race** - `tbl_race_participants` (`user_id`)
6. **Cheese Rumble** - `tbl_rumble_participants` (`user_id`)
7. **3D Game** - `tbl_riddle_completions`, `tbl_cheese_hunt_captures` (`user_id`)

**Statistics Displayed:**
- Games played
- Best score
- Total score
- DSPOINC earned
- Mission progress
- Season vs all-time stats

#### **4. Achievement Galleries**

**Tetris Achievements (25 total)**
```javascript
// API: /api/user/get-tetris-achievements.php
// Database: tbl_tetris_achievements (user_id)
// Display: Achievement cards with icons, titles, descriptions
```

**Snake Achievements (20 total)**
```javascript
// API: /api/user/get-snake-achievements.php
// Database: tbl_snake_achievements (user_id)
// Display: Achievement cards with icons, titles, descriptions
```

**Space Invaders Achievements (28 total)**
```javascript
// API: /api/user/get-space-invaders-achievements.php
// Database: tbl_space_invaders_achievements (user_id)
// Display: Achievement cards with icons, titles, descriptions
```

**3D Puzzles Achievements (30+ total)**
```javascript
// API: /api/user/get-3d-puzzles-achievements.php
// Database: tbl_user_traits (user_id, trait keys)
// Display: Achievement cards with icons, titles, descriptions
```

**Achievement Features:**
- Dynamic loading from database
- Unlocked/locked status
- Achievement icons (emoji mapping)
- Progress tracking
- Toggle visibility

#### **5. Store Catalog Section**
```javascript
// APIs: 
//   /api/store/items.php - Catalog
//   /api/store/inventory.php - User inventory
//   /api/store/purchase.php - Purchases
//   /api/store/get-user-settings.php - Settings
//   /api/store/update-user-setting.php - Settings updates
// Database: tbl_store_items, tbl_user_inventory, tbl_purchase_history, tbl_user_store_settings
```

**Features:**
- Full store catalog browsing
- Item categories
- DSPOINC price display
- Purchase functionality
- Inventory integration
- Per-game settings (Space Invaders ship color, etc.)

#### **6. Inventory & Purchase History**
```javascript
// API: /api/user/enhanced-profile.php
// Database: tbl_user_inventory, tbl_purchase_history
// Displays: Item inventory, purchase transactions
```

**Features:**
- Inventory summary (total items, unique items, total value)
- Purchase history (total purchases, total spent, average, largest)
- Detailed item list
- Recent purchases timeline
- Item usage tracking

#### **7. Leaderboard Section**
```javascript
// API: /api/dev/get-leaderboard.php
// Database: Multiple game tables
// Displays: Top players for all games
```

**Features:**
- All games leaderboards
- Top 10 players
- Season-specific rankings
- Navigation between games
- Real-time updates

#### **8. Quest System**
```javascript
// APIs:
//   /api/user/quests.php - Quest list
//   /api/user/claim-quest.php - Quest claiming
// Database: tbl_quests, tbl_quest_claims
```

**Features:**
- Active quests display
- Quest requirements
- Quest rewards
- Claim functionality
- Quest progress tracking

#### **9. NFT Verification & Holder Verify System** (Updated: December 29, 2025)

**Purpose:** Complete NFT holder verification system with wallet connection, NFT display, trait extraction, and automatic Discord role granting

**Location:** 
- `profile.html` - Main holder verification interface with NFT display and trait showcase
- `stake-lab.html` - NFT collection display for staking page

**APIs:**
```javascript
// Primary APIs:
//   /api/wallet/get-nfts.php - NFT ownership via Helius API
//   /api/user/verify-nft-holder.php - Verification and role granting
// Database: tbl_nft_ownership, tbl_holder_verifications, tbl_role_grants
```

**Features:**
- **Phantom Wallet Connection** - Secure Solana wallet integration
- **Helius API Integration** - Uses `getAssetsByOwner` (DAS API) with pagination
- **Collection Filtering** - Filters by collection address and name (Narrrf/Narrrfs)
- **NFT Gallery Display** - Responsive grid with images, names, and traits
- **Visual Differentiation** - VIP NFTs (golden theme) vs Genesis NFTs (blue theme)
- **Trait Extraction** - Fetches and displays NFT metadata and attributes
- **Holder Verification** - Cryptographic signature verification for wallet ownership
- **Automatic Role Granting** - Grants Discord roles based on collection ownership:
  - Genesis Collection → 🏆 Holder role (ID: 1402668301414563971)
  - VIP Collection → 🎴 VIP Holder role (ID: 1332016526848692345)
- **Verification Status** - Real-time status display and role confirmation
- **Metadata Fetching** - Asynchronous loading of NFT images and attributes from `metadataUri`

**Technical Implementation:**

**Profile.html Holder Verify Section:**
- Wallet connection button with Phantom integration
- NFT verification status display
- NFT gallery with trait showcase
- Collection badges (VIP vs Genesis)
- Role granting confirmation
- "How NFT Verification Works" explanation section
- "Powered by Helius" attribution

**Stake-lab.html NFT Display:**
- Wallet connection for NFT verification
- NFT collection gallery with visual differentiation
- Collection filtering (VIP and Genesis)
- Trait display with metadata
- "Under Cheese-struction" placeholder for future features

**Collection Addresses:**
- **Genesis Collection:** `AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML`
- **VIP Collection:** `CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg`

**Verification Flow:**
1. User connects Phantom wallet
2. Frontend calls `get-nfts.php` with wallet address and collection address
3. API uses Helius `getAssetsByOwner` to fetch NFTs
4. API filters by collection key or name (Narrrf/Narrrfs)
5. Frontend displays NFTs with images and traits
6. User clicks "Verify with Backend" button
7. Frontend calls `verify-nft-holder.php` with wallet, signature, and collection (for each collection separately)
8. Backend verifies signature and grants appropriate Discord roles
9. Frontend collects all granted roles across all collections
10. Frontend displays comprehensive success message showing ALL granted roles with NFT counts and collection names
11. Page refreshes after 3 seconds to show updated roles in Discord

**Success Message Display:**
- **Comprehensive Multi-Role Message:** Single message displays all granted roles (Genesis + VIP)
- **Role Details:** Shows role name, NFT count, and collection name for each granted role
- **Format Example:**
  ```
  ✅ NFT Verification Successful!
  
  🎯 Discord Roles Granted:
     1. 🏆 Holder (10 NFTs from Narrrfs World: Genesis Genetic)
     2. 🎴 VIP Holder (3 NFTs from Narrrf Genesis VIP Drop)
  
  ✨ Your Discord roles have been updated! Check your Discord server to see your new roles.
  ```
- **Enhanced `showSuccess` Function:** Supports multi-line messages with proper HTML formatting
- **Error Handling:** Tracks failed collections separately and shows partial success if some collections fail

**Security Features:**
- Cryptographic signature verification (Solana wallet signing)
- Collection address validation
- Role mapping verification (no cross-granting)
- Audit logging of all verifications and role grants

**Updated:** December 29, 2025 - Enhanced with improved Helius API integration, visual differentiation, trait display, and comprehensive multi-role success message system

---

## 🎮 **GAME PAGES**

### **Common Game Features**

All game pages share common features:
- **Role-based multipliers** - Fetch from `/api/user/roles.php`
- **Score saving** - Save to database via `/api/dev/save-score.php`
- **Achievement unlocking** - Save achievements via achievement APIs
- **Store upgrades** - Load from `/api/store/inventory.php`
- **Mobile controls** - Touch-optimized controls
- **Leaderboard integration** - Display top players
- **Profile integration** - Stats sync to profile.html

### **Game-Specific Integrations**

#### **Tetris (`tetris.html`)**
- **Script:** `scripts/tetris-scroll.js`
- **Score Table:** `tbl_tetris_scores` (`discord_id`, `game='tetris'`)
- **Achievements:** `tbl_tetris_achievements` (25 achievements)
- **Special Features:** Particle effects, boss system, role themes

#### **Snake (`snake.html`)**
- **Script:** `scripts/snake-scroll.js`
- **Score Table:** `tbl_tetris_scores` (`discord_id`, `game='snake'`)
- **Achievements:** `tbl_snake_achievements` (20 achievements)
- **Special Features:** Giant Cheese Snake Boss, Golden Apple system

#### **Space Invaders (`space-cheese-invaders.html`)**
- **Script:** `scripts/space-cheese-invaders.js`
- **Score Table:** `tbl_tetris_scores` (`discord_id`, `game='space_invaders'`)
- **Achievements:** `tbl_space_invaders_achievements` (28 achievements)
- **Special Features:** 4 Boss types, Phoenix shooting, 10:1 score conversion

---

## 🔌 **API INTEGRATION**

### **API Architecture**

All frontend pages use a consistent API integration pattern:

```javascript
// Environment detection
const isProduction = window.location.hostname === 'narrrfs.world';
const apiBaseUrl = isProduction ? 'https://narrrfs.world' : '';

// API call pattern
const response = await fetch(`${apiBaseUrl}/api/endpoint.php`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include', // For session-based auth
  body: JSON.stringify({ /* data */ })
});

const data = await response.json();
```

### **API Categories**

#### **User APIs** (`/api/user/*.php`)
- Profile data
- User statistics
- Achievements
- Roles
- Balance

#### **Game APIs** (`/api/dev/*.php`)
- Score saving
- Leaderboards
- Game statistics

#### **Store APIs** (`/api/store/*.php`)
- Item catalog
- Inventory
- Purchases
- Settings

#### **Admin APIs** (`/api/admin/*.php`)
- Admin interface (admin-interface.html only)
- User management
- Game management
- System configuration

#### **Auth APIs** (`/api/auth/*.php`)
- Discord OAuth
- Session management
- Role synchronization

#### **Wallet APIs** (`/api/wallet/*.php`)
- NFT ownership
- Wallet verification

---

## 🗄️ **DATABASE INTEGRATION**

### **Database Access Pattern**

Frontend pages **never directly access the database**. All database operations go through PHP API endpoints:

```
Frontend (JavaScript)
    ↓
API Endpoint (PHP)
    ↓
Database (SQLite3)
```

### **Database Tables Used by Frontend**

**User Data:**
- `tbl_users` - User accounts
- `tbl_user_roles` - Discord roles
- `tbl_user_scores` - DSPOINC balance
- `tbl_score_adjustments` - Score adjustments

**Game Data:**
- `tbl_tetris_scores` - Tetris, Snake, Space Invaders scores
- `tbl_cheese_clicks` - Cheese Hunt clicks
- `tbl_race_participants` - Discord Race participants
- `tbl_rumble_participants` - Cheese Rumble participants
- `tbl_riddle_completions` - 3D game riddles
- `tbl_cheese_hunt_captures` - 3D game captures

**Achievements:**
- `tbl_tetris_achievements` - Tetris achievements
- `tbl_snake_achievements` - Snake achievements
- `tbl_space_invaders_achievements` - Space Invaders achievements
- `tbl_user_traits` - 3D game achievements

**Store:**
- `tbl_store_items` - Store catalog
- `tbl_user_inventory` - User inventory
- `tbl_purchase_history` - Purchase history
- `tbl_user_store_settings` - Game settings

**Other:**
- `tbl_quests` - Quest definitions
- `tbl_quest_claims` - Quest claims
- `tbl_nft_ownership` - NFT ownership
- `tbl_holder_verifications` - NFT verifications
- `tbl_bingo_tickets` - Bingo tickets
- `tbl_partners` - Partner data
- `tbl_bug_reports` - Bug reports

---

## 🔐 **AUTHENTICATION & SECURITY**

### **Authentication System**

#### **Discord OAuth**
```javascript
// OAuth URL pattern
const discordAuthUrl = `https://discord.com/oauth2/authorize?client_id=${CLIENT_ID}&response_type=code&redirect_uri=${REDIRECT_URI}&scope=guilds+identify+guilds.members.read`;

// Redirect to Discord
window.location.href = discordAuthUrl;
```

**Flow:**
1. User clicks "Login with Discord"
2. Redirects to Discord OAuth
3. User authorizes
4. Discord redirects to `/api/auth/callback.php`
5. Backend creates session
6. Frontend receives session cookie
7. Profile data loaded

#### **Session Management**
- PHP sessions via cookies
- Session stored in `$_SESSION`
- Frontend uses `credentials: 'include'` for session cookies
- Session timeout: Configurable

#### **Role-Based Access Control**

**Role Gate System** (`js/role-gate.js`):
- Protects admin pages
- Checks Discord roles via API
- Redirects unauthorized users
- Shows custom access denied page

**Protected Pages:**
- `admin-interface.html` - Admin dashboard

**Admin Roles:**
- Admin, Administrator
- Moderator, Mod
- Server Admin, Server Moderator
- Owner, Founder

### **Security Features**

#### **Input Validation**
- All user input validated on backend
- SQL injection prevention via prepared statements
- XSS protection via output escaping
- CSRF protection via session tokens

#### **API Security**
- Session-based authentication
- Role-based authorization
- Rate limiting (future)
- Error message sanitization

---

## 📱 **MOBILE OPTIMIZATION**

### **Mobile-First Design**

All pages are optimized for mobile devices:

#### **Responsive Breakpoints**
- **Mobile:** < 768px
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px

#### **Mobile Features**

**Touch Controls:**
- Touch-optimized game controls
- Swipe gestures
- Tap interactions
- Long-press menus

**Viewport Optimization:**
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover"/>
```

**Mobile-Specific Styles:**
- Compact headers
- Stacked layouts
- Touch-friendly buttons
- Optimized font sizes

**Performance:**
- Lazy loading images
- Optimized asset sizes
- Reduced animations on mobile
- Efficient API calls

### **Game Mobile Optimization**

**Tetris, Snake, Space Invaders:**
- Touch controls
- Responsive canvas sizing
- Mobile-friendly UI
- Touch-optimized buttons
- Prevent scroll during gameplay

---

## 🎨 **STYLING & THEMES**

### **Tailwind CSS**

All pages use Tailwind CSS via CDN:
```html
<script src="https://cdn.tailwindcss.com"></script>
```

### **Theme System**

#### **Color Schemes**
- **Primary:** Blue gradients (#1e3a8a to #3730a3)
- **Success:** Green (#059669)
- **Warning:** Yellow (#fcd34d)
- **Error:** Red (#ef4444)
- **Cheese:** Orange/Yellow (#f59e0b)

#### **Role-Based Themes** (Games)
- **Golden** - VIP Holder (2.0x)
- **Silver** - Holder (1.5x)
- **Red** - Champion (1.4x)
- **Green** - Season Tester (1.3x)
- **Blue** - Early Bird (1.2x)
- **Cheese** - Cheese Hunter (1.1x)

#### **Dark Mode**
- Default dark theme
- High contrast colors
- Readable text
- Consistent styling

### **Animations**

**Common Animations:**
- Fade in/out
- Scale transitions
- Hover effects
- Loading spinners
- Achievement popups

**New Year 2026 Theme** (Current):
- Confetti animations
- Celebration gradients
- Modern visual effects

---

## 📜 **JAVASCRIPT ARCHITECTURE**

### **Core JavaScript Files**

#### **1. `discord-config.js` - Discord Configuration**
**Purpose:** Centralized Discord invite link management

**Features:**
- Environment variable support
- Server-side config loading
- Automatic link updates
- Fallback system

**Usage:**
```javascript
// Load config on page load
DISCORD_CONFIG.loadServerConfig();
DISCORD_CONFIG.updateAllLinks();

// Get current URL
const discordUrl = DISCORD_CONFIG.getCurrentUrl();
```

#### **2. `js/role-gate.js` - Role-Based Access Control**
**Purpose:** Protect admin pages with role checking

**Features:**
- Automatic role checking
- Access denial page
- Admin role detection
- Redirect handling

**Usage:**
```javascript
// Auto-check on page load
ROLE_GATE_CONFIG.checkAndBlock();
```

#### **3. `js/wallet.js` - Web3 Wallet Integration**
**Purpose:** Phantom wallet connection and NFT verification

**Features:**
- Wallet detection
- Connection handling
- NFT ownership checking
- Transaction signing

### **Game Scripts**

All game scripts follow a similar pattern:
- Game initialization
- Event handling
- Score calculation
- API integration
- Mobile controls
- Achievement checking

---

## 📦 **ASSET MANAGEMENT**

### **Asset Directories**

#### **Images (`img/`)**
- **364+ image files**
- Game assets
- UI elements
- Icons and logos
- Partner logos
- Achievement icons

#### **Sounds (`sounds/`)**
- Game sound effects
- Music tracks
- Voice clips

#### **Textures (`textures/`)**
- 3D model textures
- Material files
- Environment maps

#### **Videos (`videos/`)**
- Promotional videos
- Game trailers
- Tutorial videos

### **Asset Loading**

**Preloading:**
```html
<link rel="preload" as="image" href="img/asset.png">
<link rel="preload" as="audio" href="sounds/sound.wav">
```

**Lazy Loading:**
- Images load on demand
- Game assets load when needed
- API data fetched asynchronously

---

## 🔗 **INTEGRATION SUMMARY**

### **Frontend → Backend Integration**

```
Frontend Pages
    ├── Profile.html (30+ API endpoints)
    ├── Game Pages (3-5 API endpoints each)
    ├── Admin Interface (90+ API endpoints)
    ├── Store Pages (5+ API endpoints)
    └── Other Pages (1-3 API endpoints each)

    ↓ API Calls

Backend APIs
    ├── /api/user/*.php (15+ endpoints)
    ├── /api/dev/*.php (10+ endpoints)
    ├── /api/store/*.php (5+ endpoints)
    ├── /api/admin/*.php (90+ endpoints)
    ├── /api/auth/*.php (3+ endpoints)
    └── /api/wallet/*.php (2+ endpoints)

    ↓ Database Operations

Database (SQLite3)
    └── 67 tables accessed via APIs
```

### **Frontend → Database Connection Points**

**Direct Connections:** ❌ None (all via APIs)

**API Connections:**
- ✅ All database operations via PHP APIs
- ✅ Secure parameterized queries
- ✅ Session-based authentication
- ✅ Role-based authorization

---

## ✅ **SUMMARY**

The Narrrf's World frontend website provides:
- ✅ **22+ public pages** covering all system aspects
- ✅ **Profile.html** as central game portal (6,573 lines)
- ✅ **Stake-lab.html** for DSPOINC staking system (681 lines)
- ✅ **Nerd-lab.html** for holder-exclusive content (820 lines)
- ✅ **7 game pages** with full integration
- ✅ **50+ API endpoints** integrated
- ✅ **66 database tables** accessed via APIs
- ✅ **Mobile-optimized** responsive design
- ✅ **Achievement system** with 4 galleries
- ✅ **Store system** with full catalog
- ✅ **Authentication** via Discord OAuth
- ✅ **Role-based access** control
- ✅ **Production-ready** architecture

**Status:** ✅ **Production Ready** - All pages operational and integrated

---

---

## 📅 **RECENT UPDATES**

### **December 26, 2025 - Content Updates & 2026 Theme:**
- ✅ Updated all public pages to reflect 2026 instead of 2025
- ✅ Updated game counts from 5 to 7 games (includes Cheese Rumble and 3D Riddle Game)
- ✅ Changed "3D Hytopia" → "3D Riddle Game" (consistent naming)
- ✅ Added DSPOINC Staking System references to development and testing sections
- ✅ Updated database count to 67 tables
- ✅ Removed outdated Christmas references and updated to New Year 2026 theme
- ✅ Removed outdated events ("Last Bingo Event - Dec 18th", "VIP Night - Nov 28th")
- ✅ Updated "CHRISTMAS EVENTS" → "ONGOING GIVEAWAYS & EVENTS"
- ✅ Removed Christmas snowflake animations from `index.html` and `profile.html`

**Files Updated:**
- `public/index.html` - New Year 2026 theme, updated content
- `public/project-updates.html` - Updated to 2026, removed outdated content
- `public/profile.html` - Removed Christmas animations

---

**Document Created:** December 20, 2025  
**Last Updated:** December 29, 2025  
**Version:** 1.0.1  
**Maintainer:** Narrrf's World Development Team

