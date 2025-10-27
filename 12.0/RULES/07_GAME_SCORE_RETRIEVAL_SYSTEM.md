# 🎯 CURSOR RULE: PERFECT 5-GAME SCORE RETRIEVAL SYSTEM V3.0

## 🚨 CRITICAL RULE FOR CURSOR - ALWAYS FOLLOW THIS SYSTEM

**Last Updated:** October 26, 2025 - Bug #104 Complete  
**Status:** ✅ **ALL SYSTEMS VERIFIED AND WORKING**  

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

---

## 🎯 **ROLE-BASED SCORING SYSTEM (Oct 26, 2025)**

### **CRITICAL: All 3 main games support role multipliers:**

**Discord Role Multipliers:**
- 🎴 **VIP Holder:** 2.0x multiplier (ID: 1332016526848692345)
- 🏆 **Holder:** 1.5x multiplier (ID: 1402668301414563971)
- 🔴 **Champion:** 1.4x multiplier (ID: 1332017420591697972)
- 🟢 **Season Tester:** 1.3x multiplier (ID: 1417279348989497532)
- 🔵 **Early Bird:** 1.2x multiplier (ID: 1332017614108758148)
- 🧀 **Cheese Hunter:** 1.1x multiplier (ID: 1399651053682692208)

**Scoring Examples (Bug #104 Verified):**
- **Snake:** VIP=20, Holder=15, Champion=14, Season Tester=13, Early Bird=12, Cheese Hunter=11 DSPOINC/cheese
- **Tetris:** VIP=16, Holder=12, Champion=11, Season Tester=10, Early Bird=10, Cheese Hunter=9 DSPOINC/line
- **Space Invaders:** VIP=~72, Holder=~54, Champion=~50, Season Tester=~47, Early Bird=~43, Cheese Hunter=~40 DSPOINC/game

**Critical Fixes Applied:**
1. **Math.round() for Tetris** - Fair rounding of fractional bonuses (Champion 1.4x now gives 3, not 2)
2. **Backend fix for Snake** - Removed double multiplication (pointsPerUnit = 1)
3. **Season Tester theme** - Changed from rainbow to green (all 3 games)

**Testing Status:** 18/18 role combinations tested and verified ✅

---

## 🏆 **ACHIEVEMENT SYSTEM ARCHITECTURE (Oct 26-27, 2025)**

### **CRITICAL: All 3 games use dynamic database loading:**

**Achievement Counts:**
- **Tetris:** 25 achievements (tbl_tetris_achievements)
- **Snake:** 20 achievements (tbl_snake_achievements)
- **Space Invaders:** 28 achievements (tbl_space_invaders_achievements)
- **TOTAL:** 73 achievements across all games

**Architecture Pattern (ALL 3 GAMES):**
```php
// ✅ CORRECT: Load definitions from database
$stmt = $pdo->prepare("
    SELECT achievement_key, achievement_title, achievement_description, achievement_icon
    FROM tbl_[game]_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
");
```

**Critical Rules:**
1. **NEVER hardcode achievement descriptions in API files**
2. **ALWAYS load from database WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'**
3. **ALWAYS use dynamic HTML generation (no hardcoded cards)**
4. **ALWAYS include icon mapping functions for emoji encoding**

**Bugs Fixed:**
- ✅ Tetris: Impossible combos, unrealistic thresholds (Bugs #131, #136, #127, #134)
- ✅ Snake: Unrealistic thresholds (10k-50k → 200-3.5k based on grid max)
- ✅ Space Invaders: All thresholds (30k-300k → 1k-20k), hardcoded API/HTML removed
- ✅ All 3: NULL unlocked_at fixed (Bug #152)

**Technical Documentation:**
- TETRIS_ACHIEVEMENTS_SYSTEM.md (25KB, 775 lines)
- SNAKE_ACHIEVEMENTS_SYSTEM.md (20KB, 624 lines)
- SPACE_INVADERS_ACHIEVEMENTS_SYSTEM.md (27KB, 794 lines)
- **Total:** 72KB, 2,193 lines

---

## 🚨 **FINAL WARNING:**

**FOLLOW THIS RULE RELIGIOUSLY - IT'S THE FOUNDATION OF THE ENTIRE SCORING AND ACHIEVEMENT SYSTEM!**

**This rule ensures perfect synchronization between:**
- ✅ Admin interface
- ✅ User profiles
- ✅ Game displays
- ✅ Achievement systems
- ✅ Role-based multipliers

**Last Updated:** October 26, 2025  
**Status:** ✅ **VERIFIED AND WORKING - ALL 73 ACHIEVEMENTS + 18 ROLE COMBOS**  
**Source:** Master Ruleset V3.0 - Single Source of Truth  
**Testing:** Complete - 18/18 roles, 73/73 achievements verified