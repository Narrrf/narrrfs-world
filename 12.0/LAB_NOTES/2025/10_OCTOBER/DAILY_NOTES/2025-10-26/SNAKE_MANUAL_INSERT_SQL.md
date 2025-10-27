# 🐍 SNAKE ACHIEVEMENT DEFINITIONS - MANUAL INSERT

**Created:** October 26, 2025 - 22:45  
**Purpose:** Manually insert 20 Snake achievement definitions into local database  
**Reason:** Fix "Error loading achievements" on profile page  

---

## 📋 **SQL INSERT STATEMENT**

```sql
-- 🐍 Insert all 20 Snake achievement definitions (REVISED 2025-10-26)
-- Based on theoretical max ~3,920 DSPOINC (196 cheese, 98% grid coverage)
-- Grid: 10×20 = 200 tiles

INSERT INTO tbl_snake_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) VALUES

-- === CHEESE-BASED (5 achievements) ===
('ACHIEVEMENT_DEFINITIONS', 'first_cheese', 'First Cheese', 'Eat your first cheese', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_collector', 'Cheese Collector', 'Eat 5 cheeses in one game', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_hunter', 'Cheese Hunter', 'Eat 10 cheeses in one game', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_master', 'Cheese Master', 'Eat 25 cheeses in one game', '🧀'),
('ACHIEVEMENT_DEFINITIONS', 'cheese_legend', 'Cheese Legend', 'Eat 75 cheeses in one game', '🧀'),

-- === SCORE-BASED (6 achievements - 5% to 89% of theoretical max 3920) ===
('ACHIEVEMENT_DEFINITIONS', 'score_hunter', 'Score Hunter', 'Earn 200 DSPOINC in one game', '🎯'),
('ACHIEVEMENT_DEFINITIONS', 'point_master', 'Point Master', 'Earn 500 DSPOINC in one game', '⭐'),
('ACHIEVEMENT_DEFINITIONS', 'high_scorer', 'High Scorer', 'Earn 1,000 DSPOINC in one game', '🌟'),
('ACHIEVEMENT_DEFINITIONS', 'snake_king', 'Snake King', 'Earn 1,500 DSPOINC in one game', '👑'),
('ACHIEVEMENT_DEFINITIONS', 'score_legend', 'Score Legend', 'Earn 2,000 DSPOINC in one game', '💫'),
('ACHIEVEMENT_DEFINITIONS', 'score_god', 'Score God', 'Earn 3,500 DSPOINC (near maximum!)', '👑'),

-- === LEVEL-BASED (4 achievements) ===
('ACHIEVEMENT_DEFINITIONS', 'speed_demon', 'Speed Demon', 'Reach Level 5', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'level_master', 'Level Master', 'Reach Level 10', '🏆'),
('ACHIEVEMENT_DEFINITIONS', 'level_warrior', 'Level Warrior', 'Reach Level 15', '⚔️'),
('ACHIEVEMENT_DEFINITIONS', 'level_champion', 'Level Champion', 'Reach Level 20', '🏅'),

-- === LENGTH-BASED (3 achievements) ===
('ACHIEVEMENT_DEFINITIONS', 'long_snake', 'Long Snake', 'Grow to 10 segments', '🐍'),
('ACHIEVEMENT_DEFINITIONS', 'giant_snake', 'Giant Snake', 'Grow to 25 segments', '🐍'),
('ACHIEVEMENT_DEFINITIONS', 'mega_snake', 'Mega Snake', 'Grow to 50 segments', '🐍'),

-- === TIME-BASED (2 achievements) ===
('ACHIEVEMENT_DEFINITIONS', 'survivor', 'Survivor', 'Survive for 2 minutes', '⏰'),
('ACHIEVEMENT_DEFINITIONS', 'endurance_master', 'Endurance Master', 'Survive for 5 minutes', '⏰');
```

---

## 🚀 **EXECUTION COMMANDS**

### **Local Database (Windows PowerShell):**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Execute the INSERT
sqlite3 db/narrrf_world.sqlite "INSERT INTO tbl_snake_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) VALUES ('ACHIEVEMENT_DEFINITIONS', 'first_cheese', 'First Cheese', 'Eat your first cheese', '🧀'), ('ACHIEVEMENT_DEFINITIONS', 'cheese_collector', 'Cheese Collector', 'Eat 5 cheeses in one game', '🧀'), ('ACHIEVEMENT_DEFINITIONS', 'cheese_hunter', 'Cheese Hunter', 'Eat 10 cheeses in one game', '🧀'), ('ACHIEVEMENT_DEFINITIONS', 'cheese_master', 'Cheese Master', 'Eat 25 cheeses in one game', '🧀'), ('ACHIEVEMENT_DEFINITIONS', 'cheese_legend', 'Cheese Legend', 'Eat 75 cheeses in one game', '🧀'), ('ACHIEVEMENT_DEFINITIONS', 'score_hunter', 'Score Hunter', 'Earn 200 DSPOINC in one game', '🎯'), ('ACHIEVEMENT_DEFINITIONS', 'point_master', 'Point Master', 'Earn 500 DSPOINC in one game', '⭐'), ('ACHIEVEMENT_DEFINITIONS', 'high_scorer', 'High Scorer', 'Earn 1,000 DSPOINC in one game', '🌟'), ('ACHIEVEMENT_DEFINITIONS', 'snake_king', 'Snake King', 'Earn 1,500 DSPOINC in one game', '👑'), ('ACHIEVEMENT_DEFINITIONS', 'score_legend', 'Score Legend', 'Earn 2,000 DSPOINC in one game', '💫'), ('ACHIEVEMENT_DEFINITIONS', 'score_god', 'Score God', 'Earn 3,500 DSPOINC (near maximum!)', '👑'), ('ACHIEVEMENT_DEFINITIONS', 'speed_demon', 'Speed Demon', 'Reach Level 5', '⚡'), ('ACHIEVEMENT_DEFINITIONS', 'level_master', 'Level Master', 'Reach Level 10', '🏆'), ('ACHIEVEMENT_DEFINITIONS', 'level_warrior', 'Level Warrior', 'Reach Level 15', '⚔️'), ('ACHIEVEMENT_DEFINITIONS', 'level_champion', 'Level Champion', 'Reach Level 20', '🏅'), ('ACHIEVEMENT_DEFINITIONS', 'long_snake', 'Long Snake', 'Grow to 10 segments', '🐍'), ('ACHIEVEMENT_DEFINITIONS', 'giant_snake', 'Giant Snake', 'Grow to 25 segments', '🐍'), ('ACHIEVEMENT_DEFINITIONS', 'mega_snake', 'Mega Snake', 'Grow to 50 segments', '🐍'), ('ACHIEVEMENT_DEFINITIONS', 'survivor', 'Survivor', 'Survive for 2 minutes', '⏰'), ('ACHIEVEMENT_DEFINITIONS', 'endurance_master', 'Endurance Master', 'Survive for 5 minutes', '⏰');"

# Verify insertion
sqlite3 db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';"

# Check the definitions
sqlite3 db/narrrf_world.sqlite "SELECT achievement_key, achievement_description FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' ORDER BY achievement_key;"
```

---

## ✅ **EXPECTED RESULTS**

### **Count Check:**
```
20
```

### **Definitions Check (Sample):**
```
cheese_collector|Eat 5 cheeses in one game
cheese_hunter|Eat 10 cheeses in one game
cheese_legend|Eat 75 cheeses in one game
first_cheese|Eat your first cheese
high_scorer|Earn 1,000 DSPOINC in one game
point_master|Earn 500 DSPOINC in one game
score_god|Earn 3,500 DSPOINC (near maximum!)
score_hunter|Earn 200 DSPOINC in one game
score_legend|Earn 2,000 DSPOINC in one game
snake_king|Earn 1,500 DSPOINC in one game
```

---

## 🎯 **AFTER INSERTION**

### **Refresh Profile Page:**
1. Go to `http://localhost/public/profile.html`
2. Click "🐍 View Snake Achievements" button
3. Should now show:
   - ✅ "Total Achievements: 20"
   - ✅ All 20 achievement cards
   - ✅ Correct descriptions with DSPOINC values
   - ✅ No "Error loading achievements"

---

**🐍 READY TO INSERT SNAKE ACHIEVEMENT DEFINITIONS!**

