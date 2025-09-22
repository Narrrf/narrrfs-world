# 🎯 CURSOR RULE: PERFECT 5-GAME SCORE RETRIEVAL SYSTEM V2.0

## 🚨 CRITICAL RULE FOR CURSOR - ALWAYS FOLLOW THIS SYSTEM

**When retrieving scores from any of the 5 games in Narrrf's World, you MUST use these exact field mappings and table references:**

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
-- Table: tbl_tetris_scores (NOT tbl_user_scores)
-- Field: discord_id (NOT user_id)
-- Query: WHERE discord_id = ? AND game = 'snake'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_tetris_scores 
WHERE discord_id = ? AND game = 'snake'
```

---

## 👾 GAME 3: SPACE INVADERS
```sql
-- Table: tbl_tetris_scores (NOT tbl_user_scores)
-- Field: discord_id (NOT user_id)
-- Query: WHERE discord_id = ? AND game = 'space_invaders'
-- Example:
SELECT COUNT(*) as total_games, MAX(score) as best_score, SUM(score) as total_score
FROM tbl_tetris_scores 
WHERE discord_id = ? AND game = 'space_invaders'
```

---

## 🧀🎯 GAME 4: CHEESE HUNT
```sql
-- Table: tbl_cheese_clicks
-- Field: user_wallet (contains Discord ID)
-- Query: WHERE user_wallet = ?
-- Example:
SELECT COUNT(*) as total_clicks, MAX(clicks) as best_clicks, SUM(clicks) as total_clicks
FROM tbl_cheese_clicks 
WHERE user_wallet = ?
```

---

## 🏁 GAME 5: DISCORD CHEESE RACE
```sql
-- Table: tbl_race_participants
-- Field: user_id (contains Discord ID)
-- Query: WHERE user_id = ?
-- Example:
SELECT COUNT(*) as total_races, COUNT(CASE WHEN position = 1 THEN 1 END) as wins
FROM tbl_race_participants 
WHERE user_id = ?
```

---

## 🚨 **CRITICAL FIELD MAPPING RULES:**

### **✅ CORRECT FIELD MAPPINGS:**
1. **Tetris, Snake, Space Invaders SCORES:** Use `discord_id` in `tbl_tetris_scores`
2. **Cheese Hunt SCORES:** Use `user_wallet` in `tbl_cheese_clicks`
3. **Discord Race SCORES:** Use `user_id` in `tbl_race_participants`
4. **ALL ACHIEVEMENTS:** Use `user_id` in their respective achievement tables

### **❌ COMMON MISTAKES TO AVOID:**
- ❌ Using `user_id` for Tetris, Snake, or Space Invaders SCORE queries
- ❌ Using `discord_id` for Cheese Hunt SCORE queries
- ❌ Looking for Snake/Space Invaders in `tbl_user_scores`
- ❌ Using `final_position` instead of `position` in race queries

---

## 🏆 **ACHIEVEMENT SYSTEM FIELD MAPPINGS:**

### **TETRIS ACHIEVEMENTS:**
```sql
-- Table: tbl_tetris_achievements
-- Field: user_id (contains Discord ID)
-- Query: WHERE user_id = ?
```

### **SNAKE ACHIEVEMENTS:**
```sql
-- Table: tbl_snake_achievements
-- Field: user_id (contains Discord ID)
-- Query: WHERE user_id = ?
```

### **SPACE INVADERS ACHIEVEMENTS:**
```sql
-- Table: tbl_space_invaders_achievements
-- Field: user_id (contains Discord ID)
-- Query: WHERE user_id = ?
```

---

## 📊 **DUAL TABLE STRATEGY:**

### **SCORE TABLES:**
- **`tbl_tetris_scores`** - Tetris, Snake, Space Invaders scores (uses `discord_id`)
- **`tbl_cheese_clicks`** - Cheese Hunt clicks (uses `user_wallet`)
- **`tbl_race_participants`** - Discord Race participation (uses `user_id`)

### **ACHIEVEMENT TABLES:**
- **`tbl_tetris_achievements`** - Tetris achievements (uses `user_id`)
- **`tbl_snake_achievements`** - Snake achievements (uses `user_id`)
- **`tbl_space_invaders_achievements`** - Space Invaders achievements (uses `user_id`)

---

## 🚨 **FINAL WARNING:**

**FOLLOW THIS RULE RELIGIOUSLY - IT'S THE FOUNDATION OF THE ENTIRE SCORING SYSTEM!**

**This rule ensures perfect synchronization between admin interface, user profiles, and all game data displays.**

**Last Updated:** September 14, 2025  
**Status:** ✅ **VERIFIED AND WORKING**  
**Source:** Master Ruleset - Single Source of Truth