# 🎯 CURSOR RULE: PERFECT 5-GAME SCORE RETRIEVAL SYSTEM

## 🚨 CRITICAL RULE FOR CURSOR - ALWAYS FOLLOW THIS SYSTEM

**When retrieving scores from any of the 5 games in Narrrf's World, you MUST use these exact field mappings:**

---

## 🎮 GAME 1: TETRIS
```sql
-- Table: tbl_tetris_scores
-- Field: discord_id (NOT user_id)
-- Query: WHERE discord_id = ? AND game = 'tetris'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_tetris_scores 
WHERE discord_id = ? AND game = 'tetris'
```

---

## 🐍 GAME 2: SNAKE  
```sql
-- Table: tbl_user_scores
-- Field: user_id (NOT discord_id)
-- Query: WHERE user_id = ? AND game = 'snake'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_user_scores 
WHERE user_id = ? AND game = 'snake'
```

---

## 👾 GAME 3: SPACE INVADERS
```sql
-- Table: tbl_user_scores
-- Field: user_id (NOT discord_id)
-- Query: WHERE user_id = ? AND game = 'space_invaders'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_user_scores 
WHERE user_id = ? AND game = 'space_invaders'
```

---

## 🧀🎯 GAME 4: CHEESE HUNT
```sql
-- Table: tbl_cheese_clicks
-- Field: user_wallet (NOT discord_id)
-- Query: WHERE user_wallet = ?
-- Example:
SELECT COUNT(*) as total_clicks, COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks
FROM tbl_cheese_clicks 
WHERE user_wallet = ?
```

---

## 🏁🧀 GAME 5: DISCORD CHEESE RACE
```sql
-- Table: tbl_race_participants
-- Field: user_id (NOT discord_id)
-- Query: WHERE user_id = ?
-- Example:
SELECT COUNT(*) as total_races, COUNT(CASE WHEN position = 1 THEN 1 END) as wins
FROM tbl_race_participants 
WHERE user_id = ?
```

---

## ⚠️ CRITICAL RULES:

1. **NEVER use `discord_id` for Snake, Space Invaders, or Discord Race**
2. **NEVER use `user_id` for Tetris or Cheese Hunt**
3. **ALWAYS use the correct table for each game**
4. **NEVER assume all games use the same field name**
5. **This system works for the next 100 years - don't change it!**

---

## 🔧 IMPLEMENTATION PATTERN:

### **For user-specific data (profile pages):**
```php
// 1. Get discord_id from user
$discordId = $_POST['user_id']; // This IS the discord_id

// 2. Map to correct fields for each game
$tetrisData = queryTetris($discordId);        // Uses discord_id
$snakeData = querySnake($discordId);          // Uses user_id  
$spaceData = querySpaceInvaders($discordId);  // Uses user_id
$cheeseData = queryCheeseHunt($discordId);    // Uses user_wallet
$raceData = queryDiscordRace($discordId);     // Uses user_id
```

### **For admin overview (all users):**
```php
// Use the same field mappings but without WHERE clauses
$tetrisStats = "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris'";
$snakeStats = "SELECT COUNT(*) FROM tbl_user_scores WHERE game = 'snake'";
$spaceStats = "SELECT COUNT(*) FROM tbl_user_scores WHERE game = 'space_invaders'";
$cheeseStats = "SELECT COUNT(*) FROM tbl_cheese_clicks";
$raceStats = "SELECT COUNT(*) FROM tbl_race_participants";
```

---

## 🎯 WHY THIS SYSTEM WORKS:

- **Historical data compatibility** - Works with existing data
- **Future-proof** - New scores will use correct fields
- **Consistent across admin and user views**
- **No season filtering issues**
- **Perfect synchronization between all interfaces**

---

## 🚀 FINAL WARNING:

**FOLLOW THIS RULE RELIGIOUSLY - IT'S THE FOUNDATION OF THE ENTIRE SCORING SYSTEM!**

**This rule ensures perfect synchronization between admin interface, user profiles, and all game data displays.**

**Copy this entire file and use it as your reference for ALL game score development in Narrrf's World!**

---

**File Created:** 2025-01-28  
**Purpose:** Cursor AI Assistant Rule for Perfect 5-Game Score Retrieval  
**Status:** ACTIVE - MUST FOLLOW FOR ALL FUTURE DEVELOPMENT
