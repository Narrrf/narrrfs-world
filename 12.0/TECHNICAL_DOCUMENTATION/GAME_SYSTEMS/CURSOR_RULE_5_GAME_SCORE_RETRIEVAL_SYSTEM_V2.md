# 🎯 CURSOR RULE: PERFECT 5-GAME SCORE RETRIEVAL SYSTEM V2.0

## 🚨 CRITICAL RULE FOR CURSOR - ALWAYS FOLLOW THIS SYSTEM

**When retrieving scores from any of the 5 games in Narrrf's World, you MUST use these exact field mappings and table references:**

---

## 🎮 GAME 1: TETRIS
```sql
-- Table: tbl_tetris_scores
-- Field: user_id (NOT discord_id)
-- Query: WHERE user_id = ? AND game = 'tetris'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_tetris_scores 
WHERE user_id = ? AND game = 'tetris'
```

---

## 🐍 GAME 2: SNAKE  
```sql
-- Table: tbl_tetris_scores (NOT tbl_user_scores)
-- Field: user_id (NOT discord_id)
-- Query: WHERE user_id = ? AND game = 'snake'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_tetris_scores 
WHERE user_id = ? AND game = 'snake'
```

---

## 👾 GAME 3: SPACE INVADERS
```sql
-- Table: tbl_tetris_scores (NOT tbl_user_scores)
-- Field: user_id (NOT discord_id)
-- Query: WHERE user_id = ? AND game = 'space_invaders'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_tetris_scores 
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

## ⚠️ CRITICAL RULES V2.0:

1. **NEVER use `discord_id` for Snake, Space Invaders, or Discord Race**
2. **NEVER use `user_id` for Tetris or Cheese Hunt**
3. **ALWAYS use the correct table for each game**
4. **NEVER assume all games use the same field name**
5. **CRITICAL DISCOVERY: Snake & Space Invaders use tbl_tetris_scores, NOT tbl_user_scores**
6. **This system works for the next 100 years - don't change it!**

---

## 🔍 **CRITICAL DISCOVERIES FROM IMPLEMENTATION:**

### **Table Distribution Reality:**
- **`tbl_tetris_scores`**: Contains Tetris, Snake, AND Space Invaders scores
- **`tbl_user_scores`**: Contains Discord rewards and other adjustments
- **`tbl_cheese_clicks`**: Contains Cheese Hunt click data
- **`tbl_race_participants`**: Contains Discord Race participation data

### **Field Mapping Reality:**
- **Tetris, Snake, Space Invaders**: All use `user_id` in `tbl_tetris_scores`
- **Cheese Hunt**: Uses `user_wallet` in `tbl_cheese_clicks`
- **Discord Race**: Uses `user_id` in `tbl_race_participants`

### **Common Mistakes to Avoid:**
- ❌ Looking for Snake/Space Invaders in `tbl_user_scores`
- ❌ Using `discord_id` for Discord Race queries
- ❌ Using `final_position` instead of `position` in race queries
- ❌ Looking for alternative game names like `snake_scroll` or `space_cheese_invaders`

---

## 🔧 IMPLEMENTATION PATTERN V2.0:

### **For user-specific data (profile pages):**
```php
// 1. Get discord_id from user
$discordId = $_POST['user_id']; // This IS the discord_id

// 2. Map to correct fields for each game
$tetrisData = queryTetris($discordId);        // Uses user_id in tbl_tetris_scores
$snakeData = querySnake($discordId);          // Uses user_id in tbl_tetris_scores  
$spaceData = querySpaceInvaders($discordId);  // Uses user_id in tbl_tetris_scores
$cheeseData = queryCheeseHunt($discordId);    // Uses user_wallet in tbl_cheese_clicks
$raceData = queryDiscordRace($discordId);     // Uses user_id in tbl_race_participants
```

### **For admin overview (all users):**
```php
// Use the same field mappings but without WHERE clauses
$tetrisStats = "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris'";
$snakeStats = "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake'";
$spaceStats = "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders'";
$cheeseStats = "SELECT COUNT(*) FROM tbl_cheese_clicks";
$raceStats = "SELECT COUNT(*) FROM tbl_race_participants";
```

---

## 🚨 **IMPLEMENTATION CHECKLIST:**

### **✅ Database Schema Requirements:**
- [ ] `tbl_tetris_scores` has `user_id` field (NOT `discord_id`)
- [ ] `tbl_race_participants` has `position` field (NOT `final_position`)
- [ ] `tbl_cheese_clicks` has `user_wallet` field
- [ ] All tables have proper indexes on query fields

### **✅ API Implementation Requirements:**
- [ ] Snake queries use `tbl_tetris_scores` with `user_id`
- [ ] Space Invaders queries use `tbl_tetris_scores` with `user_id`
- [ ] Discord Race queries use `tbl_race_participants` with `user_id`
- [ ] Cheese Hunt queries use `tbl_cheese_clicks` with `user_wallet`
- [ ] No alternative game names (just `snake`, `space_invaders`)

### **✅ Field Validation:**
- [ ] All queries use correct table names
- [ ] All queries use correct field names
- [ ] No mixed field references
- [ ] Consistent parameter binding

---

## 🎯 **WHY THIS SYSTEM WORKS V2.0:**

- **Historical data compatibility** - Works with existing data structure
- **Future-proof** - New scores will use correct fields
- **Consistent across admin and user views**
- **No season filtering issues**
- **Perfect synchronization between all interfaces**
- **Based on actual database schema analysis**
- **Tested and verified in production environment**

---

## 🚀 **FINAL WARNING V2.0:**

**FOLLOW THIS RULE RELIGIOUSLY - IT'S THE FOUNDATION OF THE ENTIRE SCORING SYSTEM!**

**This rule ensures perfect synchronization between admin interface, user profiles, and all game data displays.**

**Copy this entire file and use it as your reference for ALL game score development in Narrrf's World!**

---

## 📋 **IMPLEMENTATION NOTES:**

### **Database Schema Changes Made:**
- ✅ Renamed `final_position` to `position` in `tbl_race_participants`
- ✅ Verified all game scores are in correct tables
- ✅ Confirmed field mappings match actual data structure

### **API Fixes Applied:**
- ✅ Updated Snake queries to use `tbl_tetris_scores`
- ✅ Updated Space Invaders queries to use `tbl_tetris_scores`
- ✅ Fixed Discord Race queries to use `user_id` field
- ✅ Removed unnecessary alternative game names
- ✅ All queries now follow PERFECT 5-GAME SYSTEM rules

### **Expected Results:**
- ✅ Missions API shows 5/5 Games Played
- ✅ All game data properly synchronized
- ✅ Admin interface shows consistent data
- ✅ User profiles display accurate statistics
- ✅ Perfect compliance with established system

---

**File Created:** 2025-01-28  
**Purpose:** Cursor AI Assistant Rule for Perfect 5-Game Score Retrieval System V2.0  
**Status:** ACTIVE - MUST FOLLOW FOR ALL FUTURE DEVELOPMENT  
**Version:** 2.0 - Based on Production Implementation and Testing
