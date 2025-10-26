# 🔧 UPDATE TETRIS ACHIEVEMENT DEFINITIONS IN DATABASE

**Date:** October 26, 2025  
**Time:** 21:15  
**Issue:** Profile page shows 29 old achievements, game code has 24 new achievements  
**Solution:** Update database ACHIEVEMENT_DEFINITIONS to match new code  

---

## 🎯 **THE PROBLEM**

### **What's Happening:**
1. Profile page loads achievement definitions from database
2. Database has `user_id = 'ACHIEVEMENT_DEFINITIONS'` with old thresholds
3. Game code has new, fixed thresholds
4. **Result:** Profile shows old unreachable achievements!

### **Mismatch:**
- **Database:** 29 achievements (with perfect_clear, ultimate_player, tetris_champion, score_god)
- **Game Code:** 24 achievements (removed duplicates and impossible ones)

---

## 🔧 **THE FIX**

### **Step 1: Delete Old Definitions**
```sql
DELETE FROM tbl_tetris_achievements 
WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';
```

### **Step 2: Insert New Definitions**
Run the `init-tetris-achievements.php` API to insert the 24 new achievement definitions.

**OR** manually insert via SQL (backup option if API doesn't work).

---

## 🚀 **EXECUTION PLAN**

### **Local (Test First):**
```bash
# Delete old definitions
cd C:\xampp-server\htdocs\narrrfs-world
echo "DELETE FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 db/narrrf_world.sqlite

# Verify deleted
echo "SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 db/narrrf_world.sqlite

# Call API to re-initialize
curl -X POST http://localhost/api/dev/init-tetris-achievements.php

# Verify 24 new definitions inserted
echo "SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 db/narrrf_world.sqlite
```

**Expected Output:** Count should be 24

### **Production (After Local Test):**
```bash
# In Render shell
cd /var/www/html/db

# Delete old definitions
echo "DELETE FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

# Backup database
cp narrrf_world.sqlite /data/narrrf_world.sqlite

# Call production API (use curl or manually insert)
curl -X POST https://narrrfs.world/api/dev/init-tetris-achievements.php

# Verify
echo "SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
```

---

## ✅ **VERIFICATION**

### **Check Achievement Definitions:**
```sql
SELECT achievement_key, achievement_title, game_score, lines_cleared, level_reached 
FROM tbl_tetris_achievements 
WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' 
ORDER BY achievement_key;
```

### **Should Show:**
- 24 achievements total
- Score thresholds: 200, 800, 1500, 2000, 2500
- Line thresholds: 1, 10, 30, 50, 100
- Level thresholds: 5, 8, 12, 15
- NO `score_god`, `perfect_clear`, `ultimate_player`, `tetris_champion`

---

## 📊 **EXPECTED RESULTS**

### **After Update:**
- Profile page will show 24 achievements (not 29)
- All thresholds will match game code
- Removed impossible achievements
- Fixed combo logic descriptions
- All achievements now reachable!

---

**Status:** Ready to execute  
**Risk:** LOW (only updating definitions, not user data)  
**Rollback:** Can re-run old init script if needed

