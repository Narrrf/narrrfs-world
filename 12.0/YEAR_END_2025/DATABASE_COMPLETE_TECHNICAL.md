# 🗄️ DATABASE SYSTEM - COMPLETE TECHNICAL DOCUMENTATION

**Created:** December 20, 2025  
**Status:** ✅ **COMPLETE**  
**Version:** 1.0.0  
**Purpose:** Complete technical documentation for Narrrf's World SQLite Database System  
**Scope:** All tables, relationships, integrations, and architecture

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Database Architecture](#database-architecture)
3. [Database Connection](#database-connection)
4. [Table Categories](#table-categories)
5. [Complete Table Reference](#complete-table-reference)
6. [Table Relationships](#table-relationships)
7. [Field Mappings](#field-mappings)
8. [Database Integration](#database-integration)
9. [Backup & Maintenance](#backup--maintenance)
10. [Query Patterns](#query-patterns)
11. [Performance Optimization](#performance-optimization)
12. [Migration System](#migration-system)

---

## 🎯 **OVERVIEW**

### **Database System**
Narrrf's World uses **SQLite3** as the primary database system, providing:
- **Centralized Data Storage** - All game data, user data, and system data
- **Single-File Architecture** - Easy backup and deployment
- **ACID Compliance** - Data integrity guarantees
- **Production Ready** - Handles high concurrency and large datasets

### **Database Statistics**
- **Total Tables:** 66 tables
- **Database Size:** ~6.8 MB (live snapshot)
- **Primary Key:** User identification via Discord IDs
- **Backup Strategy:** Regular snapshots and versioned backups

### **Database Location**
- **Production:** `/var/www/html/db/narrrf_world.sqlite` (Render server)
- **Local Development:** `C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite`
- **Backup Directory:** `db/backup/`

### **Database Access**
- **PHP APIs:** Direct SQLite3 connections via PDO/SQLite3
- **Discord Bot:** Via `/api/discord/db-access.php` (authenticated API)
- **Admin Interface:** Via various `/api/admin/` endpoints
- **Authentication:** Bearer token authentication for Discord bot

---

## 🏗️ **DATABASE ARCHITECTURE**

### **Core Design Principles**

#### **1. User-Centric Design**
- **Primary Identifier:** Discord ID (`discord_id` or `user_id`)
- **User Data:** Centralized in `tbl_users`
- **User Scores:** Distributed across game-specific tables
- **User Inventory:** Centralized in `tbl_user_inventory`

#### **2. Game-Agnostic Structure**
- **Separate Tables:** Each game has its own score/achievement tables
- **Consistent Patterns:** Similar structure across all games
- **Season Support:** Season-aware data storage
- **Historical Data:** Preserved through historical tables

#### **3. Audit Trail System**
- **Score Adjustments:** `tbl_score_adjustments` tracks all changes
- **Role Grants:** `tbl_role_grants` tracks Discord role assignments
- **Purchase History:** `tbl_purchase_history` tracks store transactions
- **Status History:** Various status history tables for tracking changes

#### **4. Integration Points**
- **Discord Bot:** Reads/writes via authenticated API
- **Admin Interface:** Full CRUD operations via PHP APIs
- **Frontend Games:** Read/write scores via PHP APIs
- **3D Game:** Read/write riddles and captures via PHP APIs

---

## 🔌 **DATABASE CONNECTION**

### **Connection Pattern**

#### **PHP Connection (Standard)**
```php
// Production path
$db_path = '/var/www/html/db/narrrf_world.sqlite';

// Local development path
$db_path = __DIR__ . '/../../db/narrrf_world.sqlite';

// Environment detection
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
$db_path = $isProduction 
    ? '/var/www/html/db/narrrf_world.sqlite'
    : __DIR__ . '/../../db/narrrf_world.sqlite';

// Connect
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Database connection failed');
}
```

#### **SQLite3 Connection (Legacy)**
```php
try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Database connection failed');
}
```

#### **Discord Bot API Connection**
```javascript
// Via authenticated API endpoint
const response = await fetch('https://narrrfs.world/api/discord/db-access.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': config.botToken
    },
    body: JSON.stringify({ 
        action: 'query', 
        query: 'SELECT * FROM tbl_users WHERE discord_id = ?',
        params: [userId]
    })
});
const data = await response.json();
```

### **Connection Security**

#### **Authentication**
- **Discord Bot:** Bearer token authentication
- **Admin APIs:** Session-based authentication
- **Public APIs:** Limited read-only access

#### **SQL Injection Prevention**
- **Prepared Statements:** All queries use parameterized statements
- **Input Validation:** All user input validated before database operations
- **Query Restrictions:** Discord bot API only allows SELECT/INSERT/UPDATE/DELETE

---

## 📊 **TABLE CATEGORIES**

The 66 tables are organized into the following categories:

### **1. User Management (6 tables)**
- `tbl_users` - Main user accounts
- `tbl_user_roles` - Discord role assignments
- `tbl_user_traits` - User trait assignments
- `tbl_user_scores` - DSPOINC balance tracking
- `tbl_score_adjustments` - Score adjustment audit trail
- `tbl_user_season_achievements` - Season-based achievements

### **2. Game Scores (5 tables)**
- `tbl_tetris_scores` - Tetris, Snake, Space Invaders scores
- `tbl_cheese_clicks` - Cheese Hunt click data
- `tbl_race_participants` - Discord Race participants
- `tbl_rumble_participants` - Cheese Rumble participants
- `tbl_cheese_hunt_captures` - 3D Game Cheese Temple captures

### **3. Achievements (3 tables)**
- `tbl_tetris_achievements` - Tetris achievements (25 total)
- `tbl_snake_achievements` - Snake achievements (20 total)
- `tbl_space_invaders_achievements` - Space Invaders achievements (28 total)

### **4. Store System (4 tables)**
- `tbl_store_items` - Store item definitions
- `tbl_user_inventory` - User inventory items
- `tbl_purchase_history` - Purchase transaction records
- `tbl_user_store_settings` - Per-game player settings

### **5. Quest System (2 tables)**
- `tbl_quests` - Quest definitions
- `tbl_quest_claims` - Quest claim submissions

### **6. Discord Events (6 tables)**
- `tbl_cheese_races` - Discord Race events
- `tbl_cheese_rumbles` - Cheese Rumble battle royale events
- `tbl_giveaways` - Giveaway events
- `tbl_giveaway_participants` - Giveaway participants
- `tbl_giveaway_winners` - Giveaway winners
- `tbl_discord_events` - Discord bot event logging

### **7. Season Management (4 tables)**
- `tbl_seasons` - Season definitions
- `tbl_season_settings` - Season configuration
- `tbl_season_leaderboards` - Season-based leaderboards
- `tbl_historical_stats` - Historical game statistics
- `tbl_historical_cheese_stats` - Historical Cheese Hunt statistics

### **8. NFT & Wallet (4 tables)**
- `tbl_nft_ownership` - NFT ownership records
- `tbl_holder_verifications` - Holder verification records
- `tbl_wallet_transactions` - Wallet transaction records
- `tbl_wallet_balance_history` - Wallet balance history

### **9. Twitter Missions (3 tables)**
- `tbl_twitter_missions` - Twitter mission definitions
- `tbl_twitter_mission_participants` - Mission participants
- `tbl_twitter_verification_logs` - Verification audit logs

### **10. Bug Tracker (6 tables)**
- `tbl_bug_reports` - Main bug reports
- `tbl_bug_categories` - Bug categories
- `tbl_bug_priorities` - Bug priority levels
- `tbl_bug_statuses` - Bug status definitions
- `tbl_bug_status_history` - Status change history
- `tbl_bug_assignments` - Bug assignment tracking
- `tbl_bug_comments` - Bug report comments

### **11. Admin & Security (6 tables)**
- `tbl_admin_sessions` - Admin session management
- `tbl_admin_inventory_actions` - Admin inventory actions
- `tbl_security_crawls` - Security crawl results
- `tbl_security_findings` - Security findings
- `tbl_security_bruteforce_attempts` - Bruteforce attempt tracking
- `tbl_role_grants` - Role grant audit trail
- `tbl_wl_role_grants` - Whitelist role grants

### **12. Game Configuration (4 tables)**
- `tbl_game_settings` - Game configuration settings
- `tbl_space_invaders_settings` - Space Invaders specific settings
- `boss_configurations` - Boss game configurations
- `boss_level_notifications` - Boss level achievement notifications

### **13. Rewards & Items (3 tables)**
- `tbl_rewards` - Reward definitions
- `tbl_item_usage_history` - Item usage tracking
- `tbl_item_usage_requests` - Item usage ticket requests

### **14. Community & Partners (3 tables)**
- `tbl_community_funds` - Community wallet funds tracking
- `tbl_partners` - Partner portal management
- `tbl_bingo_tickets` - Bingo game tickets

### **15. 3D Game (2 tables)**
- `tbl_riddle_completions` - Three.js Cheese Temple riddle completions
- `leaderboard` - General leaderboard cache

### **16. Legacy/Backup (1 table)**
- `tbl_space_invaders_negative_scores_backup` - Backup of negative scores

---

## 📋 **COMPLETE TABLE REFERENCE**

### **User Management Tables**

#### **`tbl_users`**
**Purpose:** Main user account information  
**Primary Key:** `discord_id` (TEXT)  
**Key Fields:**
- `discord_id` - Discord user ID (primary key)
- `username` - Discord username
- `avatar_url` - Discord avatar URL
- `twitter_username` - Linked Twitter account
- `created_at` - Account creation timestamp
- `verification_status` - Account verification status

**Usage:**
- User account lookup
- Profile data retrieval
- Discord bot user synchronization

#### **`tbl_user_scores`**
**Purpose:** DSPOINC balance tracking  
**Primary Key:** `id` (INTEGER AUTOINCREMENT)  
**Key Fields:**
- `user_id` - Discord user ID
- `game` - Game identifier (e.g., "cheese_temple_riddles")
- `score` - DSPOINC amount (can be positive or negative)
- `source` - Source of score (e.g., "riddle_completion", "legacy")
- `timestamp` - Transaction timestamp
- `season` - Season identifier

**Usage:**
- Balance calculations (SUM of scores)
- Transaction history
- Season-aware tracking

#### **`tbl_score_adjustments`**
**Purpose:** Admin score adjustment audit trail  
**Primary Key:** `id` (INTEGER AUTOINCREMENT)  
**Key Fields:**
- `user_id` - Discord user ID
- `amount` - Adjustment amount
- `reason` - Reason for adjustment (required for "Recent Score Changes" display)
- `timestamp` - Adjustment timestamp
- `admin_id` - Admin who made the adjustment

**Usage:**
- Admin interface "Recent Score Changes" display
- Audit trail for all score modifications
- User transaction history

#### **`tbl_user_roles`**
**Purpose:** Discord role assignments  
**Key Fields:**
- `user_id` - Discord user ID
- `role_name` - Discord role name

**Usage:**
- Role-based access control
- Multiplier calculations
- User permissions

#### **`tbl_user_traits`**
**Purpose:** User trait assignments  
**Key Fields:**
- `user_id` - Discord user ID
- `trait` - Trait identifier

**Usage:**
- User trait tracking
- Profile display
- Game mechanics

---

### **Game Score Tables**

#### **`tbl_tetris_scores`**
**Purpose:** Tetris, Snake, and Space Invaders game scores  
**Primary Key:** `id` (INTEGER AUTOINCREMENT)  
**Key Fields:**
- `discord_id` - Discord user ID (NOT `user_id`)
- `discord_name` - Discord username
- `score` - Game score
- `game` - Game identifier ('tetris', 'snake', 'space_invaders')
- `season` - Season identifier
- `timestamp` - Score timestamp
- `is_current_season` - Current season flag

**Critical Field Mapping:**
- ✅ Uses `discord_id` (NOT `user_id`)
- ✅ Game differentiated by `game` field
- ✅ Season-aware via `season` and `is_current_season` fields

**Usage:**
- Mission status display
- Leaderboard calculations
- Game statistics

#### **`tbl_cheese_clicks`**
**Purpose:** Cheese Hunt game click tracking  
**Primary Key:** `id` (INTEGER AUTOINCREMENT)  
**Key Fields:**
- `user_wallet` - Discord user ID (NOT `discord_id`)
- `clicks` - Number of clicks
- `timestamp` - Click timestamp

**Critical Field Mapping:**
- ✅ Uses `user_wallet` (NOT `discord_id` or `user_id`)

**Usage:**
- Cheese Hunt game statistics
- Mission status display
- Click tracking

#### **`tbl_race_participants`**
**Purpose:** Discord Race participation tracking  
**Primary Key:** `id` (INTEGER AUTOINCREMENT)  
**Key Fields:**
- `race_id` - Race event ID
- `user_id` - Discord user ID (NOT `discord_id`)
- `position` - Final position (NOT `final_position`)
- `cheese_count` - Cheese collected
- `timestamp` - Participation timestamp

**Critical Field Mapping:**
- ✅ Uses `user_id` (NOT `discord_id`)
- ✅ Uses `position` (NOT `final_position`)

**Usage:**
- Race result tracking
- Winner determination
- Mission status display

#### **`tbl_rumble_participants`**
**Purpose:** Cheese Rumble battle royale participation  
**Primary Key:** `id` (INTEGER AUTOINCREMENT)  
**Key Fields:**
- `rumble_id` - Rumble event ID
- `user_id` - Discord user ID
- `final_position` - Final position in rumble
- `eliminated_at` - Elimination timestamp

**Usage:**
- Rumble result tracking
- Winner determination
- Reward distribution

#### **`tbl_cheese_hunt_captures`**
**Purpose:** 3D Game Cheese Temple hunt captures  
**Key Fields:**
- `user_id` - Discord user ID
- `capture_count` - Number of captures
- `dspoinc_awarded` - DSPOINC amount
- `timestamp` - Capture timestamp

**Usage:**
- 3D game capture tracking
- DSPOINC reward tracking
- Mission status display

---

### **Achievement Tables**

#### **`tbl_tetris_achievements`**
**Purpose:** Tetris game achievements (25 total)  
**Primary Key:** `id` (INTEGER AUTOINCREMENT)  
**Key Fields:**
- `user_id` - Discord user ID (NOT `discord_id`)
- `achievement_key` - Achievement identifier
- `achievement_title` - Achievement title
- `achievement_description` - Achievement description
- `achievement_icon` - Achievement icon (emoji)
- `unlocked_at` - Unlock timestamp

**Special Record:**
- `user_id = 'ACHIEVEMENT_DEFINITIONS'` - Contains master achievement definitions

**Usage:**
- Achievement display on profile
- Achievement unlocking
- Achievement definitions loading

#### **`tbl_snake_achievements`**
**Purpose:** Snake game achievements (20 total)  
**Structure:** Same as `tbl_tetris_achievements`  
**Usage:** Snake achievement tracking and display

#### **`tbl_space_invaders_achievements`**
**Purpose:** Space Invaders achievements (28 total)  
**Structure:** Same as `tbl_tetris_achievements`  
**Usage:** Space Invaders achievement tracking and display

---

### **Store System Tables**

#### **`tbl_store_items`**
**Purpose:** Store item definitions  
**Key Fields:**
- `item_id` - Item identifier
- `item_name` - Item name
- `description` - Item description
- `price` - DSPOINC price
- `is_active` - Active status flag
- `quantity` - Available quantity (optional limit)

**Usage:**
- Store item browsing
- Purchase validation
- Inventory management

#### **`tbl_user_inventory`**
**Purpose:** User inventory items  
**Key Fields:**
- `user_id` - Discord user ID
- `item_id` - Item identifier
- `quantity` - Item quantity
- `purchased_at` - Purchase timestamp

**Usage:**
- User inventory display
- Item usage tracking
- Inventory management

#### **`tbl_purchase_history`**
**Purpose:** Store purchase transaction records  
**Key Fields:**
- `user_id` - Discord user ID
- `item_id` - Item identifier
- `price` - Purchase price
- `timestamp` - Purchase timestamp

**Usage:**
- Purchase history display
- Transaction audit trail
- Financial tracking

---

### **Quest System Tables**

#### **`tbl_quests`**
**Purpose:** Quest definitions  
**Key Fields:**
- `quest_id` - Quest identifier
- `quest_type` - Quest type (Twitter, Discord, Game, Custom)
- `description` - Quest description
- `reward_dspoinc` - DSPOINC reward amount
- `duration` - Quest duration
- `status` - Quest status (active, closed)

**Usage:**
- Quest creation and management
- Quest listing
- Reward distribution

#### **`tbl_quest_claims`**
**Purpose:** Quest claim submissions  
**Key Fields:**
- `claim_id` - Claim identifier
- `quest_id` - Quest identifier
- `user_id` - Discord user ID
- `claimed_at` - Claim timestamp
- `status` - Claim status (pending, approved, rejected)

**Usage:**
- Quest claim tracking
- Approval workflow
- Reward distribution

---

### **Discord Event Tables**

#### **`tbl_giveaways`**
**Purpose:** Giveaway event definitions  
**Key Fields:**
- `giveaway_id` - Giveaway identifier
- `prize` - Prize description
- `winners` - Number of winners
- `ends_at` - End timestamp
- `status` - Giveaway status (active, ended, cancelled)

**Usage:**
- Giveaway creation and management
- Winner selection
- Persistence across bot restarts

#### **`tbl_giveaway_participants`**
**Purpose:** Giveaway participants  
**Key Fields:**
- `giveaway_id` - Giveaway identifier
- `user_id` - Discord user ID
- `joined_at` - Join timestamp

**Usage:**
- Participant tracking
- Winner selection
- Participation statistics

#### **`tbl_giveaway_winners`**
**Purpose:** Giveaway winner records  
**Key Fields:**
- `giveaway_id` - Giveaway identifier
- `user_id` - Discord user ID
- `won_at` - Win timestamp

**Usage:**
- Winner tracking
- Reward distribution
- Giveaway history

#### **`tbl_cheese_races`**
**Purpose:** Discord Race event definitions  
**Key Fields:**
- `race_id` - Race identifier
- `creator_id` - Creator Discord ID
- `status` - Race status (waiting, active, ended)
- `dspoinc_reward` - DSPOINC reward amount
- `created_at` - Creation timestamp

**Usage:**
- Race creation and management
- Race state tracking
- Database persistence

#### **`tbl_cheese_rumbles`**
**Purpose:** Cheese Rumble battle royale event definitions  
**Key Fields:**
- `rumble_id` - Rumble identifier
- `creator_id` - Creator Discord ID
- `status` - Rumble status
- `winner_reward` - Winner DSPOINC reward
- `first_out_reward` - First eliminated reward

**Usage:**
- Rumble creation and management
- Event tracking
- Reward distribution

---

### **Season Management Tables**

#### **`tbl_seasons`**
**Purpose:** Season definitions  
**Key Fields:**
- `season_id` - Season identifier
- `season_name` - Season name
- `start_date` - Season start date
- `end_date` - Season end date
- `is_active` - Active season flag

**Usage:**
- Season management
- Current season detection
- Season switching

#### **`tbl_season_settings`**
**Purpose:** Season-specific game configurations  
**Key Fields:**
- `season_id` - Season identifier
- `game` - Game identifier
- `setting_key` - Setting key
- `setting_value` - Setting value

**Usage:**
- Per-season game configuration
- Setting management
- Configuration history

#### **`tbl_historical_stats`**
**Purpose:** Historical game season statistics  
**Key Fields:**
- `season_id` - Season identifier
- `game` - Game identifier
- `stat_key` - Statistic key
- `stat_value` - Statistic value

**Usage:**
- Historical data preservation
- Cross-season analysis
- Statistics tracking

---

### **NFT & Wallet Tables**

#### **`tbl_nft_ownership`**
**Purpose:** NFT ownership records  
**Key Fields:**
- `wallet_address` - Solana wallet address
- `collection_type` - Collection type (genesis, vip)
- `token_id` - NFT token ID
- `verified_at` - Verification timestamp

**Usage:**
- NFT holder verification
- Role granting
- Ownership tracking

#### **`tbl_holder_verifications`**
**Purpose:** Holder verification records  
**Key Fields:**
- `user_id` - Discord user ID
- `wallet_address` - Wallet address
- `collection_type` - Collection type
- `verified_at` - Verification timestamp

**Usage:**
- Verification audit trail
- Role grant tracking
- Verification history

---

### **Twitter Mission Tables**

#### **`tbl_twitter_missions`**
**Purpose:** Twitter mission definitions  
**Key Fields:**
- `mission_id` - Mission identifier
- `tweet_url` - Tweet URL
- `mission_type` - Mission type (like, retweet, comment, etc.)
- `reward_dspoinc` - DSPOINC reward
- `duration_hours` - Mission duration
- `status` - Mission status

**Usage:**
- Twitter mission creation
- Mission management
- Reward distribution

#### **`tbl_twitter_mission_participants`**
**Purpose:** Twitter mission participants  
**Key Fields:**
- `mission_id` - Mission identifier
- `user_id` - Discord user ID
- `verification_status` - Verification status (pending, verified)
- `joined_at` - Join timestamp

**Usage:**
- Participant tracking
- Verification workflow
- Reward distribution

---

### **Bug Tracker Tables**

#### **`tbl_bug_reports`**
**Purpose:** Main bug report records  
**Key Fields:**
- `bug_id` - Bug identifier
- `discord_message_id` - Discord message ID
- `title` - Bug title
- `description` - Bug description
- `category_id` - Bug category
- `priority_id` - Bug priority
- `status_id` - Bug status

**Usage:**
- Bug tracking
- Issue management
- Status monitoring

---

### **3D Game Tables**

#### **`tbl_riddle_completions`**
**Purpose:** Three.js Cheese Temple riddle completions  
**Key Fields:**
- `user_id` - Discord user ID
- `riddle_id` - Riddle identifier
- `level_id` - Level identifier
- `completed_at` - Completion timestamp
- `dspoinc_awarded` - DSPOINC reward

**Usage:**
- Riddle completion tracking
- Duplicate prevention
- Reward distribution

---

## 🔗 **TABLE RELATIONSHIPS**

### **User-Centric Relationships**

```
tbl_users (discord_id)
  ├── tbl_user_scores (user_id)
  ├── tbl_user_roles (user_id)
  ├── tbl_user_traits (user_id)
  ├── tbl_user_inventory (user_id)
  ├── tbl_score_adjustments (user_id)
  ├── tbl_tetris_achievements (user_id)
  ├── tbl_snake_achievements (user_id)
  ├── tbl_space_invaders_achievements (user_id)
  └── tbl_holder_verifications (user_id)
```

### **Game Score Relationships**

```
tbl_tetris_scores (discord_id)
  ├── game = 'tetris'
  ├── game = 'snake'
  └── game = 'space_invaders'

tbl_race_participants (user_id)
  └── tbl_cheese_races (race_id)

tbl_rumble_participants (user_id)
  └── tbl_cheese_rumbles (rumble_id)
```

### **Store System Relationships**

```
tbl_store_items (item_id)
  ├── tbl_user_inventory (item_id)
  └── tbl_purchase_history (item_id)
```

### **Quest System Relationships**

```
tbl_quests (quest_id)
  └── tbl_quest_claims (quest_id)
```

### **Giveaway System Relationships**

```
tbl_giveaways (giveaway_id)
  ├── tbl_giveaway_participants (giveaway_id)
  └── tbl_giveaway_winners (giveaway_id)
```

---

## 🗺️ **FIELD MAPPINGS**

### **Critical Field Mappings**

#### **User Identification Fields**
Different tables use different field names for user identification:
- **`tbl_users`:** `discord_id` (PRIMARY KEY)
- **`tbl_tetris_scores`:** `discord_id` (NOT `user_id`)
- **`tbl_user_scores`:** `user_id` (Discord ID)
- **`tbl_cheese_clicks`:** `user_wallet` (Discord ID)
- **`tbl_race_participants`:** `user_id` (Discord ID)
- **`tbl_rumble_participants`:** `user_id` (Discord ID)
- **All Achievement Tables:** `user_id` (Discord ID)

#### **Position Fields**
- **`tbl_race_participants`:** `position` (NOT `final_position`)
- **`tbl_rumble_participants`:** `final_position` (NOT `position`)

#### **Game Identification**
- **`tbl_tetris_scores`:** `game` field ('tetris', 'snake', 'space_invaders')
- **`tbl_user_scores`:** `game` field (various game identifiers)
- **`tbl_store_items`:** No game field (universal items)

---

## 🔌 **DATABASE INTEGRATION**

### **Integration Points**

#### **1. Discord Bot Integration**
- **API Endpoint:** `/api/discord/db-access.php`
- **Authentication:** Bearer token
- **Operations:** SELECT, INSERT, UPDATE, DELETE
- **Tables Used:** All tables (via authenticated queries)

#### **2. Admin Interface Integration**
- **API Endpoints:** `/api/admin/*.php`
- **Authentication:** Session-based
- **Operations:** Full CRUD operations
- **Tables Used:** All tables (admin access)

#### **3. Game Integration**
- **Score Saving:** `/api/dev/save-score.php`
- **Achievement Saving:** `/api/user/save-*-achievement.php`
- **Tables Used:** Game-specific score and achievement tables

#### **4. Profile Page Integration**
- **API Endpoints:** `/api/user/*.php`
- **Authentication:** Session-based (user access)
- **Operations:** READ operations
- **Tables Used:** User data, scores, achievements, inventory

#### **5. Store Integration**
- **API Endpoints:** `/api/store/*.php`
- **Operations:** Item browsing, purchasing, inventory
- **Tables Used:** `tbl_store_items`, `tbl_user_inventory`, `tbl_purchase_history`

---

## 💾 **BACKUP & MAINTENANCE**

### **Backup Strategy**

#### **Regular Backups**
- **Location:** `db/backup/`
- **Frequency:** Before major operations, season resets, deployments
- **Naming:** `narrrf_world_backup_YYYYMMDD_HHMMSS.sqlite`

#### **Production Backup**
- **Command:** `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite`
- **Purpose:** Preserve database state for next deployment
- **Critical:** Always backup before pushing to production

#### **Local Backup**
- **Command:** `cp db/narrrf_world.sqlite db/backup/narrrf_world_backup_[timestamp].sqlite`
- **Purpose:** Local development snapshots

### **Maintenance Operations**

#### **Database Optimization**
```sql
-- Vacuum database (reclaim space)
VACUUM;

-- Analyze tables (update statistics)
ANALYZE;

-- Reindex database
REINDEX;
```

#### **Data Cleanup**
- **Duplicate Score Cleanup:** `/synch-scores` Discord command
- **Orphaned Records:** Manual cleanup via admin interface
- **Historical Data:** Preserved in historical tables

---

## 📝 **QUERY PATTERNS**

### **Common Query Patterns**

#### **User Balance Calculation**
```sql
SELECT SUM(score) as total_balance
FROM tbl_user_scores
WHERE user_id = ?;
```

#### **Game Score Retrieval**
```sql
SELECT COUNT(*) as total_games, 
       MAX(score) as best_score, 
       SUM(score) as total_score
FROM tbl_tetris_scores
WHERE discord_id = ? AND game = ?;
```

#### **Achievement Check**
```sql
SELECT achievement_key, unlocked_at
FROM tbl_tetris_achievements
WHERE user_id = ? AND unlocked_at IS NOT NULL;
```

#### **Recent Score Adjustments**
```sql
SELECT amount, reason, timestamp
FROM tbl_score_adjustments
WHERE user_id = ?
ORDER BY timestamp DESC
LIMIT 10;
```

#### **Season-Aware Queries**
```sql
SELECT *
FROM tbl_tetris_scores
WHERE discord_id = ? 
  AND game = ?
  AND is_current_season = 1;
```

---

## ⚡ **PERFORMANCE OPTIMIZATION**

### **Indexes**

#### **Primary Indexes**
- All tables have PRIMARY KEY indexes
- User identification fields indexed for fast lookups

#### **Query Optimization**
- Use parameterized queries (prepared statements)
- Limit result sets with LIMIT clause
- Use appropriate WHERE clauses for filtering

### **Connection Pooling**
- Reuse database connections where possible
- Close connections after use
- Use connection pooling in PHP when available

---

## 🔄 **MIGRATION SYSTEM**

### **Migration Files**
- **Location:** `db/migrations/`
- **Purpose:** Schema changes and data migrations
- **Naming:** Descriptive names with dates

### **Migration Examples**
- `create_nft_verification_tables.sql` - NFT verification tables
- `create_score_tables.sql` - Score table creation
- `add_historical_stats_table.sql` - Historical stats table
- `fix_tetris_achievement_icons.sql` - Achievement icon fixes

### **Migration Execution**
```bash
# Execute migration
sqlite3 narrrf_world.sqlite < db/migrations/migration_file.sql
```

---

## 📊 **DATABASE STATISTICS**

### **Table Counts**
- **Total Tables:** 66 tables
- **User Tables:** 6 tables
- **Game Tables:** 5 tables
- **Achievement Tables:** 3 tables
- **Store Tables:** 4 tables
- **Discord Event Tables:** 6 tables
- **Season Tables:** 4 tables
- **Other Tables:** 38 tables

### **Database Size**
- **Current Size:** ~6.8 MB
- **Growth Rate:** Moderate (with regular backups)
- **Optimization:** Regular VACUUM operations

---

## ✅ **SUMMARY**

The Narrrf's World database system provides:
- ✅ **66 tables** covering all system aspects
- ✅ **User-centric design** with Discord ID as primary identifier
- ✅ **Game-agnostic structure** supporting unlimited games
- ✅ **Complete audit trails** for all critical operations
- ✅ **Season-aware data** with historical preservation
- ✅ **Secure access** via authenticated APIs
- ✅ **Production-ready** with backup and maintenance strategies
- ✅ **Scalable architecture** supporting decades of growth

**Status:** ✅ **Production Ready** - All systems operational and optimized

---

**Document Created:** December 20, 2025  
**Last Updated:** December 20, 2025  
**Version:** 1.0.0  
**Maintainer:** Narrrf's World Development Team

